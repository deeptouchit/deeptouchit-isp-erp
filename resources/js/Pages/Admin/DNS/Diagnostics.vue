<script setup>
import { ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import RefreshButton from '@/Components/UI/RefreshButton.vue'
import InfoCardsGrid from '@/Components/UI/InfoCardsGrid.vue'
import InfoCard from '@/Components/UI/InfoCard.vue'

import {
    ServerIcon,
    GlobeAltIcon,
    ArrowPathIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon,
    ShieldCheckIcon,
    ServerStackIcon,
    CommandLineIcon,
    DocumentDuplicateIcon,
    CheckIcon,
    SignalIcon,
    ArrowLeftIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    audit: {
        type: Object,
        default: () => ({ categories: [], score: 100, summary: '' }),
    },
    zones: {
        type: Array,
        default: () => [],
    },
    rdns: {
        type: Object,
        default: () => ({ ip: '103.59.177.138', hostname: 'No PTR', has_rdns: false }),
    },
    initialDig: {
        type: Object,
        default: () => ({ success: true, command: 'dig @127.0.0.1 deeptouchit.com A', output: '', latency_ms: 1 }),
    },
    stats: {
        type: Object,
        default: () => ({
            score: 100,
            target_domain: 'deeptouchit.com',
            server_ip: '103.59.177.138',
            port53_status: 'Listening (UDP/TCP 53)',
            total_zones: 0,
        }),
    },
    filters: {
        type: Object,
        default: () => ({ domain: 'deeptouchit.com' }),
    },
})

const selectedDomain = ref(props.filters.domain || (props.zones[0]?.domain || 'deeptouchit.com'))
const isAuditing = ref(false)
const feedbackMsg = ref('')

const triggerAudit = () => {
    if (!selectedDomain.value) return
    isAuditing.value = true
    router.get(
        route('admin.dns.diagnostics'),
        { domain: selectedDomain.value },
        {
            preserveState: true,
            preserveScroll: true,
            onFinish: () => { isAuditing.value = false }
        }
    )
}

// Interactive Web Dig State
const digDomain = ref(props.filters.domain || 'deeptouchit.com')
const digType = ref('A')
const digResolver = ref('127.0.0.1')
const customResolver = ref('')
const digLoading = ref(false)
const digResult = ref(props.initialDig)

const runWebDig = async () => {
    digLoading.value = true
    const resolver = digResolver.value === 'custom' ? customResolver.value : digResolver.value

    try {
        const res = await fetch(route('admin.dns.diagnostics.dig'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            body: JSON.stringify({
                domain: digDomain.value,
                type: digType.value,
                nameserver: resolver,
            })
        })
        const data = await res.json()
        digResult.value = data
    } catch (e) {
        digResult.value = {
            success: false,
            command: `dig @${resolver} ${digDomain.value} ${digType.value}`,
            output: '; Connection timed out or DNS server unreachable.'
        }
    } finally {
        digLoading.value = false
    }
}

const copyDigOutput = () => {
    navigator.clipboard.writeText(digResult.value?.output || '')
    feedbackMsg.value = 'Dig console output copied!'
    setTimeout(() => { feedbackMsg.value = '' }, 2500)
}
</script>

