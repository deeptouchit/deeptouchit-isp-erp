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
import axios from 'axios'

import {
    ArrowPathIcon,
    GlobeAltIcon,
    ServerIcon,
    SignalIcon,
    ArrowsRightLeftIcon,
    ShieldCheckIcon,
    CommandLineIcon,
    CheckIcon,
    PaperAirplaneIcon,
    UserGroupIcon,
    ArrowLeftIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    interfaces: {
        type: Array,
        default: () => [],
    },
    routes: {
        type: Array,
        default: () => [],
    },
    dns_servers: {
        type: Array,
        default: () => [],
    },
    listening_ports: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({
            active_interfaces: 0,
            total_interfaces: 0,
            public_ip: '103.59.177.138',
            default_gateway: '10.70.0.1',
            primary_device: 'enp4s0',
            listening_ports_count: 0,
        }),
    },
})

// Active Section Tab
const activeSection = ref('interfaces') // 'interfaces' | 'ports' | 'routes' | 'diagnostics'

// Diagnostics State
const pingTarget = ref('8.8.8.8')
const pingResult = ref(null)
const pingLoading = ref(false)

const dnsDomain = ref('google.com')
const dnsResult = ref(null)
const dnsLoading = ref(false)

const feedbackMsg = ref('')

// Run Ping
const runPing = async () => {
    if (!pingTarget.value) return
    pingLoading.value = true
    pingResult.value = null

    try {
        const res = await axios.post(route('admin.root-tools.network.ping'), {
            target: pingTarget.value,
        })
        pingResult.value = res.data
    } catch (err) {
        pingResult.value = {
            success: false,
            output: err.response?.data?.message || 'Ping target unreachable.',
        }
    } finally {
        pingLoading.value = false
    }
}

// Run DNS
const runDnsLookup = async () => {
    if (!dnsDomain.value) return
    dnsLoading.value = true
    dnsResult.value = null

    try {
        const res = await axios.post(route('admin.root-tools.network.dns-lookup'), {
            domain: dnsDomain.value,
        })
        dnsResult.value = res.data
    } catch (err) {
        dnsResult.value = {
            success: false,
            output: err.response?.data?.message || 'DNS resolution failed.',
        }
    } finally {
        dnsLoading.value = false
    }
}
</script>

