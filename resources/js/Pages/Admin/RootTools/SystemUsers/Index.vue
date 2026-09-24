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
    UsersIcon,
    ShieldCheckIcon,
    UserGroupIcon,
    CommandLineIcon,
    LockClosedIcon,
    LockOpenIcon,
    CheckIcon,
    ArrowLeftIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    users: {
        type: Array,
        default: () => [],
    },
    groups: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({
            interactive_count: 0,
            system_daemon_count: 0,
            sudo_operators_count: 0,
            total_groups_count: 0,
            total_users_count: 0,
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
const feedbackMsg = ref('')

const applyFilters = () => {
    router.get(route('admin.root-tools.system-users'), {
        type: currentType.value !== 'all' ? currentType.value : undefined,
        search: search.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    })
}

const selectType = (type) => {
    currentType.value = type
    applyFilters()
}

const resetFilters = () => {
    search.value = ''
    currentType.value = 'all'
    applyFilters()
}

// Toggle Shell Access
const toggleShell = (username, currentShell) => {
    const isCurrentlyInteractive = !currentShell.includes('nologin') && !currentShell.includes('false')
    const newShell = isCurrentlyInteractive ? '/usr/sbin/nologin' : '/bin/bash'
    const actionLabel = isCurrentlyInteractive ? 'disable interactive login' : 'enable bash shell'

    if (confirm(`Are you sure you want to ${actionLabel} for user '${username}'?`)) {
        router.post(route('admin.root-tools.system-users.toggle-shell'), {
            username: username,
            shell: newShell,
        }, {
            preserveScroll: true,
            onSuccess: () => {
                feedbackMsg.value = `Shell for user '${username}' updated to ${newShell}.`
                setTimeout(() => { feedbackMsg.value = '' }, 4000)
            }
        })
    }
}
</script>

<template>
    <Head title="System Users - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'System', href: '#' },
                    { label: 'Root Tools', href: route('admin.root-tools.terminal') },
                    { label: 'POSIX System Users & Daemon Accounts' }
                ]"
            >
                <template #actions>
                    <Link
                        :href="route('admin.root-tools.commands')"
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        <CommandLineIcon class="w-3.5 h-3.5 text-slate-500" />
                        <span>Command Runner</span>
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
                    title="Total Linux Users"
                    :value="String(stats.total_users_count || users.length || 0)"
                    badge="Accounts"
                    badgeType="info"
                    color="blue"
                    :icon="UsersIcon"
                />

                <InfoCard
                    title="Interactive Logins"
                    :value="String(stats.interactive_count || users.filter(u => !u.shell?.includes('nologin') && !u.shell?.includes('false')).length || 0)"
                    badge="Shells"
                    badgeType="success"
                    color="emerald"
                    :icon="CommandLineIcon"
                />

                <InfoCard
                    title="System Daemons"
                    :value="String(stats.system_daemon_count || 0)"
                    badge="Services"
                    badgeType="info"
                    color="purple"
                    :icon="UserGroupIcon"
                />

                <InfoCard
                    title="Sudo Superusers"
                    :value="String(stats.sudo_operators_count || 1)"
                    badge="Root"
                    badgeType="warning"
                    color="rose"
                    :icon="ShieldCheckIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Type Tabs -->
            <div class="flex items-center justify-between border-b border-slate-200 pb-2">
                <div class="flex flex-wrap items-center gap-2">
                    <button
                        v-for="t in ['all', 'interactive', 'daemon', 'sudo']"
                        :key="t"
                        type="button"
                        @click="selectType(t)"
                        :class="currentType === t ? 'bg-blue-600 text-white' : 'bg-white text-slate-700 hover:bg-slate-50 border border-slate-200'"
                        class="px-3 py-1.5 rounded-[3px] font-bold text-xs uppercase font-mono transition cursor-pointer shadow-2xs"
                    >
                        {{ t }}
                    </button>
                </div>
            </div>

            <!-- 4. Standard Filter Toolbar -->
            <DataTableFilter
                v-model="search"
                searchPlaceholder="Search username, UID, home directory, login shell..."
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
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Username</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">UID : GID</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Home Directory</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Login Shell</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Account Type</th>
                                <th class="py-2.5 px-3 w-28">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                            <tr v-for="(u, index) in users" :key="u.username || index" class="hover:bg-blue-50/30 transition">
                                <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 font-mono text-[11px] whitespace-nowrap">
                                    {{ index + 1 }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-bold text-slate-900 font-mono">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-xs border border-blue-100 shrink-0">
                                            <UsersIcon class="w-3.5 h-3.5" />
                                        </div>
                                        <span>{{ u.username }}</span>
                                    </div>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 font-mono text-[11px] text-slate-700 whitespace-nowrap text-center">
                                    {{ u.uid }} : {{ u.gid }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-mono text-[11px] text-slate-600">
                                    {{ u.home || '/nonexistent' }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-mono text-[11px] text-slate-800">
                                    <span :class="u.shell?.includes('nologin') || u.shell?.includes('false') ? 'text-slate-400' : 'text-emerald-700 font-bold'">
                                        {{ u.shell || '/bin/bash' }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span 
                                        :class="u.is_sudo ? 'bg-rose-50 text-rose-700 border-rose-200' : (!u.shell?.includes('nologin') && !u.shell?.includes('false') ? 'bg-blue-50 text-blue-700 border-blue-200' : 'bg-slate-100 text-slate-500 border-slate-200')"
                                        class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase border font-mono"
                                    >
                                        {{ u.is_sudo ? 'Sudo Root' : (!u.shell?.includes('nologin') && !u.shell?.includes('false') ? 'Interactive' : 'Daemon') }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1">
                                        <RowActionDropdown>
                                            <button
                                                type="button"
                                                @click="toggleShell(u.username, u.shell || '')"
                                                class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 font-medium cursor-pointer"
                                            >
                                                <CommandLineIcon class="w-3.5 h-3.5 text-slate-500" />
                                                <span>{{ (!u.shell?.includes('nologin') && !u.shell?.includes('false')) ? 'Disable Login (/sbin/nologin)' : 'Enable Shell (/bin/bash)' }}</span>
                                            </button>
                                        </RowActionDropdown>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="!users || users.length === 0">
                                <td colspan="7" class="py-12 text-center text-slate-400 font-sans">
                                    No Linux user accounts found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
