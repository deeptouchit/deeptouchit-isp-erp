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
    CheckIcon,
    XMarkIcon,
    ShieldCheckIcon,
    UserGroupIcon,
    GlobeAltIcon,
    ComputerDesktopIcon,
    TrashIcon,
    ClockIcon,
    ArrowRightOnRectangleIcon,
    DocumentDuplicateIcon,
    ArrowLeftIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    sessions: {
        type: Object,
        default: () => ({ data: [], links: [] }),
    },
    stats: {
        type: Object,
        default: () => ({
            total_active_sessions: 0,
            unique_operators: 0,
            static_ip_sessions: 0,
            guest_sessions: 0,
        }),
    },
    filters: {
        type: Object,
        default: () => ({ search: '', scope: 'authenticated' }),
    },
})

// Flash Feedback Toast
const feedbackMsg = ref('')

// Filter State
const search = ref(props.filters?.search || '')
const currentScope = ref(props.filters?.scope || 'authenticated')

const applyFilters = () => {
    router.get(route('admin.administration.sessions'), {
        search: search.value || undefined,
        scope: currentScope.value !== 'authenticated' ? currentScope.value : undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    })
}

const selectScope = (scope) => {
    currentScope.value = scope
    applyFilters()
}

const resetFilters = () => {
    search.value = ''
    currentScope.value = 'authenticated'
    applyFilters()
}

// Terminate Single Session
const terminateSession = (session) => {
    if (confirm(`Terminate session for ${session.user ? session.user.name : 'Guest'} (${session.ip_address})?`)) {
        router.delete(route('admin.administration.sessions.destroy', session.id), {
            preserveScroll: true,
            onSuccess: () => {
                feedbackMsg.value = 'Session terminated.'
                setTimeout(() => { feedbackMsg.value = '' }, 4000)
            }
        })
    }
}

// Terminate All Other Sessions
const terminateAllOther = () => {
    if (confirm('Revoke all active sessions on other devices? You will remain logged in on this current browser.')) {
        router.post(route('admin.administration.sessions.terminate-all-other'), {}, {
            preserveScroll: true,
            onSuccess: () => {
                feedbackMsg.value = 'All other active sessions revoked.'
                setTimeout(() => { feedbackMsg.value = '' }, 4000)
            }
        })
    }
}

const copyIp = (ip) => {
    navigator.clipboard.writeText(ip)
    feedbackMsg.value = 'IP address copied.'
    setTimeout(() => { feedbackMsg.value = '' }, 2500)
}
</script>

<template>
    <Head title="Active Sessions - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Administration', href: route('admin.administration.sessions') },
                    { label: 'Sessions' }
                ]"
            >
                <template #actions>
                    <Link
                        :href="route('admin.administration.administrators')"
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        <ShieldCheckIcon class="w-3.5 h-3.5 text-slate-500" />
                        <span>Administrators</span>
                    </Link>

                    <button 
                        type="button" 
                        @click="terminateAllOther"
                        class="px-2.5 py-1.5 bg-white hover:bg-rose-50 text-rose-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        <ArrowRightOnRectangleIcon class="w-3.5 h-3.5" />
                        <span>Revoke Other Sessions</span>
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
                    title="Active Sessions"
                    :value="String(stats.total_active_sessions || sessions.data?.length || 0)"
                    badge="Online"
                    badgeType="info"
                    color="blue"
                    :icon="ComputerDesktopIcon"
                />

                <InfoCard
                    title="Unique Operators"
                    :value="String(stats.unique_operators || 1)"
                    badge="Staff"
                    badgeType="success"
                    color="emerald"
                    :icon="UserGroupIcon"
                />

                <InfoCard
                    title="Static IP Sessions"
                    :value="String(stats.static_ip_sessions || 0)"
                    badge="Secured"
                    badgeType="info"
                    color="purple"
                    :icon="ShieldCheckIcon"
                />

                <InfoCard
                    title="Guest Sessions"
                    :value="String(stats.guest_sessions || 0)"
                    badge="Public"
                    badgeType="info"
                    color="sky"
                    :icon="GlobeAltIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Scope Tabs -->
            <div class="flex items-center justify-between border-b border-slate-200 pb-2">
                <div class="flex flex-wrap items-center gap-2">
                    <button
                        v-for="sc in ['all', 'authenticated', 'guest']"
                        :key="sc"
                        type="button"
                        @click="selectScope(sc)"
                        :class="currentScope === sc ? 'bg-blue-600 text-white' : 'bg-white text-slate-700 hover:bg-slate-50 border border-slate-200'"
                        class="px-3 py-1.5 rounded-[3px] font-bold text-xs uppercase font-mono transition cursor-pointer shadow-2xs"
                    >
                        {{ sc }}
                    </button>
                </div>
            </div>

            <!-- 4. Standard Filter Toolbar -->
            <DataTableFilter
                v-model="search"
                searchPlaceholder="Search operator name, email address, client IP..."
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
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Operator Account</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">IP Address</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">User Agent / Platform</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Last Activity</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Current Device</th>
                                <th class="py-2.5 px-3 w-14 text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                            <tr v-for="(s, index) in sessions.data" :key="s.id || index" class="hover:bg-blue-50/30 transition">
                                <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 font-mono text-[11px] whitespace-nowrap">
                                    {{ index + 1 }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-bold text-slate-900">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-xs border border-blue-100 shrink-0">
                                            <ComputerDesktopIcon class="w-3.5 h-3.5" />
                                        </div>
                                        <div>
                                            <span class="block leading-tight font-bold">{{ s.user?.name || s.user_name || 'Guest Operator' }}</span>
                                            <span class="text-[10px] text-slate-400 font-mono block">{{ s.user?.email || 'Unauthenticated' }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-mono text-[11px] text-slate-700">
                                    {{ s.ip_address || '127.0.0.1' }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-mono text-[11px] text-slate-600 max-w-sm truncate" :title="s.user_agent">
                                    {{ s.user_agent || 'Mozilla/5.0 Chrome/120' }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono text-[10.5px] text-slate-500">
                                    {{ s.last_activity ? new Date(s.last_activity * 1000).toLocaleTimeString() : 'Just Now' }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span 
                                        :class="s.is_current_device ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-500 border-slate-200'"
                                        class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase border font-mono"
                                    >
                                        {{ s.is_current_device ? 'This Device' : 'Remote Device' }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                    <RowActionDropdown>
                                        <button
                                            type="button"
                                            @click="copyIp(s.ip_address)"
                                            class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 font-medium cursor-pointer"
                                        >
                                            <DocumentDuplicateIcon class="w-3.5 h-3.5 text-slate-500" />
                                            <span>Copy IP</span>
                                        </button>

                                        <button
                                            type="button"
                                            @click="terminateSession(s)"
                                            class="w-full text-left px-3 py-1.5 text-xs text-rose-600 hover:bg-rose-50 flex items-center gap-2 font-medium cursor-pointer"
                                        >
                                            <ArrowRightOnRectangleIcon class="w-3.5 h-3.5" />
                                            <span>Terminate Session</span>
                                        </button>
                                    </RowActionDropdown>
                                </td>
                            </tr>

                            <tr v-if="!sessions.data || sessions.data.length === 0">
                                <td colspan="7" class="py-12 text-center text-slate-400 font-sans">
                                    No active user sessions found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