<template>
    <Head title="Network & Sockets - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'System', href: '#' },
                    { label: 'Root Tools', href: route('admin.root-tools.terminal') },
                    { label: 'Network Interfaces & Socket Ports' }
                ]"
            >
                <template #actions>
                    <Link
                        :href="route('admin.root-tools.users')"
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        <UserGroupIcon class="w-3.5 h-3.5 text-slate-500" />
                        <span>Linux Users</span>
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
                    title="Active NICs"
                    :value="String(stats.active_interfaces || interfaces.length || 0)"
                    badge="Online"
                    badgeType="success"
                    color="blue"
                    :icon="SignalIcon"
                />

                <InfoCard
                    title="Public IP Node"
                    :value="stats.public_ip || '103.59.177.138'"
                    badge="WAN"
                    badgeType="info"
                    color="emerald"
                    :icon="GlobeAltIcon"
                />

                <InfoCard
                    title="Default Gateway"
                    :value="stats.default_gateway || '10.70.0.1'"
                    badge="Route"
                    badgeType="info"
                    color="purple"
                    :icon="ArrowsRightLeftIcon"
                />

                <InfoCard
                    title="Listening Sockets"
                    :value="String(stats.listening_ports_count || listening_ports.length || 0)"
                    badge="Ports"
                    badgeType="info"
                    color="sky"
                    :icon="ServerIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Section Tabs -->
            <div class="flex items-center justify-between border-b border-slate-200 pb-2">
                <div class="flex flex-wrap items-center gap-2">
                    <button
                        v-for="sec in [
                            { id: 'interfaces', label: 'Network Interfaces (NICs)' },
                            { id: 'ports', label: 'Listening Socket Ports' },
                            { id: 'routes', label: 'Routing Table' },
                            { id: 'diagnostics', label: 'ICMP & DNS Diagnostics' }
                        ]"
                        :key="sec.id"
                        type="button"
                        @click="activeSection = sec.id"
                        :class="activeSection === sec.id ? 'bg-blue-600 text-white' : 'bg-white text-slate-700 hover:bg-slate-50 border border-slate-200'"
                        class="px-3 py-1.5 rounded-[3px] font-bold text-xs uppercase font-mono transition cursor-pointer shadow-2xs"
                    >
                        {{ sec.label }}
                    </button>
                </div>
            </div>

            <!-- 4. SECTION 1: INTERFACES -->
            <div v-if="activeSection === 'interfaces'" class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-center border-collapse text-xs whitespace-nowrap">
                        <thead>
                            <tr class="bg-slate-50/90 border-b border-slate-200 text-[10.5px] font-bold uppercase tracking-wider text-slate-600 whitespace-nowrap">
                                <th class="py-2.5 px-3 border-r border-slate-200 w-12">#</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Interface Name</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">IPv4 Address & CIDR</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">MAC Hardware Address</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">MTU</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Link State</th>
                                <th class="py-2.5 px-3">RX / TX Traffic</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                            <tr v-for="(iface, index) in interfaces" :key="iface.name || index" class="hover:bg-blue-50/30 transition">
                                <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 font-mono text-[11px] whitespace-nowrap">
                                    {{ index + 1 }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-bold text-slate-900 font-mono">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-xs border border-blue-100 shrink-0">
                                            <SignalIcon class="w-3.5 h-3.5" />
                                        </div>
                                        <span>{{ iface.name }}</span>
                                    </div>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-mono text-[11px] text-slate-800">
                                    {{ iface.ip_address || iface.ipv4 || '127.0.0.1' }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-mono text-[11px] text-slate-500">
                                    {{ iface.mac_address || '00:00:00:00:00:00' }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono text-[11px]">
                                    {{ iface.mtu || 1500 }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span 
                                        :class="iface.is_up || iface.state === 'UP' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200'"
                                        class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase border font-mono"
                                    >
                                        {{ iface.is_up || iface.state === 'UP' ? 'UP' : 'DOWN' }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 text-center whitespace-nowrap font-mono text-[11px] text-slate-600">
                                    ↓ {{ iface.rx_bytes || '1.2 GB' }} / ↑ {{ iface.tx_bytes || '840 MB' }}
                                </td>
                            </tr>

                            <tr v-if="!interfaces || interfaces.length === 0">
                                <td colspan="7" class="py-12 text-center text-slate-400 font-sans">
                                    No network interfaces detected.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 5. SECTION 2: PORTS -->
            <div v-if="activeSection === 'ports'" class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-center border-collapse text-xs whitespace-nowrap">
                        <thead>
                            <tr class="bg-slate-50/90 border-b border-slate-200 text-[10.5px] font-bold uppercase tracking-wider text-slate-600 whitespace-nowrap">
                                <th class="py-2.5 px-3 border-r border-slate-200 w-12">#</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Protocol</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Port Number</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Bound Interface IP</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Listening Daemon / Program</th>
                                <th class="py-2.5 px-3">State</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                            <tr v-for="(p, index) in listening_ports" :key="index" class="hover:bg-blue-50/30 transition">
                                <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 font-mono text-[11px] whitespace-nowrap">
                                    {{ index + 1 }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono font-bold text-[11px] text-blue-700">
                                    {{ p.protocol || 'TCP' }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono font-bold text-[11px] text-slate-900">
                                    :{{ p.port }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-mono text-[11px] text-slate-700">
                                    {{ p.ip || '0.0.0.0' }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-bold text-slate-900">
                                    {{ p.process || p.daemon || 'systemd' }}
                                </td>
                                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                    <span class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase bg-emerald-50 text-emerald-700 border border-emerald-200 font-mono">
                                        LISTEN
                                    </span>
                                </td>
                            </tr>

                            <tr v-if="!listening_ports || listening_ports.length === 0">
                                <td colspan="6" class="py-12 text-center text-slate-400 font-sans">
                                    No listening socket ports discovered.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 6. SECTION 3: ROUTES -->
            <div v-if="activeSection === 'routes'" class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-center border-collapse text-xs whitespace-nowrap">
                        <thead>
                            <tr class="bg-slate-50/90 border-b border-slate-200 text-[10.5px] font-bold uppercase tracking-wider text-slate-600 whitespace-nowrap">
                                <th class="py-2.5 px-3 border-r border-slate-200 w-12">#</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Destination Subnet</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Gateway IP</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Device Interface</th>
                                <th class="py-2.5 px-3">Metric</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                            <tr v-for="(r, index) in routes" :key="index" class="hover:bg-blue-50/30 transition">
                                <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 font-mono text-[11px] whitespace-nowrap">
                                    {{ index + 1 }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-mono font-bold text-slate-900">
                                    {{ r.destination || 'default (0.0.0.0/0)' }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-mono text-[11px] text-slate-700">
                                    {{ r.gateway || '0.0.0.0' }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono text-[11px] text-slate-800">
                                    {{ r.interface || 'enp4s0' }}
                                </td>
                                <td class="py-2.5 px-3 text-center whitespace-nowrap font-mono text-[11px] text-slate-500">
                                    {{ r.metric || 100 }}
                                </td>
                            </tr>

                            <tr v-if="!routes || routes.length === 0">
                                <td colspan="5" class="py-12 text-center text-slate-400 font-sans">
                                    No IP routing table entries found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 7. SECTION 4: DIAGNOSTICS -->
            <div v-if="activeSection === 'diagnostics'" class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                <!-- Ping Tool -->
                <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs p-4 space-y-3">
                    <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide flex items-center gap-2">
                        <SignalIcon class="w-4 h-4 text-blue-600" />
                        ICMP Ping Diagnostic
                    </h3>
                    <div class="flex items-center gap-2">
                        <input v-model="pingTarget" type="text" placeholder="8.8.8.8 or example.com" class="flex-1 px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                        <button
                            type="button"
                            @click="runPing"
                            :disabled="pingLoading || !pingTarget"
                            class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-[3px] text-xs transition shadow-2xs cursor-pointer disabled:opacity-50"
                        >
                            {{ pingLoading ? 'Pinging...' : 'Ping' }}
                        </button>
                    </div>
                    <pre v-if="pingResult" class="bg-slate-950 text-emerald-400 p-3 rounded-[3px] font-mono text-[11px] max-h-48 overflow-y-auto whitespace-pre-wrap">{{ pingResult.output }}</pre>
                </div>

                <!-- DNS Tool -->
                <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs p-4 space-y-3">
                    <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide flex items-center gap-2">
                        <GlobeAltIcon class="w-4 h-4 text-blue-600" />
                        DNS Dig Lookup Diagnostic
                    </h3>
                    <div class="flex items-center gap-2">
                        <input v-model="dnsDomain" type="text" placeholder="example.com" class="flex-1 px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                        <button
                            type="button"
                            @click="runDnsLookup"
                            :disabled="dnsLoading || !dnsDomain"
                            class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-[3px] text-xs transition shadow-2xs cursor-pointer disabled:opacity-50"
                        >
                            {{ dnsLoading ? 'Resolving...' : 'Lookup' }}
                        </button>
                    </div>
                    <pre v-if="dnsResult" class="bg-slate-950 text-emerald-400 p-3 rounded-[3px] font-mono text-[11px] max-h-48 overflow-y-auto whitespace-pre-wrap">{{ dnsResult.output }}</pre>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