<template>
    <Head title="DNS Diagnostics & Dig Console - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Services', href: '#' },
                    { label: 'DNS Management', href: route('admin.dns.zones') },
                    { label: 'DNS Diagnostics & Live Dig' }
                ]"
            >
                <template #actions>
                    <Link
                        :href="route('admin.dns.zones')"
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        <ArrowLeftIcon class="w-3.5 h-3.5 text-slate-500" />
                        <span>DNS Zones</span>
                    </Link>

                    <!-- Domain Switcher -->
                    <div class="flex items-center gap-1.5 bg-white px-2.5 py-1.5 rounded-[3px] border border-slate-200 shadow-2xs text-xs">
                        <span class="font-bold text-slate-500">Domain:</span>
                        <select 
                            v-model="selectedDomain"
                            @change="triggerAudit"
                            class="bg-blue-50 border border-blue-200 text-xs font-bold text-blue-700 rounded-[2px] py-0.5 px-2 focus:ring-1 focus:ring-blue-500 cursor-pointer outline-none"
                        >
                            <option v-for="z in zones" :key="z.id" :value="z.domain">
                                {{ z.domain }}
                            </option>
                        </select>
                    </div>

                    <button
                        type="button"
                        @click="triggerAudit"
                        :disabled="isAuditing"
                        class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold flex items-center gap-1.5 shadow-2xs transition cursor-pointer disabled:opacity-50"
                    >
                        <ArrowPathIcon class="w-3.5 h-3.5" :class="{ 'animate-spin': isAuditing }" />
                        <span>Run Full Diagnostic</span>
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
                    title="DNS Health Score"
                    :value="`${audit.score || 100} / 100`"
                    :badge="audit.score >= 90 ? 'Optimal' : 'Needs Review'"
                    :badgeType="audit.score >= 90 ? 'success' : 'warning'"
                    color="emerald"
                    :icon="ShieldCheckIcon"
                />

                <InfoCard
                    title="Audited Target"
                    :value="selectedDomain"
                    badge="Active Zone"
                    badgeType="info"
                    color="blue"
                    :icon="GlobeAltIcon"
                />

                <InfoCard
                    title="Port 53 Listener"
                    :value="stats.port53_status || 'Active (53)'"
                    badge="UDP/TCP"
                    badgeType="success"
                    color="purple"
                    :icon="SignalIcon"
                />

                <InfoCard
                    title="Reverse DNS (rDNS)"
                    :value="rdns.has_rdns ? 'PTR Valid' : 'No PTR'"
                    :badge="rdns.has_rdns ? 'Match' : 'Unbound'"
                    :badgeType="rdns.has_rdns ? 'success' : 'warning'"
                    color="sky"
                    :icon="ServerIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Health Audit Summary Categories -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                <div v-for="(cat, ci) in (audit.categories || [])" :key="ci" class="bg-white rounded-[4px] border border-[#E2E8F0] p-4 shadow-2xs space-y-3">
                    <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                        <div class="flex items-center gap-2">
                            <span :class="cat.status === 'passed' ? 'text-emerald-600 bg-emerald-50 border-emerald-200' : 'text-amber-600 bg-amber-50 border-amber-200'" class="w-6 h-6 rounded-[3px] border flex items-center justify-center font-bold text-xs">
                                <CheckCircleIcon v-if="cat.status === 'passed'" class="w-4 h-4" />
                                <ExclamationTriangleIcon v-else class="w-4 h-4" />
                            </span>
                            <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">{{ cat.title }}</h3>
                        </div>
                        <span :class="cat.status === 'passed' ? 'text-emerald-700 font-bold' : 'text-amber-700 font-bold'" class="text-[11px] font-mono">
                            {{ cat.status === 'passed' ? 'PASS' : 'WARN' }}
                        </span>
                    </div>

                    <p class="text-xs text-slate-600">{{ cat.description }}</p>

                    <div v-if="cat.details && cat.details.length" class="bg-slate-50 p-2.5 rounded-[3px] border border-slate-200 space-y-1 font-mono text-[11px] text-slate-700">
                        <div v-for="(det, di) in cat.details" :key="di" class="flex items-start gap-2">
                            <span class="text-slate-400">•</span>
                            <span>{{ det }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 4. Interactive Live Dig Console -->
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] p-4 shadow-2xs space-y-3.5">
                <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold border border-blue-100">
                            <CommandLineIcon class="w-3.5 h-3.5" />
                        </div>
                        <div>
                            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900">Interactive Web Dig Console</h3>
                            <p class="text-[11px] text-slate-400">Query local BIND9 or upstream root resolvers in real time.</p>
                        </div>
                    </div>

                    <button
                        type="button"
                        @click="copyDigOutput"
                        class="px-2 py-1 bg-white hover:bg-slate-100 text-slate-700 font-bold rounded-[3px] border border-slate-200 text-xs transition flex items-center gap-1 cursor-pointer"
                    >
                        <DocumentDuplicateIcon class="w-3.5 h-3.5 text-slate-500" />
                        <span>Copy Output</span>
                    </button>
                </div>

                <form @submit.prevent="runWebDig" class="grid grid-cols-1 sm:grid-cols-4 gap-3 text-xs">
                    <div class="space-y-1 sm:col-span-2">
                        <label class="block font-bold text-slate-700">Target Host / FQDN</label>
                        <input v-model="digDomain" type="text" required placeholder="deeptouchit.com" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Record Type</label>
                        <select v-model="digType" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-bold focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer">
                            <option value="A">A</option>
                            <option value="AAAA">AAAA</option>
                            <option value="CNAME">CNAME</option>
                            <option value="MX">MX</option>
                            <option value="TXT">TXT</option>
                            <option value="NS">NS</option>
                            <option value="SOA">SOA</option>
                            <option value="ANY">ANY</option>
                        </select>
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Nameserver Resolver</label>
                        <select v-model="digResolver" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer">
                            <option value="127.0.0.1">Local BIND9 (127.0.0.1)</option>
                            <option value="8.8.8.8">Google DNS (8.8.8.8)</option>
                            <option value="1.1.1.1">Cloudflare (1.1.1.1)</option>
                            <option value="custom">Custom Resolver...</option>
                        </select>
                    </div>

                    <div v-if="digResolver === 'custom'" class="sm:col-span-3 space-y-1">
                        <label class="block font-bold text-slate-700">Custom Nameserver IP</label>
                        <input v-model="customResolver" type="text" placeholder="e.g. 9.9.9.9" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                    </div>

                    <div class="sm:col-span-1 flex items-end">
                        <button
                            type="submit"
                            :disabled="digLoading"
                            class="w-full py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-[3px] transition shadow-2xs cursor-pointer disabled:opacity-50 text-xs"
                        >
                            {{ digLoading ? 'Querying...' : 'Query Dig' }}
                        </button>
                    </div>
                </form>

                <!-- Console Output Display -->
                <div class="bg-slate-900 text-emerald-400 p-3.5 rounded-[3px] font-mono text-[11px] max-h-72 overflow-y-auto border border-slate-800 leading-relaxed whitespace-pre-wrap">
                    <p class="text-slate-500 font-bold mb-2">$ {{ digResult?.command || `dig @${digResolver} ${digDomain} ${digType}` }} ({{ digResult?.latency_ms || 1 }}ms)</p>
                    <div>{{ digResult?.output || '; No response recorded.' }}</div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
