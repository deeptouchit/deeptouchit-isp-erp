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
    ShieldCheckIcon,
    LockClosedIcon,
    ArrowPathIcon,
    CheckIcon,
    BoltIcon,
    GlobeAltIcon,
    ClockIcon,
    ServerIcon,
    ArrowLeftIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    stats: {
        type: Object,
        default: () => ({}),
    },
    certificates: {
        type: Array,
        default: () => [],
    },
    hostedDomains: {
        type: Array,
        default: () => [],
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

const filteredCerts = computed(() => {
    let list = props.certificates || []
    if (search.value.trim()) {
        const q = search.value.toLowerCase()
        list = list.filter(c =>
            (c.domain || '').toLowerCase().includes(q) ||
            (c.issuer || '').toLowerCase().includes(q)
        )
    }
    return list
})

const runAutoRenewAll = () => {
    router.post(route('admin.automation.auto-ssl.run-all'), {}, {
        preserveScroll: true,
        onSuccess: () => {
            feedbackMsg.value = 'AutoSSL cron run triggered for all active domains.'
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

const reissueSingle = (cert) => {
    router.post(route('admin.security.ssl.renew', cert.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            feedbackMsg.value = `AutoSSL renewed for '${cert.domain}'.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}
</script>

<template>
    <Head title="AutoSSL Automation Engine - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Automation', href: '#' },
                    { label: 'Automation & Schedulers', href: route('admin.automation.cron') },
                    { label: 'AutoSSL Automation & Renewal Engine' }
                ]"
            >
                <template #actions>
                    <Link
                        :href="route('admin.security.ssl')"
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        <ArrowLeftIcon class="w-3.5 h-3.5 text-slate-500" />
                        <span>SSL Certificates</span>
                    </Link>

                    <button
                        type="button"
                        @click="runAutoRenewAll"
                        class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold flex items-center gap-1.5 shadow-2xs transition cursor-pointer"
                    >
                        <BoltIcon class="w-3.5 h-3.5" />
                        <span>Run AutoSSL Check Now</span>
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
                    title="Managed AutoSSL Domains"
                    :value="String(certificates.length || stats.total_managed || 0)"
                    badge="ACME"
                    badgeType="info"
                    color="blue"
                    :icon="GlobeAltIcon"
                />

                <InfoCard
                    title="Secured & Valid"
                    :value="String(certificates.filter(c => c.status === 'active').length || stats.active_count || 0)"
                    badge="TLS 1.3"
                    badgeType="success"
                    color="emerald"
                    :icon="LockClosedIcon"
                />

                <InfoCard
                    title="Expiring in <30 Days"
                    :value="String(stats.expiring_soon || 0)"
                    :badge="stats.expiring_soon > 0 ? 'Queued' : 'All Valid'"
                    :badgeType="stats.expiring_soon > 0 ? 'warning' : 'success'"
                    color="purple"
                    :icon="ClockIcon"
                />

                <InfoCard
                    title="ACME Renewal Engine"
                    value="Cron Daemon Active"
                    badge="Optimal"
                    badgeType="success"
                    color="sky"
                    :icon="ShieldCheckIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Standard Filter Toolbar -->
            <DataTableFilter
                v-model="search"
                searchPlaceholder="Search domain name, ACME issuer, or status..."
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
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Target Domain (FQDN)</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">ACME Certificate Provider</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Auto-Renew Policy</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Expiry Date</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Status</th>
                                <th class="py-2.5 px-3 w-28">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                            <tr v-for="(cert, index) in filteredCerts" :key="cert.id || index" class="hover:bg-blue-50/30 transition">
                                <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 font-mono text-[11px] whitespace-nowrap">
                                    {{ index + 1 }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-mono font-bold text-slate-900">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-[3px] bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-xs border border-emerald-100 shrink-0">
                                            <LockClosedIcon class="w-3.5 h-3.5" />
                                        </div>
                                        <span>{{ cert.domain }}</span>
                                    </div>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-mono text-[11px] text-slate-600">
                                    {{ cert.issuer || "Let's Encrypt Authority" }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase bg-emerald-50 text-emerald-700 border border-emerald-200 font-mono">
                                        Enabled (Automatic)
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono text-[10.5px] text-slate-500">
                                    {{ cert.valid_to ? new Date(cert.valid_to).toLocaleDateString() : '90 Days' }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span 
                                        :class="cert.status === 'active' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-amber-50 text-amber-700 border-amber-200'"
                                        class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase border font-mono"
                                    >
                                        {{ cert.status || 'active' }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                    <button 
                                        type="button" 
                                        @click="reissueSingle(cert)"
                                        class="px-2 py-1 bg-white hover:bg-slate-50 text-blue-700 font-bold rounded-[3px] text-xs border border-slate-200 shadow-2xs transition cursor-pointer"
                                        title="Renew Now"
                                    >
                                        Renew ⚡
                                    </button>
                                </td>
                            </tr>

                            <tr v-if="!filteredCerts || filteredCerts.length === 0">
                                <td colspan="7" class="py-12 text-center text-slate-400 font-sans">
                                    No AutoSSL certificates found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
