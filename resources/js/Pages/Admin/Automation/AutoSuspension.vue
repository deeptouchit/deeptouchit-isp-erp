<script setup>
import { ref, computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import RefreshButton from '@/Components/UI/RefreshButton.vue'
import InfoCardsGrid from '@/Components/UI/InfoCardsGrid.vue'
import InfoCard from '@/Components/UI/InfoCard.vue'
import DataTableFilter from '@/Components/UI/DataTableFilter.vue'
import RowActionDropdown from '@/Components/UI/RowActionDropdown.vue'

import {
    NoSymbolIcon,
    ClockIcon,
    CheckIcon,
    ExclamationTriangleIcon,
    ArrowPathIcon,
    BoltIcon,
    GlobeAltIcon,
    UserIcon,
    CurrencyDollarIcon,
    CalendarDaysIcon,
    ArrowLeftIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    stats: {
        type: Object,
        default: () => ({}),
    },
    subscriptions: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
})

// Search & Feedback State
const search = ref('')
const feedbackMsg = ref('')

const filteredSubs = computed(() => {
    let list = props.subscriptions || []
    if (search.value.trim()) {
        const q = search.value.toLowerCase()
        list = list.filter(s =>
            (s.domain || '').toLowerCase().includes(q) ||
            (s.username || '').toLowerCase().includes(q) ||
            (s.suspension_reason || '').toLowerCase().includes(q)
        )
    }
    return list
})

const runSuspensionCheck = () => {
    router.post(route('admin.automation.auto-suspension.run'), {}, {
        preserveScroll: true,
        onSuccess: () => {
            feedbackMsg.value = 'Auto-suspension policy evaluation executed.'
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

const unsuspendAccount = (sub) => {
    router.post(route('admin.hosting.unsuspend', sub.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            feedbackMsg.value = `Account '${sub.domain}' unsuspended successfully.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}
</script>

<template>
    <Head title="Auto Suspension Policies - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Automation', href: '#' },
                    { label: 'Automation & Schedulers', href: route('admin.automation.cron') },
                    { label: 'Auto-Suspension Policy Engine' }
                ]"
            >
                <template #actions>
                    <Link
                        :href="route('admin.customers.suspended')"
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        <ArrowLeftIcon class="w-3.5 h-3.5 text-slate-500" />
                        <span>Suspended Customers</span>
                    </Link>

                    <button
                        type="button"
                        @click="runSuspensionCheck"
                        class="px-3 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-[3px] text-xs font-bold flex items-center gap-1.5 shadow-2xs transition cursor-pointer"
                    >
                        <NoSymbolIcon class="w-3.5 h-3.5" />
                        <span>Evaluate Overdue Accounts</span>
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
                    title="Suspended Accounts"
                    :value="String(subscriptions.length || stats.suspended_count || 0)"
                    :badge="subscriptions.length > 0 ? 'Suspended' : 'Clean'"
                    :badgeType="subscriptions.length > 0 ? 'danger' : 'success'"
                    color="rose"
                    :icon="NoSymbolIcon"
                />

                <InfoCard
                    title="Overdue Invoice Grace"
                    :value="`${stats.grace_days || 3} Days Grace`"
                    badge="Billing Policy"
                    badgeType="info"
                    color="blue"
                    :icon="CalendarDaysIcon"
                />

                <InfoCard
                    title="Quota Overuse Trigger"
                    value="Enforced (100%)"
                    badge="Disk/Traffic"
                    badgeType="warning"
                    color="purple"
                    :icon="ExclamationTriangleIcon"
                />

                <InfoCard
                    title="Evaluation Daemon"
                    value="Midnight Cron (00:00)"
                    badge="Active"
                    badgeType="success"
                    color="sky"
                    :icon="ClockIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Standard Filter Toolbar -->
            <DataTableFilter
                v-model="search"
                searchPlaceholder="Search domain name, username, or suspension reason..."
                @search="() => {}"
                @filter="() => {}"
                @reset="search = ''"
            />

            <!-- 4. Standard Data Table -->
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-center border-collapse text-xs whitespace-nowrap">
                        <thead>
                            <tr class="bg-slate-50/90 border-b border-slate-200 text-[10.5px] font-bold uppercase tracking-wider text-slate-600 whitespace-nowrap">
                                <th class="py-2.5 px-3 border-r border-slate-200 w-12">#</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Suspended Account Domain</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Customer Username</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Reason for Suspension</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Suspended Date</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Overdue Days</th>
                                <th class="py-2.5 px-3 w-28">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                            <tr v-for="(s, index) in filteredSubs" :key="s.id || index" class="hover:bg-rose-50/20 transition">
                                <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 font-mono text-[11px] whitespace-nowrap">
                                    {{ index + 1 }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-mono font-bold text-slate-900">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-[3px] bg-rose-50 text-rose-600 flex items-center justify-center font-bold text-xs border border-rose-100 shrink-0">
                                            <NoSymbolIcon class="w-3.5 h-3.5" />
                                        </div>
                                        <span>{{ s.domain }}</span>
                                    </div>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-mono text-[11px] text-slate-600">
                                    @{{ s.username }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap text-rose-700 font-medium max-w-sm truncate" :title="s.suspension_reason">
                                    {{ s.suspension_reason || 'Unpaid Overdue Invoice (Over 3 Days)' }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono text-[10.5px] text-slate-500">
                                    {{ s.suspended_at ? new Date(s.suspended_at).toLocaleDateString() : 'Recent' }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono text-[11px] font-bold text-slate-800">
                                    {{ s.overdue_days || 4 }} Days
                                </td>
                                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                    <button 
                                        type="button" 
                                        @click="unsuspendAccount(s)"
                                        class="px-2 py-1 bg-white hover:bg-slate-50 text-emerald-700 font-bold rounded-[3px] text-xs border border-slate-200 shadow-2xs transition cursor-pointer"
                                        title="Unsuspend"
                                    >
                                        Unsuspend 🔓
                                    </button>
                                </td>
                            </tr>

                            <tr v-if="!filteredSubs || filteredSubs.length === 0">
                                <td colspan="7" class="py-12 text-center text-slate-400 font-sans">
                                    No accounts currently suspended by automation policies.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
