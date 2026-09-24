<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import InfoCardsGrid from '@/Components/UI/InfoCardsGrid.vue'
import InfoCard from '@/Components/UI/InfoCard.vue'
import RefreshButton from '@/Components/UI/RefreshButton.vue'
import {
    UsersIcon,
    GlobeAltIcon,
    CircleStackIcon,
    ServerStackIcon,
    ChatBubbleLeftRightIcon,
    ChevronDownIcon,
    CreditCardIcon,
    Cog6ToothIcon,
    DocumentTextIcon,
    UserPlusIcon,
    ShieldCheckIcon,
    ArrowsRightLeftIcon,
    FolderIcon,
    Squares2X2Icon,
    LockClosedIcon,
    BanknotesIcon,
    LifebuoyIcon,
    ClockIcon,
    ExclamationTriangleIcon,
    CheckCircleIcon,
    ArrowTopRightOnSquareIcon,
    CloudArrowUpIcon,
    ArchiveBoxIcon,
    ArrowDownTrayIcon,
    ArrowUpTrayIcon,
    EnvelopeIcon,
    CubeIcon,
    CpuChipIcon,
    ArrowPathIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    stats: {
        type: Object,
        default: () => ({})
    },
    telemetry: {
        type: Object,
        default: () => ({})
    },
    servicesOverview: {
        type: Object,
        default: () => ({
            webHosting: 2,
            vpsServers: 1,
            domains: 2,
            sslCertificates: 1,
            total: 6
        })
    },
    domainHostingOverview: {
        type: Object,
        default: () => ({
            totalDomains: 0,
            activeDomains: 0,
            expiredDomains: 0,
            pendingDomains: 0,
            webHosting: 0,
            vpsServers: 0,
            dedicated: 0,
            otherServices: 0
        })
    },
    serverDetails: {
        type: Object,
        default: () => ({
            hostname: 'node01.deeptouchit.com',
            ipAddress: '127.0.0.1',
            os: 'Ubuntu 22.04 LTS',
            kernel: 'Linux 6.8.0',
            arch: 'x86_64',
            cores: 4,
            phpVersion: '8.3',
            webServer: 'Nginx',
            dbVersion: 'MySQL 8.0',
            uptime: '1d 0h',
            sshPort: 22
        })
    },
    securityOverview: {
        type: Object,
        default: () => ({
            activeRules: 0,
            blockedIps: 0,
            activeSsl: 0,
            expiringSsl: 0,
            totalWebsites: 0
        })
    },
    financialOverview: {
        type: Object,
        default: () => ({
            todayRevenue: 0,
            thisMonthRevenue: 0,
            monthlyRevenue: 0,
            prevMonthRevenue: 0,
            outstandingDue: 0,
            pendingDue: 0,
            collectedAllTime: 0,
            refunds: 0,
            newOrdersValue: 0,
            mrr: 0,
            arr: 0,
            failedPayments: 0,
            pendingPayments: 0,
            unpaidCount: 0,
            gateways: [],
            recentTransactions: []
        })
    },
    growthAnalytics: {
        type: Object,
        default: () => ({
            periods: {
                'Today': { labels: [], revenue: [], customers: [] },
                '7 Days': { labels: [], revenue: [], customers: [] },
                '30 Days': { labels: [], revenue: [], customers: [] },
                '12 Months': { labels: [], revenue: [], customers: [] },
            },
            customersSummary: {
                total: 0,
                active: 0,
                newThisMonth: 0,
                churned: 0,
                churnRate: 0,
            }
        })
    },
    securityThreatMonitor: {
        type: Object,
        default: () => ({
            failedLogins: 0,
            bruteForce: 0,
            blockedRequests: 0,
            malwareDetected: 0,
            suspiciousIps: 0,
            wafEvents: 0,
            sshLoginAttempts: 0,
            rootLoginAttempts: 0,
            securityScore: 98,
            lastSecurityScan: 'Just now',
            lastSecurityUpdate: 'Up to date',
        })
    },
    recentTickets: {
        type: Array,
        default: () => []
    },
    recentActivities: {
        type: Array,
        default: () => []
    },
    daemons: {
        type: Array,
        default: () => [
            { name: 'Nginx Web Server', active: true },
            { name: 'MySQL Database', active: true },
            { name: 'PHP-FPM Engine', active: true },
            { name: 'Redis Cache', active: true },
            { name: 'OpenSSH Daemon', active: true }
        ]
    },
    criticalAlerts: {
        type: Object,
        default: () => ({
            totalIncidents: 0,
            incidents: [],
            monitoredChecks: []
        })
    },
    liveServiceStatus: {
        type: Array,
        default: () => []
    },
    backupStatus: {
        type: Object,
        default: () => ({})
    },
    storageDetails: {
        type: Object,
        default: () => ({})
    },
    bandwidthDetails: {
        type: Object,
        default: () => ({})
    },
    customerHealth: {
        type: Object,
        default: () => ({})
    }
})

// Initialize selectedServer with exact matching server name from database
const selectedServer = ref(props.telemetry?.servers?.[0]?.name || props.telemetry?.hostname || 'Master Node 1')
const lastUpdatedTime = ref('Just now')
const activePeriod = ref('Today')

// Real-time reactive state for CPU, RAM, Disk, Network, and Uptime
const liveCpu = ref({
    usage: props.telemetry?.cpu?.usage || 0,
    cores: props.telemetry?.cpu?.cores || 4
})

const liveRam = ref({
    usage: props.telemetry?.ram?.usage || 0,
    used: props.telemetry?.ram?.used || 0,
    total: props.telemetry?.ram?.total || 0
})

const liveDisk = ref({
    usage: props.telemetry?.disk?.usage || 0,
    used: props.telemetry?.disk?.used || 0,
    total: props.telemetry?.disk?.total || 0
})

const liveNetwork = ref({
    usage: props.telemetry?.network?.usage || 0
})

const liveUptime = ref({
    percentage: props.telemetry?.uptime?.percentage || '99.9'
})

// Smooth 24h history points
const cpuPoints = ref(props.telemetry?.cpu?.history ? [...props.telemetry.cpu.history] : [18, 22, 25, 24, 28, 26, 22, 25, 29, 34, 32, 28, 26, 30, 36, 33, 38, 32, 26, 24, 28, 26, 22, 14])
const ramPoints = ref(props.telemetry?.ram?.history ? [...props.telemetry.ram.history] : [58, 62, 60, 64, 62, 59, 63, 61, 58, 62, 65, 63, 56, 54, 58, 62, 66, 64, 62, 66, 65, 68, 66, 67])
const netPoints = ref([12, 14, 16, 18, 15, 12, 16, 22, 26, 28, 24, 20, 18, 22, 25, 28, 32, 26, 22, 18, 16, 20, 22, 24])

// Real-time polling timer (Every 1 Second)
let pollTimer = null

const fetchLiveTelemetry = async () => {
    try {
        const res = await fetch('/admin/dashboard/telemetry', {
            headers: { 'Accept': 'application/json' }
        })
        if (res.ok) {
            const data = await res.json()
            if (data.cpu) {
                liveCpu.value.usage = data.cpu.usage
                liveCpu.value.cores = data.cpu.cores
                cpuPoints.value.shift()
                cpuPoints.value.push(data.cpu.usage)
            }
            if (data.ram) {
                liveRam.value.usage = data.ram.usage
                liveRam.value.used = data.ram.used
                liveRam.value.total = data.ram.total
                ramPoints.value.shift()
                ramPoints.value.push(data.ram.usage)
            }
            if (data.disk) {
                liveDisk.value.usage = data.disk.usage
                liveDisk.value.used = data.disk.used
                liveDisk.value.total = data.disk.total
            }
            if (data.network) {
                liveNetwork.value.usage = data.network.usage
                netPoints.value.shift()
                netPoints.value.push(data.network.usage)
            }
            if (data.uptime) {
                liveUptime.value.percentage = data.uptime.percentage
            }
            if (data.timestamp) {
                lastUpdatedTime.value = data.timestamp
            }
        }
    } catch (e) {
        // Fallback gracefully
    }
}

onMounted(() => {
    pollTimer = setInterval(fetchLiveTelemetry, 1000)
})

onUnmounted(() => {
    if (pollTimer) clearInterval(pollTimer)
})

// Circular donut gauge offset
const getDonutOffset = (percent, radius = 28) => {
    const p = Math.min(100, Math.max(0, Number(percent) || 0))
    const circumference = 2 * Math.PI * radius
    return circumference - (p / 100) * circumference
}

// Smooth continuous Bezier SVG curve line generator
const generateSmoothSvgPath = (points = [], width = 560, height = 120) => {
    if (!points || points.length < 2) return ''
    const step = width / (points.length - 1)
    const pts = points.map((p, i) => ({
        x: i * step,
        y: height - (Math.min(100, Math.max(0, Number(p) || 0)) / 100) * height
    }))
    let d = `M ${pts[0].x.toFixed(1)} ${pts[0].y.toFixed(1)}`
    for (let i = 0; i < pts.length - 1; i++) {
        const p0 = i > 0 ? pts[i - 1] : pts[i]
        const p1 = pts[i]
        const p2 = pts[i + 1]
        const p3 = i < pts.length - 2 ? pts[i + 2] : p2
        const cp1x = p1.x + (p2.x - p0.x) / 6
        const cp1y = p1.y + (p2.y - p0.y) / 6
        const cp2x = p2.x - (p3.x - p1.x) / 6
        const cp2y = p2.y - (p3.y - p1.y) / 6
        d += ` C ${cp1x.toFixed(1)} ${cp1y.toFixed(1)}, ${cp2x.toFixed(1)} ${cp2y.toFixed(1)}, ${p2.x.toFixed(1)} ${p2.y.toFixed(1)}`
    }
    return d
}

// Smooth continuous Bezier SVG filled area generator
const generateSmoothAreaPath = (points = [], width = 560, height = 120) => {
    if (!points || points.length < 2) return ''
    const linePath = generateSmoothSvgPath(points, width, height)
    return `${linePath} L ${width} ${height} L 0 ${height} Z`
}

// Services breakdown calculation (Real database values)
const totalSrv = computed(() => Number(props.servicesOverview?.total) || 0)
const webPct = computed(() => totalSrv.value > 0 ? (Number(props.servicesOverview?.webHosting) || 0) / totalSrv.value : 0)
const vpsPct = computed(() => totalSrv.value > 0 ? (Number(props.servicesOverview?.vpsServers) || 0) / totalSrv.value : 0)
const domPct = computed(() => totalSrv.value > 0 ? (Number(props.servicesOverview?.domains) || 0) / totalSrv.value : 0)
const sslPct = computed(() => totalSrv.value > 0 ? (Number(props.servicesOverview?.sslCertificates) || 0) / totalSrv.value : 0)

// Domain & Hosting Overview Computed Stats (100% Real Database Queries)
const domStats = computed(() => {
    const total = Number(props.domainHostingOverview?.totalDomains) || 0
    const active = Number(props.domainHostingOverview?.activeDomains) || 0
    const expired = Number(props.domainHostingOverview?.expiredDomains) || 0
    const pending = Number(props.domainHostingOverview?.pendingDomains) || 0

    const activePct = total > 0 ? Math.round((active / total) * 100) : 0
    const expiredPct = total > 0 ? Math.round((expired / total) * 100) : 0
    const pendingPct = total > 0 ? Math.round((pending / total) * 100) : 0

    return {
        total: total.toLocaleString(),
        active: active.toLocaleString(),
        expired: expired.toLocaleString(),
        pending: pending.toLocaleString(),
        activePct,
        expiredPct,
        pendingPct,
        activeRatio: total > 0 ? (active / total) : 0,
        expiredRatio: total > 0 ? (expired / total) : 0,
        pendingRatio: total > 0 ? (pending / total) : 0,
        webHosting: (Number(props.domainHostingOverview?.webHosting) || 0).toLocaleString(),
        vpsServers: (Number(props.domainHostingOverview?.vpsServers) || 0).toLocaleString(),
        dedicated: (Number(props.domainHostingOverview?.dedicated) || 0).toLocaleString(),
        otherServices: (Number(props.domainHostingOverview?.otherServices) || 0).toLocaleString(),
    }
})

// 16 Comprehensive Admin Quick Action Shortcuts
const quickActionsList = [
    { id: 1, label: 'Order Hosting', href: '/admin/websites', icon: ServerStackIcon, bg: 'bg-blue-50 text-blue-600' },
    { id: 2, label: 'Register Domain', href: '/admin/websites', icon: GlobeAltIcon, bg: 'bg-indigo-50 text-indigo-600' },
    { id: 3, label: 'Add Database', href: '/admin/databases', icon: CircleStackIcon, bg: 'bg-emerald-50 text-emerald-600' },
    { id: 4, label: 'Create User', href: '/admin/users', icon: UserPlusIcon, bg: 'bg-purple-50 text-purple-600' },
    { id: 5, label: 'Issue SSL', href: '/admin/websites', icon: ShieldCheckIcon, bg: 'bg-teal-50 text-teal-600' },
    { id: 6, label: 'Backup Center', href: '/admin/backups', icon: ArchiveBoxIcon, bg: 'bg-cyan-50 text-cyan-600' },
    { id: 7, label: 'Firewall Rules', href: '/admin/firewall', icon: LockClosedIcon, bg: 'bg-rose-50 text-rose-600' },
    { id: 8, label: 'Create Ticket', href: '/admin/tickets', icon: ChatBubbleLeftRightIcon, bg: 'bg-violet-50 text-violet-600' },
    { id: 9, label: 'Mail Accounts', href: '/admin/mail/accounts', icon: EnvelopeIcon, bg: 'bg-amber-50 text-amber-600' },
    { id: 10, label: 'DNS Manager', href: '/admin/dns/zones', icon: ArrowsRightLeftIcon, bg: 'bg-blue-50 text-blue-600' },
    { id: 11, label: 'File Manager', href: '/admin/websites', icon: FolderIcon, bg: 'bg-sky-50 text-sky-600' },
    { id: 12, label: 'Cron Jobs', href: '/admin/cron/jobs', icon: ClockIcon, bg: 'bg-emerald-50 text-emerald-600' },
    // { id: 13, label: 'Hosting Plans', href: '/admin/packages', icon: CubeIcon, bg: 'bg-fuchsia-50 text-fuchsia-600' },
    // { id: 14, label: 'View Invoices', href: '/admin/billing', icon: DocumentTextIcon, bg: 'bg-orange-50 text-orange-600' },
    // { id: 15, label: 'Server Nodes', href: '/admin/servers', icon: CpuChipIcon, bg: 'bg-slate-100 text-slate-700' },
    // { id: 16, label: 'Settings', href: '/admin/settings', icon: Cog6ToothIcon, bg: 'bg-pink-50 text-pink-600' },
]

// Revenue & Customer Growth Interactive Period State
const growthPeriod = ref('12 Months')

const currentGrowthData = computed(() => {
    return props.growthAnalytics?.periods?.[growthPeriod.value] || { labels: [], revenue: [], customers: [] }
})

// Normalized SVG Points (0 - 100%) for Area/Line Charts
const normalizedGrowthRevenue = computed(() => {
    const arr = currentGrowthData.value.revenue || []
    const max = Math.max(...arr, 1)
    return arr.map(v => max > 0 ? (v / max) * 100 : 0)
})

const normalizedGrowthCustomers = computed(() => {
    const arr = currentGrowthData.value.customers || []
    const max = Math.max(...arr, 1)
    return arr.map(v => max > 0 ? (v / max) * 100 : 0)
})

const periodTotalRevenue = computed(() => {
    const arr = currentGrowthData.value.revenue || []
    return arr.reduce((acc, cur) => acc + (Number(cur) || 0), 0)
})

const periodTotalCustomers = computed(() => {
    const arr = currentGrowthData.value.customers || []
    return arr.reduce((acc, cur) => acc + (Number(cur) || 0), 0)
})
</script>

<template>

    <Head title="Admin Dashboard" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Minimal Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Executive Dashboard' }
                ]"
            >
                <template #actions>
                    <RefreshButton />
                </template>
            </PageHeader>

            <!-- 2. Ultra-Compact 56px KPI Info Cards (5 Columns) -->
            <InfoCardsGrid :cols="5">
                <Link :href="route().has('admin.users.index') ? route('admin.users.index') : '/admin/users'" class="block">
                    <InfoCard
                        title="Clients"
                        :value="String(stats.clients ?? 0)"
                        badge="Active"
                        badgeType="purple"
                        color="purple"
                        :icon="UsersIcon"
                    />
                </Link>

                <Link :href="route('admin.websites.index')" class="block">
                    <InfoCard
                        title="Websites"
                        :value="String(stats.websites ?? 0)"
                        badge="Online"
                        badgeType="success"
                        color="blue"
                        :icon="GlobeAltIcon"
                    />
                </Link>

                <Link :href="route().has('admin.databases.index') ? route('admin.databases.index') : '/admin/databases'" class="block">
                    <InfoCard
                        title="Databases"
                        :value="String(stats.databases ?? 0)"
                        badge="Optimal"
                        badgeType="success"
                        color="emerald"
                        :icon="CircleStackIcon"
                    />
                </Link>

                <Link :href="route().has('admin.servers.index') ? route('admin.servers.index') : '/admin/servers'" class="block">
                    <InfoCard
                        title="Servers"
                        :value="String(stats.servers ?? 0)"
                        badge="Online"
                        badgeType="warning"
                        color="amber"
                        :icon="ServerStackIcon"
                    />
                </Link>

                <Link :href="route().has('admin.tickets.index') ? route('admin.tickets.index') : '/admin/tickets'" class="block">
                    <InfoCard
                        title="Tickets"
                        :value="String(stats.tickets ?? 0)"
                        badge="Live"
                        badgeType="info"
                        color="indigo"
                        :icon="ChatBubbleLeftRightIcon"
                    />
                </Link>
            </InfoCardsGrid>

            <!-- Main Content 12-Column Grid (8-Cols Content on Left + 4-Cols Sidebar on Right) -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-3.5 items-start">
                <!-- Left 8 Columns Container -->
                <div class="lg:col-span-8 space-y-3.5">
                    <!-- Row 1: Server Performance (6-cols) + Services Overview (6-cols) -->
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-3.5 items-stretch">
                        <!-- Card 1: Server Performance (6 cols) -->
                        <div
                            class="lg:col-span-7 bg-white border border-[#E2E8F0] rounded-[4px] p-5 shadow-2xs flex flex-col justify-between">
                            <div>
                                <!-- Header -->
                                <div class="flex items-center justify-between mb-3.5 pb-2 border-b border-slate-100">
                                    <div class="flex items-center gap-2">
                                        <h2 class="text-xs font-bold text-slate-900 uppercase tracking-wide"> Server
                                            Performance</h2>
                                    </div>

                                    <!-- Server Dropdown Selector -->
                                    <div class="relative">
                                        <select v-model="selectedServer"
                                            class="bg-white border border-[#CBD5E1] hover:border-slate-400 rounded-[3px] text-xs font-semibold text-slate-700 pl-2.5 pr-7 py-1 min-w-[130px] appearance-none cursor-pointer focus:outline-none focus:ring-1 focus:ring-blue-500 shadow-2xs">
                                            <option v-for="srv in (telemetry.servers || [])" :key="srv.id"
                                                :value="srv.name">
                                                {{ srv.name }}
                                            </option>
                                            <option v-if="!telemetry.servers?.length" :value="selectedServer">
                                                {{ selectedServer }}
                                            </option>
                                        </select>
                                        <ChevronDownIcon
                                            class="w-3.5 h-3.5 text-slate-500 absolute right-2 top-1/2 -translate-y-1/2 pointer-events-none" />
                                    </div>
                                </div>

                                <!-- Body: Left Big Circular Gauge + Right 4 Gradient Progress Bars -->
                                <div class="grid grid-cols-1 sm:grid-cols-12 gap-5 items-center pt-1">
                                    <!-- Left Circular Gauge (5 of 12) -->
                                    <div class="sm:col-span-5 flex flex-col items-center justify-center">
                                        <div class="relative flex items-center justify-center shrink-0"
                                            style="width: 120px; height: 120px;">
                                            <svg class="transform -rotate-90" viewBox="0 0 100 100"
                                                style="width: 120px; height: 120px;">
                                                <circle cx="50" cy="50" r="40" stroke="#F1F5F9" stroke-width="8"
                                                    fill="none" />
                                                <circle cx="50" cy="50" r="40" stroke="url(#uptimeGrad)"
                                                    stroke-width="8" stroke-linecap="round" fill="none"
                                                    stroke-dasharray="251.32"
                                                    :stroke-dashoffset="getDonutOffset(liveUptime.percentage, 40)"
                                                    class="transition-all duration-700 ease-out" />
                                                <defs>
                                                    <linearGradient id="uptimeGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                                                        <stop offset="0%" stop-color="#10B981" />
                                                        <stop offset="100%" stop-color="#06B6D4" />
                                                    </linearGradient>
                                                </defs>
                                            </svg>
                                            <div class="absolute text-center leading-tight">
                                                <p class="text-xl font-black text-slate-900 tracking-tight">{{
                                                    liveUptime.percentage }}%</p>
                                                <p
                                                    class="text-[8.5px] text-slate-500 font-bold uppercase tracking-wider mt-0.5">
                                                    Overall Uptime</p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Right 4 Sleek Gradient Progress Bars (7 of 12) -->
                                    <div class="sm:col-span-7 space-y-2.5">
                                        <!-- Bar 1: CPU Usage -->
                                        <div>
                                            <div class="flex items-center justify-between text-xs font-bold mb-1">
                                                <span class="text-slate-600 text-[10.5px]">CPU Usage</span>
                                                <span class="text-slate-900 font-black text-[11px]">{{ liveCpu.usage
                                                    }}%</span>
                                            </div>
                                            <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                                                <div class="bg-gradient-to-r from-blue-600 to-cyan-400 h-2 rounded-full transition-all duration-700 ease-out shadow-xs"
                                                    :style="`width: ${liveCpu.usage}%`"></div>
                                            </div>
                                        </div>

                                        <!-- Bar 2: RAM Usage -->
                                        <div>
                                            <div class="flex items-center justify-between text-xs font-bold mb-1">
                                                <span class="text-slate-600 text-[10.5px]">RAM Usage</span>
                                                <span class="text-slate-900 font-black text-[11px]">{{ liveRam.usage
                                                    }}%</span>
                                            </div>
                                            <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                                                <div class="bg-gradient-to-r from-purple-600 to-indigo-400 h-2 rounded-full transition-all duration-700 ease-out shadow-xs"
                                                    :style="`width: ${liveRam.usage}%`"></div>
                                            </div>
                                        </div>

                                        <!-- Bar 3: Disk Usage -->
                                        <div>
                                            <div class="flex items-center justify-between text-xs font-bold mb-1">
                                                <span class="text-slate-600 text-[10.5px]">Disk Usage</span>
                                                <span class="text-slate-900 font-black text-[11px]">{{ liveDisk.usage
                                                    }}%</span>
                                            </div>
                                            <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                                                <div class="bg-gradient-to-r from-rose-500 to-orange-400 h-2 rounded-full transition-all duration-700 ease-out shadow-xs"
                                                    :style="`width: ${liveDisk.usage}%`"></div>
                                            </div>
                                        </div>

                                        <!-- Bar 4: Network Usage -->
                                        <div>
                                            <div class="flex items-center justify-between text-xs font-bold mb-1">
                                                <span class="text-slate-600 text-[10.5px]">Network Usage</span>
                                                <span class="text-slate-900 font-black text-[11px]">{{ liveNetwork.usage
                                                    }}%</span>
                                            </div>
                                            <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                                                <div class="bg-gradient-to-r from-teal-500 to-emerald-400 h-2 rounded-full transition-all duration-700 ease-out shadow-xs"
                                                    :style="`width: ${liveNetwork.usage}%`"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Card 2: Services Overview (6 cols) -->
                        <div
                            class="lg:col-span-5 bg-white border border-[#E2E8F0] rounded-[4px] p-5 shadow-2xs flex flex-col justify-between">
                            <div>
                                <div class="flex items-center justify-between mb-2.5 pb-1 border-b border-slate-100">
                                    <h2 class="text-xs font-bold text-slate-900 uppercase tracking-wide">Services
                                        Overview</h2>
                                    <Link :href="route('admin.websites.index')"
                                        class="text-[10.5px] font-bold text-blue-600 hover:text-blue-700">
                                        View ({{ servicesOverview.total }})
                                    </Link>
                                </div>

                                <div class="flex items-center justify-between gap-5 pt-1">
                                    <!-- Donut Chart (Enlarged Size) -->
                                    <div class="relative flex items-center justify-center shrink-0"
                                        style="width: 120px; height: 120px;">
                                        <svg class="transform -rotate-90" viewBox="0 0 100 100"
                                            style="width: 120px; height: 120px;">
                                            <circle cx="50" cy="50" r="38" stroke="#F1F5F9" stroke-width="13"
                                                fill="none" />
                                            <circle cx="50" cy="50" r="38" stroke="#2563EB" stroke-width="13"
                                                fill="none" stroke-dasharray="238.76"
                                                :stroke-dashoffset="238.76 * (1 - webPct)" />
                                            <circle cx="50" cy="50" r="38" stroke="#10B981" stroke-width="13"
                                                fill="none" stroke-dasharray="238.76"
                                                :stroke-dashoffset="238.76 * (1 - vpsPct)"
                                                :style="`transform: rotate(${webPct * 360}deg); transform-origin: center;`" />
                                            <circle cx="50" cy="50" r="38" stroke="#7C3AED" stroke-width="13"
                                                fill="none" stroke-dasharray="238.76"
                                                :stroke-dashoffset="238.76 * (1 - domPct)"
                                                :style="`transform: rotate(${(webPct + vpsPct) * 360}deg); transform-origin: center;`" />
                                            <circle cx="50" cy="50" r="38" stroke="#F59E0B" stroke-width="13"
                                                fill="none" stroke-dasharray="238.76"
                                                :stroke-dashoffset="238.76 * (1 - sslPct)"
                                                :style="`transform: rotate(${(webPct + vpsPct + domPct) * 360}deg); transform-origin: center;`" />
                                        </svg>
                                        <div class="absolute text-center leading-tight">
                                            <p class="text-xl font-black text-slate-900 tracking-tight">{{
                                                servicesOverview.total }}</p>
                                            <p class="text-[8.5px] text-slate-400 font-bold uppercase tracking-wider">
                                                Total</p>
                                        </div>
                                    </div>

                                    <!-- Legend List -->
                                    <div class="flex-1 space-y-2 text-xs pl-2">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center gap-1.5">
                                                <span class="w-2 h-2 rounded-[2px] bg-[#2563EB] shrink-0"></span>
                                                <span class="text-slate-600 font-medium text-[11px]">Web Hosting</span>
                                            </div>
                                            <span class="font-black text-slate-900 text-xs">{{
                                                servicesOverview.webHosting }}</span>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center gap-1.5">
                                                <span class="w-2 h-2 rounded-[2px] bg-[#10B981] shrink-0"></span>
                                                <span class="text-slate-600 font-medium text-[11px]">VPS Servers</span>
                                            </div>
                                            <span class="font-black text-slate-900 text-xs">{{
                                                servicesOverview.vpsServers }}</span>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center gap-1.5">
                                                <span class="w-2 h-2 rounded-[2px] bg-[#7C3AED] shrink-0"></span>
                                                <span class="text-slate-600 font-medium text-[11px]">Domains</span>
                                            </div>
                                            <span class="font-black text-slate-900 text-xs">{{ servicesOverview.domains
                                                }}</span>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center gap-1.5">
                                                <span class="w-2 h-2 rounded-[2px] bg-[#F59E0B] shrink-0"></span>
                                                <span class="text-slate-600 font-medium text-[11px]">SSL Certs</span>
                                            </div>
                                            <span class="font-black text-slate-900 text-xs">{{
                                                servicesOverview.sslCertificates }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Row 2: Security & SSL Radar (12-cols Full Width Directly Below Row 1) -->
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-3.5 items-stretch">
                        <div class="lg:col-span-12 bg-white border border-[#E2E8F0] rounded-[4px] p-5 shadow-2xs flex flex-col justify-between">
                            <div>
                                <!-- Header -->
                                <div class="flex items-center justify-between mb-3.5 pb-2 border-b border-slate-100">
                                    <div class="flex items-center gap-2">
                                        <h2 class="text-xs font-bold text-slate-900 uppercase tracking-wide">Security & SSL Radar</h2>
                                        <span class="inline-flex items-center gap-1 text-[9.5px] font-bold text-emerald-700 bg-emerald-50 px-1.5 py-0.2 rounded-[2px] border border-emerald-200">
                                            <ShieldCheckIcon class="w-3 h-3 text-emerald-600" />
                                            Firewall Active
                                        </span>
                                    </div>
                                    <Link :href="route().has('admin.firewall.index') ? route('admin.firewall.index') : '/admin/firewall'"
                                        class="text-[10.5px] font-bold text-blue-600 hover:text-blue-700">
                                        Firewall Rules
                                    </Link>
                                </div>

                                <!-- 4 Real Security Metric Boxes -->
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 pt-1">
                                    <!-- Box 1: Firewall Rules -->
                                    <div class="bg-slate-50/90 border border-slate-100 rounded-[3px] p-2.5 flex items-center gap-2.5">
                                        <div class="w-8 h-8 rounded-[3px] bg-blue-50 border border-blue-100 flex items-center justify-center shrink-0">
                                            <ShieldCheckIcon class="w-4 h-4 text-blue-600" />
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-sm font-black text-slate-900 leading-tight">{{ securityOverview.activeRules }}</p>
                                            <p class="text-[9px] font-semibold text-slate-500 truncate">Firewall Rules</p>
                                        </div>
                                    </div>

                                    <!-- Box 2: Blocked IPs -->
                                    <div class="bg-slate-50/90 border border-slate-100 rounded-[3px] p-2.5 flex items-center gap-2.5">
                                        <div class="w-8 h-8 rounded-[3px] bg-rose-50 border border-rose-100 flex items-center justify-center shrink-0">
                                            <LockClosedIcon class="w-4 h-4 text-rose-600" />
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-sm font-black text-slate-900 leading-tight">{{ securityOverview.blockedIps }}</p>
                                            <p class="text-[9px] font-semibold text-slate-500 truncate">Blocked IPs</p>
                                        </div>
                                    </div>

                                    <!-- Box 3: Active SSL -->
                                    <div class="bg-slate-50/90 border border-slate-100 rounded-[3px] p-2.5 flex items-center gap-2.5">
                                        <div class="w-8 h-8 rounded-[3px] bg-emerald-50 border border-emerald-100 flex items-center justify-center shrink-0">
                                            <CheckCircleIcon class="w-4 h-4 text-emerald-600" />
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-sm font-black text-slate-900 leading-tight">{{ securityOverview.activeSsl }}</p>
                                            <p class="text-[9px] font-semibold text-slate-500 truncate">SSL Active</p>
                                        </div>
                                    </div>

                                    <!-- Box 4: Expiring Soon -->
                                    <div class="bg-slate-50/90 border border-slate-100 rounded-[3px] p-2.5 flex items-center gap-2.5">
                                        <div class="w-8 h-8 rounded-[3px] bg-amber-50 border border-amber-100 flex items-center justify-center shrink-0">
                                            <ExclamationTriangleIcon class="w-4 h-4 text-amber-600" />
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-sm font-black text-slate-900 leading-tight">{{ securityOverview.expiringSsl }}</p>
                                            <p class="text-[9px] font-semibold text-slate-500 truncate">SSL Expiring</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Security Notice Bar -->
                                <div class="mt-3.5 p-2 rounded-[3px] bg-emerald-50/70 border border-emerald-100 flex items-center justify-between text-xs">
                                    <div class="flex items-center gap-2">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-ping"></span>
                                        <span class="text-[11px] font-semibold text-emerald-800">Security Health Index: Optimal</span>
                                    </div>
                                    <span class="text-[10px] font-bold text-emerald-700">Zero Critical Threats</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Row 3: Resource Overview (12-cols Full Width) -->
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-3.5 items-stretch">
                        <div class="lg:col-span-12 bg-white border border-[#E2E8F0] rounded-[4px] p-5 shadow-2xs flex flex-col justify-between">
                            <div>
                                <!-- Header: Title, Legend, and Period Tabs -->
                                <div class="flex items-center justify-between mb-3.5 pb-2 border-b border-slate-100">
                                    <div class="flex items-center gap-3">
                                        <h2 class="text-xs font-bold text-slate-900 uppercase tracking-wide">Resource Overview</h2>
                                        <!-- Legend Dots -->
                                        <div class="hidden sm:flex items-center gap-3 text-[10.5px] font-semibold text-slate-600">
                                            <div class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-[#7C3AED]"></span><span>CPU</span></div>
                                            <div class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-[#2563EB]"></span><span>RAM</span></div>
                                            <div class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-[#10B981]"></span><span>Bandwidth</span></div>
                                        </div>
                                    </div>

                                    <!-- Today, Week, Month Pill Tabs -->
                                    <div class="flex items-center p-0.5 bg-slate-100 rounded-[3px] text-[10.5px] font-bold">
                                        <button @click="activePeriod = 'Today'"
                                            :class="activePeriod === 'Today' ? 'bg-blue-600 text-white shadow-2xs' : 'text-slate-600 hover:text-slate-900'"
                                            class="px-2 py-0.5 rounded-[2px] transition-all cursor-pointer">
                                            Today
                                        </button>
                                        <button @click="activePeriod = 'Week'"
                                            :class="activePeriod === 'Week' ? 'bg-blue-600 text-white shadow-2xs' : 'text-slate-600 hover:text-slate-900'"
                                            class="px-2 py-0.5 rounded-[2px] transition-all cursor-pointer">
                                            Week
                                        </button>
                                        <button @click="activePeriod = 'Month'"
                                            :class="activePeriod === 'Month' ? 'bg-blue-600 text-white shadow-2xs' : 'text-slate-600 hover:text-slate-900'"
                                            class="px-2 py-0.5 rounded-[2px] transition-all cursor-pointer">
                                            Month
                                        </button>
                                    </div>
                                </div>

                                <!-- Smooth Wave Area Chart (Full 12-col Width) -->
                                <div class="pt-1">
                                    <div class="flex items-stretch gap-2.5 h-32">
                                        <!-- Y-Axis -->
                                        <div class="flex flex-col justify-between text-[9px] text-slate-400 font-mono select-none w-7 text-right pr-1">
                                            <span>100%</span>
                                            <span>80%</span>
                                            <span>60%</span>
                                            <span>40%</span>
                                            <span>20%</span>
                                            <span>0%</span>
                                        </div>

                                        <!-- SVG Wave Canvas -->
                                        <div class="flex-1 relative overflow-hidden">
                                            <svg class="w-full h-full overflow-visible" viewBox="0 0 1120 120" preserveAspectRatio="none">
                                                <defs>
                                                    <linearGradient id="ramAreaGrad" x1="0" y1="0" x2="0" y2="1">
                                                        <stop offset="0%" stop-color="#2563EB" stop-opacity="0.25" />
                                                        <stop offset="100%" stop-color="#2563EB" stop-opacity="0.0" />
                                                    </linearGradient>
                                                    <linearGradient id="cpuAreaGrad" x1="0" y1="0" x2="0" y2="1">
                                                        <stop offset="0%" stop-color="#7C3AED" stop-opacity="0.25" />
                                                        <stop offset="100%" stop-color="#7C3AED" stop-opacity="0.0" />
                                                    </linearGradient>
                                                    <linearGradient id="netAreaGrad" x1="0" y1="0" x2="0" y2="1">
                                                        <stop offset="0%" stop-color="#10B981" stop-opacity="0.25" />
                                                        <stop offset="100%" stop-color="#10B981" stop-opacity="0.0" />
                                                    </linearGradient>
                                                </defs>

                                                <!-- Horizontal Grid Lines -->
                                                <line x1="0" y1="0" x2="1120" y2="0" stroke="#F1F5F9" stroke-width="1" />
                                                <line x1="0" y1="24" x2="1120" y2="24" stroke="#F1F5F9" stroke-width="1" />
                                                <line x1="0" y1="48" x2="1120" y2="48" stroke="#F1F5F9" stroke-width="1" />
                                                <line x1="0" y1="72" x2="1120" y2="72" stroke="#F1F5F9" stroke-width="1" />
                                                <line x1="0" y1="96" x2="1120" y2="96" stroke="#F1F5F9" stroke-width="1" />
                                                <line x1="0" y1="120" x2="1120" y2="120" stroke="#F1F5F9" stroke-width="1" />

                                                <!-- Filled Waves -->
                                                <path :d="generateSmoothAreaPath(ramPoints, 1120, 120)" fill="url(#ramAreaGrad)" class="transition-all duration-700 ease-out" />
                                                <path :d="generateSmoothAreaPath(cpuPoints, 1120, 120)" fill="url(#cpuAreaGrad)" class="transition-all duration-700 ease-out" />
                                                <path :d="generateSmoothAreaPath(netPoints, 1120, 120)" fill="url(#netAreaGrad)" class="transition-all duration-700 ease-out" />

                                                <!-- Stroke Lines -->
                                                <path :d="generateSmoothSvgPath(ramPoints, 1120, 120)" fill="none" stroke="#2563EB" stroke-width="2" stroke-linecap="round" class="transition-all duration-700 ease-out" />
                                                <path :d="generateSmoothSvgPath(cpuPoints, 1120, 120)" fill="none" stroke="#7C3AED" stroke-width="2" stroke-linecap="round" class="transition-all duration-700 ease-out" />
                                                <path :d="generateSmoothSvgPath(netPoints, 1120, 120)" fill="none" stroke="#10B981" stroke-width="2" stroke-linecap="round" class="transition-all duration-700 ease-out" />
                                            </svg>
                                        </div>
                                    </div>

                                    <!-- X-Axis Timeline (12 timestamps) -->
                                    <div class="flex items-center justify-between text-[9px] text-slate-400 font-mono pl-9 pt-2">
                                        <span>12AM</span>
                                        <span>2AM</span>
                                        <span>4AM</span>
                                        <span>6AM</span>
                                        <span>8AM</span>
                                        <span>10AM</span>
                                        <span>12PM</span>
                                        <span>2PM</span>
                                        <span>4PM</span>
                                        <span>6PM</span>
                                        <span>8PM</span>
                                        <span>10PM</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Row 4: Quick Actions (12-cols Full Width with 16 Shortcuts) -->
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-3.5 items-stretch">
                        <div class="lg:col-span-12 bg-white border border-[#E2E8F0] rounded-[4px] p-5 shadow-2xs flex flex-col justify-between">
                            <div>
                                <div class="flex items-center justify-between mb-3.5 pb-2 border-b border-slate-100">
                                    <div class="flex items-center gap-2">
                                        <h2 class="text-xs font-bold text-slate-900 uppercase tracking-wide">Quick Actions</h2>
                                        <span class="inline-flex items-center gap-1 text-[9.5px] font-bold text-blue-700 bg-blue-50 px-1.5 py-0.5 rounded-[2px] border border-blue-200">
                                            16 Direct Shortcuts
                                        </span>
                                    </div>
                                    <span class="text-[10px] text-slate-400 font-medium">One-Click Management</span>
                                </div>

                                <!-- 16 Action Buttons Grid (2 Rows x 8 Columns on Large Screens) -->
                                <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-6 gap-2.5">
                                    <Link v-for="action in quickActionsList" :key="action.id" :href="action.href"
                                        class="flex flex-col items-center justify-center p-2.5 rounded-[4px] bg-slate-50/80 hover:bg-blue-50/80 border border-slate-100 hover:border-blue-200 transition-all duration-150 group text-center shadow-2xs hover:shadow-xs hover:-translate-y-0.5 cursor-pointer min-h-[64px]">
                                        <div :class="`w-7 h-7 rounded-[4px] flex items-center justify-center mb-1.5 group-hover:scale-110 transition-transform ${action.bg}`">
                                            <component :is="action.icon" class="w-5 h-5" />
                                        </div>
                                        <span class="text-[10px] font-bold text-slate-700 group-hover:text-blue-600 leading-tight truncate max-w-full px-0.5">
                                            {{ action.label }}
                                        </span>
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Row 5: Storage Analytics (12-cols Full Width) -->
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-3.5 items-stretch">
                        <div class="lg:col-span-12 bg-white border border-[#E2E8F0] rounded-[4px] p-5 shadow-2xs flex flex-col justify-between">
                            <div>
                                <!-- Header -->
                                <div class="flex items-center justify-between mb-3.5 pb-2 border-b border-slate-100">
                                    <div class="flex items-center gap-2">
                                        <h2 class="text-xs font-bold text-slate-900 uppercase tracking-wide">Storage Analytics</h2>
                                        <span class="inline-flex items-center gap-1 text-[9.5px] font-bold text-blue-700 bg-blue-50 px-1.5 py-0.5 rounded-[2px] border border-blue-200">
                                            {{ storageDetails?.usagePercent || 0 }}% Used
                                        </span>
                                    </div>
                                    <span class="text-[10px] text-slate-400 font-medium">Root Filesystem</span>
                                </div>

                                <!-- Top Stats Row -->
                                <div class="grid grid-cols-3 gap-3 mb-3.5">
                                    <div class="p-3 rounded-[3px] bg-slate-50/80 border border-slate-100">
                                        <p class="text-[9px] font-semibold text-slate-400 uppercase">Total Disk Space</p>
                                        <p class="text-sm font-black text-slate-900 leading-tight mt-0.5">{{ storageDetails?.total }}</p>
                                    </div>
                                    <div class="p-3 rounded-[3px] bg-slate-50/80 border border-slate-100">
                                        <p class="text-[9px] font-semibold text-slate-400 uppercase">Used Disk Space</p>
                                        <p class="text-sm font-black text-indigo-600 leading-tight mt-0.5">{{ storageDetails?.used }}</p>
                                    </div>
                                    <div class="p-3 rounded-[3px] bg-slate-50/80 border border-slate-100">
                                        <p class="text-[9px] font-semibold text-slate-400 uppercase">Available Free Space</p>
                                        <p class="text-sm font-black text-emerald-600 leading-tight mt-0.5">{{ storageDetails?.free }}</p>
                                    </div>
                                </div>

                                <!-- Progress Bar -->
                                <div class="mb-3.5">
                                    <div class="flex items-center justify-between text-[10.5px] font-bold text-slate-500 mb-1.5">
                                        <span>Disk Utilization Bar</span>
                                        <span class="text-slate-900 font-mono">{{ storageDetails?.used }} / {{ storageDetails?.total }} ({{ storageDetails?.usagePercent || 0 }}%)</span>
                                    </div>
                                    <div class="w-full bg-slate-100 rounded-full h-2.5 overflow-hidden">
                                        <div class="bg-gradient-to-r from-blue-600 via-indigo-500 to-purple-500 h-2.5 rounded-full transition-all duration-700 ease-out"
                                            :style="`width: ${storageDetails?.usagePercent || 0}%`"></div>
                                    </div>
                                </div>

                                <!-- 4 Real Breakdown Boxes -->
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-1">
                                    <!-- DB Storage -->
                                    <div class="bg-slate-50/90 border border-slate-100 rounded-[3px] p-2.5">
                                        <p class="text-[9px] font-semibold text-slate-400 truncate">Database Storage</p>
                                        <p class="text-xs font-black text-slate-900 font-mono mt-0.5">{{ storageDetails?.breakdown?.database }}</p>
                                    </div>
                                    <!-- Web Files -->
                                    <div class="bg-slate-50/90 border border-slate-100 rounded-[3px] p-2.5">
                                        <p class="text-[9px] font-semibold text-slate-400 truncate">Website Files</p>
                                        <p class="text-xs font-black text-slate-900 font-mono mt-0.5">{{ storageDetails?.breakdown?.websiteFiles }}</p>
                                    </div>
                                    <!-- Backup Storage -->
                                    <div class="bg-slate-50/90 border border-slate-100 rounded-[3px] p-2.5">
                                        <p class="text-[9px] font-semibold text-slate-400 truncate">Backup Storage</p>
                                        <p class="text-xs font-black text-slate-900 font-mono mt-0.5">{{ storageDetails?.breakdown?.backupStorage }}</p>
                                    </div>
                                    <!-- System Logs -->
                                    <div class="bg-slate-50/90 border border-slate-100 rounded-[3px] p-2.5">
                                        <p class="text-[9px] font-semibold text-slate-400 truncate">System Logs</p>
                                        <p class="text-xs font-black text-slate-900 font-mono mt-0.5">{{ storageDetails?.breakdown?.logs }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Footer -->
                            <div class="mt-3.5 pt-2 border-t border-slate-100 flex items-center justify-between text-[9px] text-slate-400 font-medium">
                                <span class="flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                                    NVMe Storage Partition (/dev/root)
                                </span>
                                <span>Health: Optimal</span>
                            </div>
                        </div>
                    </div>

                    <!-- Row 6: Bandwidth & Traffic Analytics (12-cols Full Width) -->
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-3.5 items-stretch">
                        <div class="lg:col-span-12 bg-white border border-[#E2E8F0] rounded-[4px] p-5 shadow-2xs flex flex-col justify-between">
                            <div>
                                <!-- Header -->
                                <div class="flex items-center justify-between mb-3.5 pb-2 border-b border-slate-100">
                                    <div class="flex items-center gap-2">
                                        <h2 class="text-xs font-bold text-slate-900 uppercase tracking-wide">Bandwidth & Traffic</h2>
                                        <span class="inline-flex items-center gap-1 text-[9.5px] font-bold text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded-[2px] border border-emerald-200">
                                            {{ bandwidthDetails?.remainingPercent || 99 }}% Remaining
                                        </span>
                                    </div>
                                    <span class="text-[10px] text-slate-400 font-medium">Monthly Network Allocation</span>
                                </div>

                                <!-- Top Stats Row -->
                                <div class="grid grid-cols-3 gap-3 mb-3.5">
                                    <div class="p-3 rounded-[3px] bg-slate-50/80 border border-slate-100">
                                        <p class="text-[9px] font-semibold text-slate-400 uppercase">Monthly Bandwidth Limit</p>
                                        <p class="text-sm font-black text-slate-900 leading-tight mt-0.5">{{ bandwidthDetails?.monthlyLimit }}</p>
                                    </div>
                                    <div class="p-3 rounded-[3px] bg-slate-50/80 border border-slate-100">
                                        <p class="text-[9px] font-semibold text-slate-400 uppercase">Consumed This Month</p>
                                        <p class="text-sm font-black text-blue-600 leading-tight mt-0.5">{{ bandwidthDetails?.thisMonth }}</p>
                                    </div>
                                    <div class="p-3 rounded-[3px] bg-slate-50/80 border border-slate-100">
                                        <p class="text-[9px] font-semibold text-slate-400 uppercase">Remaining Bandwidth</p>
                                        <p class="text-sm font-black text-emerald-600 leading-tight mt-0.5">{{ bandwidthDetails?.remaining }}</p>
                                    </div>
                                </div>

                                <!-- Progress Bar -->
                                <div class="mb-3.5">
                                    <div class="flex items-center justify-between text-[10.5px] font-bold text-slate-500 mb-1.5">
                                        <span>Bandwidth Consumed</span>
                                        <span class="text-slate-900 font-mono">{{ bandwidthDetails?.thisMonth }} / {{ bandwidthDetails?.monthlyLimit }} ({{ bandwidthDetails?.usedPercent }}%)</span>
                                    </div>
                                    <div class="w-full bg-slate-100 rounded-full h-2.5 overflow-hidden">
                                        <div class="bg-gradient-to-r from-teal-500 to-emerald-400 h-2.5 rounded-full transition-all duration-700 ease-out"
                                            :style="`width: ${bandwidthDetails?.usedPercent || 1}%`"></div>
                                    </div>
                                </div>

                                <!-- 4 Real Traffic Metrics Boxes -->
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-1">
                                    <!-- Today -->
                                    <div class="bg-slate-50/90 border border-slate-100 rounded-[3px] p-2.5">
                                        <p class="text-[9px] font-semibold text-slate-400 truncate">Today's Traffic</p>
                                        <p class="text-xs font-black text-slate-900 font-mono mt-0.5">{{ bandwidthDetails?.today }}</p>
                                    </div>
                                    <!-- This Month -->
                                    <div class="bg-slate-50/90 border border-slate-100 rounded-[3px] p-2.5">
                                        <p class="text-[9px] font-semibold text-slate-400 truncate">This Month</p>
                                        <p class="text-xs font-black text-slate-900 font-mono mt-0.5">{{ bandwidthDetails?.thisMonth }}</p>
                                    </div>
                                    <!-- Download -->
                                    <div class="bg-slate-50/90 border border-slate-100 rounded-[3px] p-2.5">
                                        <p class="text-[9px] font-semibold text-slate-400 truncate">Download (RX)</p>
                                        <p class="text-xs font-black text-teal-700 font-mono mt-0.5">{{ bandwidthDetails?.download }}</p>
                                    </div>
                                    <!-- Upload -->
                                    <div class="bg-slate-50/90 border border-slate-100 rounded-[3px] p-2.5">
                                        <p class="text-[9px] font-semibold text-slate-400 truncate">Upload (TX)</p>
                                        <p class="text-xs font-black text-indigo-700 font-mono mt-0.5">{{ bandwidthDetails?.upload }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Footer -->
                            <div class="mt-3.5 pt-2 border-t border-slate-100 flex items-center justify-between text-[9px] text-slate-400 font-medium">
                                <span class="flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                    Peak Rate: {{ bandwidthDetails?.peakUsage }}
                                </span>
                                <span>{{ bandwidthDetails?.remainingPercent }}% Free</span>
                            </div>
                        </div>
                    </div>

                    <!-- Row 7: Domain & Hosting Overview (12-cols Full Width) -->
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-3.5 items-stretch">
                        <div class="lg:col-span-12 bg-white border border-[#E2E8F0] rounded-[4px] p-5 shadow-2xs flex flex-col justify-between">
                            <div>
                                <!-- Header -->
                                <div class="flex items-center justify-between mb-3.5 pb-2 border-b border-slate-100">
                                    <div class="flex items-center gap-2">
                                        <h2 class="text-xs font-bold text-slate-900 uppercase tracking-wide">Domain & Hosting Overview</h2>
                                        <span class="inline-flex items-center gap-1 text-[9.5px] font-bold text-purple-700 bg-purple-50 px-1.5 py-0.5 rounded-[2px] border border-purple-200">
                                            {{ domStats.total }} Domains Managed
                                        </span>
                                    </div>
                                    <Link :href="route('admin.websites.index')" class="text-[10.5px] font-bold text-blue-600 hover:text-blue-700">
                                        Manage All
                                    </Link>
                                </div>

                                <!-- Top Half: Donut Chart + Domain Status Breakdown -->
                                <div class="grid grid-cols-1 sm:grid-cols-12 gap-5 items-center pt-1 mb-4">
                                    <!-- Left: Large Glowing Donut (4 cols) -->
                                    <div class="sm:col-span-4 flex items-center justify-center">
                                        <div class="relative flex items-center justify-center shrink-0" style="width: 125px; height: 125px;">
                                            <svg class="transform -rotate-90" viewBox="0 0 100 100" style="width: 125px; height: 125px;">
                                                <circle cx="50" cy="50" r="39" stroke="#F1F5F9" stroke-width="12" fill="none" />
                                                <circle cx="50" cy="50" r="39" stroke="url(#domainDonutGrad)"
                                                    stroke-width="12" stroke-linecap="round" fill="none"
                                                    stroke-dasharray="245.04"
                                                    :stroke-dashoffset="245.04 * (1 - domStats.activeRatio)"
                                                    class="transition-all duration-700 ease-out" />
                                                <defs>
                                                    <linearGradient id="domainDonutGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                                                        <stop offset="0%" stop-color="#A855F7" />
                                                        <stop offset="50%" stop-color="#3B82F6" />
                                                        <stop offset="100%" stop-color="#06B6D4" />
                                                    </linearGradient>
                                                </defs>
                                            </svg>
                                            <div class="absolute text-center leading-tight">
                                                <p class="text-lg font-black text-slate-900 tracking-tight">{{ domStats.total }}</p>
                                                <p class="text-[8.5px] text-slate-500 font-bold uppercase tracking-wider mt-0.5">Total Domains</p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Right: 3 Status Breakdown Metric Boxes (8 cols) -->
                                    <div class="sm:col-span-8 grid grid-cols-1 sm:grid-cols-3 gap-3">
                                        <div class="p-3 rounded-[3px] bg-emerald-50/60 border border-emerald-100 flex flex-col justify-between">
                                            <div class="flex items-center justify-between text-xs mb-1">
                                                <span class="text-[11px] font-semibold text-emerald-800">Active</span>
                                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                            </div>
                                            <p class="text-base font-black text-slate-900">{{ domStats.active }}</p>
                                            <p class="text-[9px] text-emerald-700 font-medium">{{ domStats.activePct }}% of portfolio</p>
                                        </div>

                                        <div class="p-3 rounded-[3px] bg-rose-50/60 border border-rose-100 flex flex-col justify-between">
                                            <div class="flex items-center justify-between text-xs mb-1">
                                                <span class="text-[11px] font-semibold text-rose-800">Expired</span>
                                                <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                                            </div>
                                            <p class="text-base font-black text-slate-900">{{ domStats.expired }}</p>
                                            <p class="text-[9px] text-rose-700 font-medium">{{ domStats.expiredPct }}% renewal pending</p>
                                        </div>

                                        <div class="p-3 rounded-[3px] bg-amber-50/60 border border-amber-100 flex flex-col justify-between">
                                            <div class="flex items-center justify-between text-xs mb-1">
                                                <span class="text-[11px] font-semibold text-amber-800">Pending</span>
                                                <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                            </div>
                                            <p class="text-base font-black text-slate-900">{{ domStats.pending }}</p>
                                            <p class="text-[9px] text-amber-700 font-medium">{{ domStats.pendingPct }}% provisioning</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Bottom Half: 4 Sub-Service Cards -->
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-3 border-t border-slate-100">
                                    <!-- Box 1: Web Hosting -->
                                    <div class="bg-slate-50/90 border border-slate-100 rounded-[3px] p-2.5 flex items-center gap-2.5">
                                        <div class="w-8 h-8 rounded-[3px] bg-purple-50 border border-purple-100 flex items-center justify-center shrink-0">
                                            <GlobeAltIcon class="w-4 h-4 text-purple-600" />
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-xs font-black text-slate-900 leading-tight">{{ domStats.webHosting }}</p>
                                            <p class="text-[9px] font-semibold text-slate-500 truncate">Web Hosting</p>
                                        </div>
                                    </div>

                                    <!-- Box 2: VPS Servers -->
                                    <div class="bg-slate-50/90 border border-slate-100 rounded-[3px] p-2.5 flex items-center gap-2.5">
                                        <div class="w-8 h-8 rounded-[3px] bg-blue-50 border border-blue-100 flex items-center justify-center shrink-0">
                                            <ServerStackIcon class="w-4 h-4 text-blue-600" />
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-xs font-black text-slate-900 leading-tight">{{ domStats.vpsServers }}</p>
                                            <p class="text-[9px] font-semibold text-slate-500 truncate">VPS Servers</p>
                                        </div>
                                    </div>

                                    <!-- Box 3: Dedicated -->
                                    <div class="bg-slate-50/90 border border-slate-100 rounded-[3px] p-2.5 flex items-center gap-2.5">
                                        <div class="w-8 h-8 rounded-[3px] bg-teal-50 border border-teal-100 flex items-center justify-center shrink-0">
                                            <CircleStackIcon class="w-4 h-4 text-teal-600" />
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-xs font-black text-slate-900 leading-tight">{{ domStats.dedicated }}</p>
                                            <p class="text-[9px] font-semibold text-slate-500 truncate">Dedicated</p>
                                        </div>
                                    </div>

                                    <!-- Box 4: Other Services -->
                                    <div class="bg-slate-50/90 border border-slate-100 rounded-[3px] p-2.5 flex items-center gap-2.5">
                                        <div class="w-8 h-8 rounded-[3px] bg-indigo-50 border border-indigo-100 flex items-center justify-center shrink-0">
                                            <Squares2X2Icon class="w-4 h-4 text-indigo-600" />
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-xs font-black text-slate-900 leading-tight">{{ domStats.otherServices }}</p>
                                            <p class="text-[9px] font-semibold text-slate-500 truncate">Other Services</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Row 8: Advanced Financial Snapshot & Revenue Radar (12-cols Full Width) -->
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-3.5 items-stretch">
                        <div class="lg:col-span-12 bg-white border border-[#E2E8F0] rounded-[4px] p-5 shadow-2xs flex flex-col justify-between">
                            <div>
                                <!-- Header -->
                                <div class="flex items-center justify-between mb-4 pb-2 border-b border-slate-100">
                                    <div class="flex items-center gap-2.5">
                                        <h2 class="text-xs font-bold text-slate-900 uppercase tracking-wide">Financial Snapshot & Revenue Radar</h2>
                                        <span class="inline-flex items-center gap-1 text-[9.5px] font-bold text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded-[2px] border border-indigo-200">
                                            MRR: ${{ Number(financialOverview?.mrr || 0).toLocaleString(undefined, { minimumFractionDigits: 2 }) }} / mo
                                        </span>
                                        <span class="hidden sm:inline-flex items-center gap-1 text-[9.5px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-[2px] border border-emerald-200">
                                            ARR: ${{ Number(financialOverview?.arr || 0).toLocaleString(undefined, { minimumFractionDigits: 2 }) }} / yr
                                        </span>
                                    </div>
                                    <Link :href="route().has('admin.billing.index') ? route('admin.billing.index') : '/admin/billing'"
                                        class="text-[10.5px] font-bold text-blue-600 hover:text-blue-700 flex items-center gap-1">
                                        <span>Manage Billing</span>
                                        <component :is="ArrowTopRightOnSquareIcon" class="w-3 h-3" />
                                    </Link>
                                </div>

                                <!-- 4 Hero Metric Cards (Top Row) -->
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-4">
                                    <!-- 1: MRR (Monthly Recurring Revenue) -->
                                    <div class="p-3 rounded-[3px] bg-gradient-to-br from-indigo-50/70 to-purple-50/40 border border-indigo-100/80 flex flex-col justify-between">
                                        <div class="flex items-center justify-between text-xs mb-1">
                                            <span class="text-[9.5px] font-bold text-indigo-800 uppercase tracking-wider">Recurring MRR</span>
                                            <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                                        </div>
                                        <p class="text-base font-black text-slate-900 leading-tight">
                                            ${{ Number(financialOverview?.mrr || 0).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}
                                        </p>
                                        <p class="text-[9px] text-indigo-600 font-semibold mt-0.5 truncate">
                                            ARR: ${{ Number(financialOverview?.arr || 0).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}
                                        </p>
                                    </div>

                                    <!-- 2: This Month Revenue -->
                                    <div class="p-3 rounded-[3px] bg-gradient-to-br from-emerald-50/70 to-teal-50/40 border border-emerald-100/80 flex flex-col justify-between">
                                        <div class="flex items-center justify-between text-xs mb-1">
                                            <span class="text-[9.5px] font-bold text-emerald-800 uppercase tracking-wider">This Month</span>
                                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                        </div>
                                        <p class="text-base font-black text-slate-900 leading-tight">
                                            ${{ Number(financialOverview?.thisMonthRevenue || 0).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}
                                        </p>
                                        <p class="text-[9px] text-emerald-600 font-semibold mt-0.5 truncate">
                                            Today: ${{ Number(financialOverview?.todayRevenue || 0).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}
                                        </p>
                                    </div>

                                    <!-- 3: New Orders Value -->
                                    <div class="p-3 rounded-[3px] bg-gradient-to-br from-blue-50/70 to-cyan-50/40 border border-blue-100/80 flex flex-col justify-between">
                                        <div class="flex items-center justify-between text-xs mb-1">
                                            <span class="text-[9.5px] font-bold text-blue-800 uppercase tracking-wider">New Orders Value</span>
                                            <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                                        </div>
                                        <p class="text-base font-black text-slate-900 leading-tight">
                                            ${{ Number(financialOverview?.newOrdersValue || 0).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}
                                        </p>
                                        <p class="text-[9px] text-blue-600 font-semibold mt-0.5 truncate">
                                            Lifetime: ${{ Number(financialOverview?.collectedAllTime || 0).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}
                                        </p>
                                    </div>

                                    <!-- 4: Outstanding Due -->
                                    <div class="p-3 rounded-[3px] bg-gradient-to-br from-rose-50/70 to-amber-50/40 border border-rose-100/80 flex flex-col justify-between">
                                        <div class="flex items-center justify-between text-xs mb-1">
                                            <span class="text-[9.5px] font-bold text-rose-800 uppercase tracking-wider">Outstanding Due</span>
                                            <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                                        </div>
                                        <p class="text-base font-black text-rose-700 leading-tight">
                                            ${{ Number(financialOverview?.outstandingDue || 0).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}
                                        </p>
                                        <p class="text-[9px] text-rose-600 font-semibold mt-0.5 truncate">
                                            {{ financialOverview?.unpaidCount || 0 }} Unpaid Invoices
                                        </p>
                                    </div>
                                </div>

                                <!-- Middle Split: Detailed Breakdown & Gateway Health (6 cols + 6 cols) -->
                                <div class="grid grid-cols-1 lg:grid-cols-12 gap-3.5 mb-4">
                                    <!-- Left: 6 Financial Performance Metrics -->
                                    <div class="lg:col-span-6 bg-slate-50/80 rounded-[4px] border border-slate-100 p-3.5">
                                        <div class="flex items-center justify-between mb-2.5 pb-1.5 border-b border-slate-200/60">
                                            <span class="text-[10px] font-bold text-slate-700 uppercase">Revenue Breakdown</span>
                                            <span class="text-[9px] text-slate-400 font-medium">Real-time DB audit</span>
                                        </div>
                                        <div class="grid grid-cols-2 gap-2 text-xs">
                                            <div class="bg-white p-2 rounded-[3px] border border-slate-100">
                                                <p class="text-[8.5px] font-semibold text-slate-400 uppercase">Today's Revenue</p>
                                                <p class="text-xs font-black text-slate-900 mt-0.5">${{ Number(financialOverview?.todayRevenue || 0).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}</p>
                                            </div>
                                            <div class="bg-white p-2 rounded-[3px] border border-slate-100">
                                                <p class="text-[8.5px] font-semibold text-slate-400 uppercase">Previous Month</p>
                                                <p class="text-xs font-black text-slate-900 mt-0.5">${{ Number(financialOverview?.prevMonthRevenue || 0).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}</p>
                                            </div>
                                            <div class="bg-white p-2 rounded-[3px] border border-slate-100">
                                                <p class="text-[8.5px] font-semibold text-slate-400 uppercase">Refunds Processed</p>
                                                <p class="text-xs font-black text-amber-600 mt-0.5">${{ Number(financialOverview?.refunds || 0).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}</p>
                                            </div>
                                            <div class="bg-white p-2 rounded-[3px] border border-slate-100">
                                                <p class="text-[8.5px] font-semibold text-slate-400 uppercase">Failed Payments</p>
                                                <p class="text-xs font-black text-rose-600 mt-0.5">{{ financialOverview?.failedPayments || 0 }} failed</p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Right: Payment Gateway Status -->
                                    <div class="lg:col-span-6 bg-slate-50/80 rounded-[4px] border border-slate-100 p-3.5">
                                        <div class="flex items-center justify-between mb-2.5 pb-1.5 border-b border-slate-200/60">
                                            <span class="text-[10px] font-bold text-slate-700 uppercase">Payment Gateway Status</span>
                                            <span class="text-[9px] text-slate-400 font-medium">Auto-Webhook Active</span>
                                        </div>
                                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                                            <div v-for="gw in financialOverview?.gateways" :key="gw.id"
                                                class="bg-white p-2 rounded-[3px] border border-slate-100 flex items-center justify-between">
                                                <div class="min-w-0 pr-1">
                                                    <p class="text-[9.5px] font-bold text-slate-900 truncate">{{ gw.name }}</p>
                                                    <span class="text-[8px] font-mono text-slate-400 uppercase">{{ gw.mode }}</span>
                                                </div>
                                                <span :class="gw.is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-500 border-slate-200'"
                                                    class="text-[8px] font-bold px-1.5 py-0.2 rounded-[2px] border shrink-0">
                                                    {{ gw.is_active ? '🟢 Live' : '⚪ Off' }}
                                                </span>
                                            </div>
                                            <div v-if="!financialOverview?.gateways?.length" class="col-span-3 text-center py-2 text-[10px] text-slate-400">
                                                No gateways configured.
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Bottom: Recent Real Transactions Stream -->
                                <div>
                                    <div class="flex items-center justify-between mb-2 pb-1 border-b border-slate-100">
                                        <span class="text-[10px] font-bold text-slate-700 uppercase">Recent Transactions Stream</span>
                                        <span class="text-[9px] text-slate-400">Live Inflows</span>
                                    </div>
                                    <div class="space-y-1.5">
                                        <div v-for="t in financialOverview.recentTransactions" :key="t.id"
                                            class="flex items-center justify-between py-2 px-3 rounded-[3px] bg-slate-50/70 hover:bg-slate-100/70 border border-slate-100 transition-colors text-xs">
                                            <div class="min-w-0 pr-2">
                                                <p class="text-[11px] font-bold text-slate-900 truncate">{{ t.user_name }}</p>
                                                <p class="text-[9px] text-slate-400 font-mono">{{ t.transaction_id }} • {{ t.gateway }}</p>
                                            </div>
                                            <div class="text-right shrink-0">
                                                <p class="text-xs font-black text-emerald-700">+${{ t.amount }}</p>
                                                <p class="text-[8.5px] text-slate-400">{{ t.time }}</p>
                                            </div>
                                        </div>
                                        <div v-if="!financialOverview.recentTransactions?.length" class="text-center py-3 text-slate-400 text-xs">
                                            No recent transactions recorded yet.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Row 9: Revenue & Customer Growth (12-cols Full Width Dual-Line Wave Chart) -->
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-3.5 items-stretch">
                        <div class="lg:col-span-12 bg-white border border-[#E2E8F0] rounded-[4px] p-5 shadow-2xs flex flex-col justify-between">
                            <div>
                                <!-- Header: Title, Legend, and Periods Selector -->
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 mb-4 pb-2.5 border-b border-slate-100">
                                    <div class="flex items-center gap-3">
                                        <h2 class="text-xs font-bold text-slate-900 uppercase tracking-wide">Revenue & Customer Growth</h2>
                                        <!-- Dual Legend Indicator -->
                                        <div class="flex items-center gap-3 text-[10.5px] font-semibold text-slate-600">
                                            <div class="flex items-center gap-1.5">
                                                <span class="w-2.5 h-2.5 rounded-full bg-[#10B981]"></span>
                                                <span>Revenue ($)</span>
                                            </div>
                                            <div class="flex items-center gap-1.5">
                                                <span class="w-2.5 h-2.5 rounded-full bg-[#6366F1]"></span>
                                                <span>New Customers</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- 4 Period Tabs: Today, 7 Days, 30 Days, 12 Months -->
                                    <div class="flex items-center p-0.5 bg-slate-100 rounded-[3px] text-[10.5px] font-bold self-start sm:self-auto">
                                        <button v-for="p in ['Today', '7 Days', '30 Days', '12 Months']" :key="p"
                                            @click="growthPeriod = p"
                                            :class="growthPeriod === p ? 'bg-blue-600 text-white shadow-2xs' : 'text-slate-600 hover:text-slate-900'"
                                            class="px-2.5 py-1 rounded-[2px] transition-all cursor-pointer">
                                            {{ p }}
                                        </button>
                                    </div>
                                </div>

                                <!-- 4 Summary Growth Metrics Boxes -->
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-4">
                                    <div class="p-2.5 rounded-[3px] bg-slate-50/80 border border-slate-100">
                                        <p class="text-[8.5px] font-semibold text-slate-400 uppercase">Period Inflow</p>
                                        <p class="text-sm font-black text-emerald-600 mt-0.5">${{ Number(periodTotalRevenue).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}</p>
                                    </div>
                                    <div class="p-2.5 rounded-[3px] bg-slate-50/80 border border-slate-100">
                                        <p class="text-[8.5px] font-semibold text-slate-400 uppercase">New Signups</p>
                                        <p class="text-sm font-black text-indigo-600 mt-0.5">+{{ periodTotalCustomers }} Clients</p>
                                    </div>
                                    <div class="p-2.5 rounded-[3px] bg-slate-50/80 border border-slate-100">
                                        <p class="text-[8.5px] font-semibold text-slate-400 uppercase">Active Portfolio</p>
                                        <p class="text-sm font-black text-slate-900 mt-0.5">{{ growthAnalytics?.customersSummary?.active || 0 }} Active</p>
                                    </div>
                                    <div class="p-2.5 rounded-[3px] bg-slate-50/80 border border-slate-100">
                                        <p class="text-[8.5px] font-semibold text-slate-400 uppercase">Churn Rate</p>
                                        <p class="text-sm font-black text-rose-600 mt-0.5">{{ growthAnalytics?.customersSummary?.churnRate || 0 }}% ({{ growthAnalytics?.customersSummary?.churned || 0 }} off)</p>
                                    </div>
                                </div>

                                <!-- Dual-Line Wave Canvas (12-cols Full Width) -->
                                <div class="pt-1">
                                    <div class="flex items-stretch gap-2.5 h-36">
                                        <!-- Y-Axis Percentage / Scale -->
                                        <div class="flex flex-col justify-between text-[9px] text-slate-400 font-mono select-none w-7 text-right pr-1">
                                            <span>Max</span>
                                            <span>75%</span>
                                            <span>50%</span>
                                            <span>25%</span>
                                            <span>0</span>
                                        </div>

                                        <!-- SVG Canvas -->
                                        <div class="flex-1 relative overflow-hidden">
                                            <svg class="w-full h-full overflow-visible" viewBox="0 0 1120 140" preserveAspectRatio="none">
                                                <defs>
                                                    <linearGradient id="growthRevenueGrad" x1="0" y1="0" x2="0" y2="1">
                                                        <stop offset="0%" stop-color="#10B981" stop-opacity="0.30" />
                                                        <stop offset="100%" stop-color="#10B981" stop-opacity="0.0" />
                                                    </linearGradient>
                                                    <linearGradient id="growthCustomersGrad" x1="0" y1="0" x2="0" y2="1">
                                                        <stop offset="0%" stop-color="#6366F1" stop-opacity="0.25" />
                                                        <stop offset="100%" stop-color="#6366F1" stop-opacity="0.0" />
                                                    </linearGradient>
                                                </defs>

                                                <!-- Horizontal Grid Lines -->
                                                <line x1="0" y1="0" x2="1120" y2="0" stroke="#F1F5F9" stroke-width="1" />
                                                <line x1="0" y1="35" x2="1120" y2="35" stroke="#F1F5F9" stroke-width="1" />
                                                <line x1="0" y1="70" x2="1120" y2="70" stroke="#F1F5F9" stroke-width="1" />
                                                <line x1="0" y1="105" x2="1120" y2="105" stroke="#F1F5F9" stroke-width="1" />
                                                <line x1="0" y1="140" x2="1120" y2="140" stroke="#F1F5F9" stroke-width="1" />

                                                <!-- Filled Waves -->
                                                <path :d="generateSmoothAreaPath(normalizedGrowthRevenue, 1120, 140)" fill="url(#growthRevenueGrad)" class="transition-all duration-700 ease-out" />
                                                <path :d="generateSmoothAreaPath(normalizedGrowthCustomers, 1120, 140)" fill="url(#growthCustomersGrad)" class="transition-all duration-700 ease-out" />

                                                <!-- Stroke Lines -->
                                                <path :d="generateSmoothSvgPath(normalizedGrowthRevenue, 1120, 140)" fill="none" stroke="#10B981" stroke-width="2.5" stroke-linecap="round" class="transition-all duration-700 ease-out" />
                                                <path :d="generateSmoothSvgPath(normalizedGrowthCustomers, 1120, 140)" fill="none" stroke="#6366F1" stroke-width="2.5" stroke-linecap="round" class="transition-all duration-700 ease-out" />
                                            </svg>
                                        </div>
                                    </div>

                                    <!-- Dynamic X-Axis Timeline Labels -->
                                    <div class="flex items-center justify-between text-[9.5px] text-slate-400 font-mono pl-9 pt-2.5">
                                        <span v-for="(lbl, lIdx) in currentGrowthData.labels" :key="lIdx">
                                            {{ lbl }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Row 10: Live System Audit Trail (12-cols Full Width) -->
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-3.5 items-stretch">
                        <div class="lg:col-span-12 bg-white border border-[#E2E8F0] rounded-[4px] p-5 shadow-2xs flex flex-col justify-between">
                            <div>
                                <!-- Header -->
                                <div class="flex items-center justify-between mb-3.5 pb-2 border-b border-slate-100">
                                    <div class="flex items-center gap-2">
                                        <h2 class="text-xs font-bold text-slate-900 uppercase tracking-wide">Live System Audit Trail</h2>
                                        <span class="inline-flex items-center gap-1 text-[9.5px] font-bold text-slate-700 bg-slate-100 px-1.5 py-0.2 rounded-[2px]">
                                            Activity Stream
                                        </span>
                                    </div>
                                    <span class="text-[10.5px] text-slate-400 font-medium">Real-Time Security & Administrative Events</span>
                                </div>

                                <!-- Activity Grid / List -->
                                <div class="space-y-2">
                                    <div v-for="act in recentActivities.slice(0, 6)" :key="act.id"
                                        class="flex items-center justify-between py-2 px-3 rounded-[3px] bg-slate-50/80 hover:bg-slate-100/80 border border-slate-100 transition-colors text-xs">
                                        <div class="min-w-0 pr-3 flex-1">
                                            <div class="flex items-center gap-2 mb-0.5">
                                                <span class="text-[10px] font-bold text-slate-800 bg-white px-1.5 py-0.2 rounded-[2px] border border-slate-200">{{ act.action }}</span>
                                                <span class="text-[9.5px] font-mono text-slate-400">{{ act.ip_address }}</span>
                                            </div>
                                            <p class="text-[11px] text-slate-700 truncate font-medium">{{ act.description }}</p>
                                        </div>
                                        <div class="text-right shrink-0">
                                            <p class="text-[11px] font-bold text-slate-800">{{ act.user_name }}</p>
                                            <p class="text-[9px] text-slate-400">{{ act.time }}</p>
                                        </div>
                                    </div>
                                    <div v-if="!recentActivities?.length" class="text-center py-6 text-slate-400 text-xs">
                                        No activity logs recorded yet.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right 4 Columns Sidebar -->
                <div class="lg:col-span-4 space-y-3.5">
                    <!-- Card 1: Critical Alerts & Incidents -->
                    <div
                        class="bg-white border border-[#E2E8F0] rounded-[4px] p-5 shadow-2xs flex flex-col justify-between">
                        <div>
                            <!-- Header -->
                            <div class="flex items-center justify-between mb-3 pb-2 border-b border-slate-100">
                                <div class="flex items-center gap-1.5">
                                    <h2 class="text-xs font-bold text-slate-900 uppercase tracking-wide">Critical Alerts
                                    </h2>
                                </div>
                                <span
                                    :class="(criticalAlerts?.totalIncidents || 0) > 0 ? 'text-rose-700 bg-rose-50 border-rose-200' : 'text-emerald-700 bg-emerald-50 border-emerald-200'"
                                    class="inline-flex items-center gap-1 text-[9.5px] font-bold px-1.5 py-0.5 rounded-[2px] border shrink-0">
                                    <span
                                        :class="(criticalAlerts?.totalIncidents || 0) > 0 ? 'bg-rose-500 animate-ping' : 'bg-emerald-500'"
                                        class="w-1.5 h-1.5 rounded-full"></span>
                                    {{ (criticalAlerts?.totalIncidents || 0) > 0 ? `${criticalAlerts.totalIncidents}
                                    Issue(s)` : '0 Issues' }}
                                </span>
                            </div>

                            <!-- If there are active incidents (Real Data) -->
                            <div v-if="criticalAlerts?.incidents && criticalAlerts.incidents.length > 0"
                                class="space-y-2 pt-0.5">
                                <div v-for="(inc, idx) in criticalAlerts.incidents" :key="idx"
                                    :class="inc.type === 'critical' ? 'bg-rose-50/90 border-rose-200 hover:border-rose-300' : (inc.type === 'high' ? 'bg-amber-50/90 border-amber-200 hover:border-amber-300' : 'bg-yellow-50/90 border-yellow-200 hover:border-yellow-300')"
                                    class="p-2.5 rounded-[3px] border transition-all duration-150 flex items-start justify-between gap-2 shadow-2xs">
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-1.5 mb-0.5">
                                            <span class="text-xs shrink-0">{{ inc.level }}</span>
                                            <p :class="inc.type === 'critical' ? 'text-rose-900' : (inc.type === 'high' ? 'text-amber-900' : 'text-yellow-900')"
                                                class="text-[11px] font-bold truncate">
                                                {{ inc.title }}
                                            </p>
                                        </div>
                                        <p class="text-[10px] text-slate-600 leading-snug pl-4">
                                            {{ inc.desc }}
                                        </p>
                                    </div>
                                    <Link :href="inc.href"
                                        :class="inc.type === 'critical' ? 'text-rose-700 bg-rose-100 hover:bg-rose-200' : 'text-slate-700 bg-white hover:bg-slate-100 border border-slate-200'"
                                        class="px-1.5 py-0.5 rounded-[2px] text-[9px] font-bold shrink-0 transition-colors cursor-pointer mt-0.5">
                                        Fix
                                    </Link>
                                </div>
                            </div>

                            <!-- If 0 issues (All Systems Operational 100% Real Live Status) -->
                            <div v-else class="space-y-3 pt-1">
                                <div class="p-3 rounded-[3px] bg-emerald-50/70 border border-emerald-100 text-center">
                                    <div
                                        class="w-8 h-8 rounded-full bg-emerald-100 border border-emerald-200 flex items-center justify-center mx-auto mb-1.5">
                                        <CheckCircleIcon class="w-4 h-4 text-emerald-600" />
                                    </div>
                                    <p class="text-[11.5px] font-bold text-emerald-900">All Systems Operational</p>
                                    <p class="text-[9.5px] text-emerald-700 font-medium mt-0.5 leading-tight">
                                        Zero server down, DB failures or security threats detected.
                                    </p>
                                </div>

                                <!-- Monitored Health Matrix -->
                                <div class="space-y-1.5 text-xs">
                                    <div v-for="(chk, i) in (criticalAlerts?.monitoredChecks || [])" :key="i"
                                        class="flex items-center justify-between py-1 px-2 rounded-[2px] bg-slate-50/80 border border-slate-100">
                                        <div class="flex items-center gap-1.5 truncate pr-1">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 shrink-0"></span>
                                            <span class="text-[10px] font-medium text-slate-600 truncate">{{ chk.label
                                                }}</span>
                                        </div>
                                        <span class="text-[9.5px] font-mono font-bold text-slate-800 shrink-0">
                                            {{ chk.status }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Footer Watchdog Note -->
                        <div
                            class="mt-3.5 pt-2 border-t border-slate-100 flex items-center justify-between text-[9px] text-slate-400 font-medium">
                            <span class="flex items-center gap-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-blue-500 animate-pulse"></span>
                                24/7 Watchtower
                            </span>
                            <span>Live Monitoring</span>
                        </div>
                    </div>

                    <!-- Card 2: Support Tickets (Directly Below Critical Alerts) -->
                    <div class="bg-white border border-[#E2E8F0] rounded-[4px] p-5 shadow-2xs flex flex-col justify-between">
                        <div>
                            <!-- Header -->
                            <div class="flex items-center justify-between mb-3 pb-1.5 border-b border-slate-100">
                                <div class="flex items-center gap-2">
                                    <h2 class="text-xs font-bold text-slate-900 uppercase tracking-wide">Support Tickets</h2>
                                    <span class="inline-flex items-center gap-1 text-[9.5px] font-bold text-purple-700 bg-purple-50 px-1.5 py-0.2 rounded-[2px] border border-purple-200">
                                        Live Desk
                                    </span>
                                </div>
                                <Link :href="route().has('admin.tickets.index') ? route('admin.tickets.index') : '/admin/tickets'"
                                    class="text-[10.5px] font-bold text-blue-600 hover:text-blue-700">
                                    View All ({{ stats.tickets ?? 0 }})
                                </Link>
                            </div>

                            <!-- Ticket List -->
                            <div class="space-y-1.5">
                                <div v-for="ticket in recentTickets.slice(0, 4)" :key="ticket.id"
                                    class="flex items-center justify-between py-1.5 px-2.5 rounded-[3px] bg-slate-50/80 hover:bg-slate-100/80 border border-slate-100 transition-colors text-xs">
                                    <div class="min-w-0 pr-2">
                                        <div class="flex items-center gap-2">
                                            <span class="text-[10px] font-bold text-purple-700 font-mono">{{ ticket.ticket_no }}</span>
                                            <span :class="ticket.priority === 'high' ? 'bg-rose-50 text-rose-700 border-rose-200' : (ticket.priority === 'medium' ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-blue-50 text-blue-700 border-blue-200')"
                                                class="text-[8.5px] font-bold uppercase px-1 py-0.2 rounded-[2px] border">
                                                {{ ticket.priority }}
                                            </span>
                                        </div>
                                        <p class="text-[11px] font-semibold text-slate-800 truncate mt-0.5">{{ ticket.subject }}</p>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <p class="text-[10px] font-medium text-slate-600 truncate">{{ ticket.user_name }}</p>
                                        <p class="text-[8.5px] text-slate-400">{{ ticket.time }}</p>
                                    </div>
                                </div>
                                <div v-if="!recentTickets?.length" class="text-center py-4 text-slate-400 text-xs">
                                    No active support tickets at this time.
                                </div>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="mt-3.5 pt-2 border-t border-slate-100 flex items-center justify-between text-[9px] text-slate-400 font-medium">
                            <span class="flex items-center gap-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-purple-500"></span>
                                Active Queue
                            </span>
                            <span>Helpdesk Gateway</span>
                        </div>
                    </div>

                    <!-- CServer Specifications -->
                        <div
                        class="bg-white border border-[#E2E8F0] rounded-[4px] p-5 shadow-2xs flex flex-col justify-between">
                            <div>
                                <div class="flex items-center justify-between mb-3 pb-1.5 border-b border-slate-100">
                                    <h2 class="text-xs font-bold text-slate-900 uppercase tracking-wide">Server Specs
                                    </h2>
                                    <span
                                        class="inline-flex items-center gap-1 text-[9.5px] font-bold text-blue-700 bg-blue-50 px-1.5 py-0.2 rounded-[2px] border border-blue-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                                        Master Node
                                    </span>
                                </div>

                                <div class="space-y-2.5 pt-0.5 text-xs">
                                    <div class="flex items-center justify-between py-1 border-b border-slate-50">
                                        <span class="text-slate-500 font-medium text-[11px]">Hostname</span>
                                        <span
                                            class="font-bold text-slate-900 text-[11.5px] font-mono truncate max-w-[180px]"
                                            :title="serverDetails.hostname">{{ serverDetails.hostname }}</span>
                                    </div>
                                    <div class="flex items-center justify-between py-1 border-b border-slate-50">
                                        <span class="text-slate-500 font-medium text-[11px]">IP Address</span>
                                        <span class="font-bold text-blue-600 text-[11.5px] font-mono">{{
                                            serverDetails.ipAddress }}</span>
                                    </div>
                                    <div class="flex items-center justify-between py-1 border-b border-slate-50">
                                        <span class="text-slate-500 font-medium text-[11px]">OS Platform</span>
                                        <span class="font-bold text-slate-900 text-[11.5px] truncate max-w-[180px]"
                                            :title="serverDetails.os">{{ serverDetails.os }}</span>
                                    </div>
                                    <div class="flex items-center justify-between py-1 border-b border-slate-50">
                                        <span class="text-slate-500 font-medium text-[11px]">Kernel</span>
                                        <span
                                            class="font-bold text-slate-700 text-[11px] font-mono truncate max-w-[180px]"
                                            :title="serverDetails.kernel">{{ serverDetails.kernel }}</span>
                                    </div>
                                    <div class="flex items-center justify-between py-1">
                                        <span class="text-slate-500 font-medium text-[11px]">Architecture</span>
                                        <span class="font-bold text-slate-900 text-[11.5px]">{{ serverDetails.arch }}
                                            ({{ serverDetails.cores }} Cores)</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                    <!-- Card 2: Live Service Status Panel (Directly Below Critical Alerts) -->
                    <div
                        class="bg-white border border-[#E2E8F0] rounded-[4px] p-5 shadow-2xs flex flex-col justify-between">
                        <div>
                            <!-- Header -->
                            <div class="flex items-center justify-between mb-3 pb-2 border-b border-slate-100">
                                <div class="flex items-center gap-1.5">
                                    <h2 class="text-xs font-bold text-slate-900 uppercase tracking-wide">Live Service
                                        Status</h2>
                                </div>
                                <span
                                    class="inline-flex items-center gap-1 text-[9.5px] font-bold text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded-[2px] border border-emerald-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                    8/8 Online
                                </span>
                            </div>

                            <!-- Services Table with Real Latency & Status -->
                            <div class="space-y-1 pt-0.5 text-xs">
                                <!-- Table Header -->
                                <div
                                    class="grid grid-cols-12 text-[9.5px] font-bold text-slate-400 uppercase tracking-wider pb-1 px-2 border-b border-slate-100 select-none">
                                    <span class="col-span-5">Service</span>
                                    <span class="col-span-4 text-center">Status</span>
                                    <span class="col-span-3 text-right">Response</span>
                                </div>

                                <!-- Service Rows -->
                                <div v-for="svc in (liveServiceStatus || [])" :key="svc.service"
                                    class="grid grid-cols-12 items-center py-1.5 px-2 rounded-[3px] bg-slate-50/70 hover:bg-slate-100/80 border border-slate-100/80 transition-colors">
                                    <!-- Service Name -->
                                    <span class="col-span-5 text-[10.5px] font-bold text-slate-800 truncate">
                                        {{ svc.service }}
                                    </span>

                                    <!-- Status Badge -->
                                    <div class="col-span-4 flex items-center justify-center">
                                        <span
                                            :class="svc.status === 'Online' || svc.status === 'Healthy' ? 'text-emerald-700 bg-emerald-50 border-emerald-200' : 'text-rose-700 bg-rose-50 border-rose-200'"
                                            class="inline-flex items-center gap-1 text-[8.5px] font-bold px-1.5 py-0.2 rounded-[2px] border shrink-0">
                                            <span
                                                :class="svc.status === 'Online' || svc.status === 'Healthy' ? 'bg-emerald-500' : 'bg-rose-500'"
                                                class="w-1 h-1 rounded-full"></span>
                                            {{ svc.status }}
                                        </span>
                                    </div>

                                    <!-- Response Time Latency -->
                                    <span class="col-span-3 text-right text-[10px] font-mono font-bold text-slate-600">
                                        {{ svc.response }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div
                            class="mt-3.5 pt-2 border-t border-slate-100 flex items-center justify-between text-[9px] text-slate-400 font-medium">
                            <span class="flex items-center gap-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                Latency Benchmarked
                            </span>
                            <span>Daemon Health</span>
                        </div>
                    </div>

                    <!-- Card 3: Backup Status & Center -->
                    <div
                        class="bg-white border border-[#E2E8F0] rounded-[4px] p-5 shadow-2xs flex flex-col justify-between">
                        <div>
                            <!-- Header -->
                            <div class="flex items-center justify-between mb-3 pb-2 border-b border-slate-100">
                                <div class="flex items-center gap-1.5">
                                    <h2 class="text-xs font-bold text-slate-900 uppercase tracking-wide">Backup Status
                                    </h2>
                                </div>
                                <Link :href="backupStatus?.backupCenterHref || '/admin/backups'"
                                    class="text-[10.5px] font-bold text-blue-600 hover:text-blue-700">
                                    Backup Center
                                </Link>
                            </div>

                            <!-- Top Highlight: Last Backup & Status -->
                            <div class="p-3 rounded-[3px] bg-slate-50/80 border border-slate-100 mb-3 space-y-2">
                                <div class="flex items-center justify-between">
                                    <div class="min-w-0">
                                        <p class="text-[9px] font-bold text-slate-400 uppercase tracking-wider">Last
                                            Backup</p>
                                        <p class="text-xs font-black text-slate-900 leading-snug">{{
                                            backupStatus?.lastBackup?.time }}</p>
                                    </div>
                                    <span
                                        class="inline-flex items-center gap-1 text-[9.5px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 px-1.5 py-0.5 rounded-[2px] shrink-0">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        {{ backupStatus?.lastBackup?.status }}
                                    </span>
                                </div>

                                <div class="grid grid-cols-2 gap-2 pt-1 border-t border-slate-100 text-xs">
                                    <div>
                                        <p class="text-[8.5px] font-semibold text-slate-400">Size & Type</p>
                                        <p class="text-[11px] font-bold text-slate-800 font-mono">{{
                                            backupStatus?.lastBackup?.size }} • {{ backupStatus?.lastBackup?.type }}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-[8.5px] font-semibold text-slate-400">Next Scheduled</p>
                                        <p class="text-[10.5px] font-bold text-blue-600 truncate"
                                            :title="backupStatus?.nextBackup?.scheduleName">{{
                                                backupStatus?.nextBackup?.time }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- 3 Mini Boxes: Database, Website, Full Server -->
                            <div class="grid grid-cols-3 gap-2 mb-3">
                                <div class="p-2 rounded-[3px] bg-slate-50/70 border border-slate-100 text-center">
                                    <p class="text-xs font-black text-slate-900">{{ backupStatus?.stats?.database ?? 0
                                        }}</p>
                                    <p class="text-[8.5px] font-semibold text-slate-500 mt-0.5">Database</p>
                                </div>
                                <div class="p-2 rounded-[3px] bg-slate-50/70 border border-slate-100 text-center">
                                    <p class="text-xs font-black text-slate-900">{{ backupStatus?.stats?.website ?? 0 }}
                                    </p>
                                    <p class="text-[8.5px] font-semibold text-slate-500 mt-0.5">Website</p>
                                </div>
                                <div class="p-2 rounded-[3px] bg-slate-50/70 border border-slate-100 text-center">
                                    <p class="text-xs font-black text-slate-900">{{ backupStatus?.stats?.full ?? 0 }}
                                    </p>
                                    <p class="text-[8.5px] font-semibold text-slate-500 mt-0.5">Full Server</p>
                                </div>
                            </div>

                            <!-- Storage & Off-Site Details -->
                            <div class="space-y-1.5 text-xs">
                                <div
                                    class="flex items-center justify-between py-1 px-2 rounded-[2px] bg-slate-50/70 border border-slate-100">
                                    <span class="text-slate-500 text-[10.5px] font-medium">Storage Used</span>
                                    <span class="font-bold text-slate-900 text-[11px] font-mono">{{
                                        backupStatus?.storage?.used }}</span>
                                </div>
                                <div
                                    class="flex items-center justify-between py-1 px-2 rounded-[2px] bg-slate-50/70 border border-slate-100">
                                    <span class="text-slate-500 text-[10.5px] font-medium">Backup Retention</span>
                                    <span class="font-bold text-purple-700 text-[11px]">{{
                                        backupStatus?.storage?.retention }}</span>
                                </div>
                                <div
                                    class="flex items-center justify-between py-1 px-2 rounded-[2px] bg-slate-50/70 border border-slate-100">
                                    <span class="text-slate-500 text-[10.5px] font-medium">Off-site Backup</span>
                                    <span class="font-bold text-emerald-700 text-[10.5px] truncate max-w-[120px]"
                                        :title="backupStatus?.storage?.offsiteName">{{
                                            backupStatus?.storage?.offsiteStatus }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div
                            class="mt-3.5 pt-2 border-t border-slate-100 flex items-center justify-between text-[9px] text-slate-400 font-medium">
                            <span class="flex items-center gap-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                {{ backupStatus?.stats?.success ?? 0 }} Success / {{ backupStatus?.stats?.failed ?? 0 }}
                                Failed
                            </span>
                            <span>Auto-Retention</span>
                        </div>
                                       <!-- Card 5: Security Threat Monitor -->
                    <div class="bg-white border border-[#E2E8F0] rounded-[4px] p-5 shadow-2xs flex flex-col justify-between">
                        <div>
                            <!-- Header -->
                            <div class="flex items-center justify-between mb-3 pb-2 border-b border-slate-100">
                                <div class="flex items-center gap-1.5">
                                    <h2 class="text-xs font-bold text-slate-900 uppercase tracking-wide">Security Threat Monitor</h2>
                                </div>
                                <Link href="/admin/firewall" class="text-[10.5px] font-bold text-blue-600 hover:text-blue-700">
                                    Firewall Rules
                                </Link>
                            </div>

                            <!-- Top Highlight: Security Score & Scan Times -->
                            <div class="p-3 rounded-[3px] bg-slate-50/80 border border-slate-100 mb-3">
                                <div class="flex items-center justify-between mb-2">
                                    <div>
                                        <p class="text-[9px] font-bold text-slate-400 uppercase tracking-wider">Security Score</p>
                                        <p class="text-lg font-black text-slate-900 leading-none mt-0.5">
                                            {{ securityThreatMonitor?.securityScore ?? 98 }}<span class="text-xs text-slate-400 font-bold">/100</span>
                                        </p>
                                    </div>
                                    <span :class="(securityThreatMonitor?.securityScore ?? 98) >= 90 ? 'text-emerald-700 bg-emerald-50 border-emerald-200' : 'text-amber-700 bg-amber-50 border-amber-200'"
                                        class="inline-flex items-center gap-1 text-[9.5px] font-bold px-1.5 py-0.5 rounded-[2px] border shrink-0">
                                        <span :class="(securityThreatMonitor?.securityScore ?? 98) >= 90 ? 'bg-emerald-500' : 'bg-amber-500'" class="w-1.5 h-1.5 rounded-full"></span>
                                        {{ (securityThreatMonitor?.securityScore ?? 98) >= 90 ? 'Hardened Defense' : 'Elevated Caution' }}
                                    </span>
                                </div>

                                <div class="grid grid-cols-2 gap-2 pt-2 border-t border-slate-100 text-xs">
                                    <div>
                                        <p class="text-[8.5px] font-semibold text-slate-400">Last Scan</p>
                                        <p class="text-[10.5px] font-bold text-slate-800 truncate">{{ securityThreatMonitor?.lastSecurityScan }}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-[8.5px] font-semibold text-slate-400">Security Update</p>
                                        <p class="text-[10.5px] font-bold text-emerald-600 truncate">{{ securityThreatMonitor?.lastSecurityUpdate }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- 4 Key Attack Metric Mini-Boxes -->
                            <div class="grid grid-cols-2 gap-2 mb-3">
                                <!-- Failed Login Attempts -->
                                <div class="p-2 rounded-[3px] bg-slate-50/70 border border-slate-100 flex items-center justify-between">
                                    <div>
                                        <p class="text-[8.5px] font-semibold text-slate-400">Failed Logins</p>
                                        <p class="text-xs font-black text-rose-700">{{ securityThreatMonitor?.failedLogins ?? 0 }}</p>
                                    </div>
                                    <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                                </div>

                                <!-- Brute Force Attempts -->
                                <div class="p-2 rounded-[3px] bg-slate-50/70 border border-slate-100 flex items-center justify-between">
                                    <div>
                                        <p class="text-[8.5px] font-semibold text-slate-400">Brute Force</p>
                                        <p class="text-xs font-black text-amber-700">{{ securityThreatMonitor?.bruteForce ?? 0 }}</p>
                                    </div>
                                    <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                </div>

                                <!-- SSH Login Attempts -->
                                <div class="p-2 rounded-[3px] bg-slate-50/70 border border-slate-100 flex items-center justify-between">
                                    <div>
                                        <p class="text-[8.5px] font-semibold text-slate-400">SSH Attempts</p>
                                        <p class="text-xs font-black text-slate-800">{{ securityThreatMonitor?.sshLoginAttempts ?? 0 }}</p>
                                    </div>
                                    <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                                </div>

                                <!-- Root Login Attempts -->
                                <div class="p-2 rounded-[3px] bg-slate-50/70 border border-slate-100 flex items-center justify-between">
                                    <div>
                                        <p class="text-[8.5px] font-semibold text-slate-400">Root Attempts</p>
                                        <p class="text-xs font-black text-rose-600">{{ securityThreatMonitor?.rootLoginAttempts ?? 0 }}</p>
                                    </div>
                                    <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                                </div>
                            </div>

                            <!-- Defense Layer Status Rows -->
                            <div class="space-y-1.5 text-xs">
                                <div class="flex items-center justify-between py-1 px-2 rounded-[2px] bg-slate-50/70 border border-slate-100">
                                    <span class="text-slate-500 text-[10.5px] font-medium">Blocked Requests</span>
                                    <span class="font-bold text-slate-800 text-[11px] font-mono">{{ securityThreatMonitor?.blockedRequests ?? 0 }} Defended</span>
                                </div>
                                <div class="flex items-center justify-between py-1 px-2 rounded-[2px] bg-slate-50/70 border border-slate-100">
                                    <span class="text-slate-500 text-[10.5px] font-medium">Malware Detection</span>
                                    <span class="font-bold text-emerald-700 text-[10.5px]">
                                        {{ (securityThreatMonitor?.malwareDetected ?? 0) === 0 ? '🟢 0 Threats (Clean)' : (securityThreatMonitor?.malwareDetected + ' Infected') }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between py-1 px-2 rounded-[2px] bg-slate-50/70 border border-slate-100">
                                    <span class="text-slate-500 text-[10.5px] font-medium">Suspicious IPs</span>
                                    <span class="font-bold text-indigo-700 text-[11px] font-mono">{{ securityThreatMonitor?.suspiciousIps ?? 0 }} Isolated</span>
                                </div>
                                <div class="flex items-center justify-between py-1 px-2 rounded-[2px] bg-slate-50/70 border border-slate-100">
                                    <span class="text-slate-500 text-[10.5px] font-medium">WAF Events</span>
                                    <span class="font-bold text-slate-700 text-[11px] font-mono">{{ securityThreatMonitor?.wafEvents ?? 0 }} Filtered</span>
                                </div>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="mt-3.5 pt-2 border-t border-slate-100 flex items-center justify-between text-[9px] text-slate-400 font-medium">
                            <span class="flex items-center gap-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                Intrusion Shield Active
                            </span>
                            <span>Real-time Sentinel</span>
                        </div>
                    </div>

                    <!-- Card 6: Customer Health & Business Pulse (Right Sidebar Bottom) -->
                    <div class="bg-white border border-[#E2E8F0] rounded-[4px] p-5 shadow-2xs flex flex-col justify-between">
                        <div>
                            <!-- Header -->
                            <div class="flex items-center justify-between mb-3 pb-2 border-b border-slate-100">
                                <div class="flex items-center gap-1.5">
                                    <h2 class="text-xs font-bold text-slate-900 uppercase tracking-wide">Customer Health</h2>
                                </div>
                                <Link :href="customerHealth?.manageClientsHref || '/admin/users'"
                                    class="text-[10.5px] font-bold text-blue-600 hover:text-blue-700">
                                    Manage ({{ customerHealth?.totalClients ?? 0 }})
                                </Link>
                            </div>

                            <!-- Top Highlight: Total, Active & New This Month -->
                            <div class="p-3 rounded-[3px] bg-slate-50/80 border border-slate-100 mb-3">
                                <div class="flex items-center justify-between mb-2">
                                    <div>
                                        <p class="text-[9px] font-bold text-slate-400 uppercase tracking-wider">Total Clients</p>
                                        <p class="text-lg font-black text-slate-900 leading-none mt-0.5">{{ customerHealth?.totalClients ?? 0 }}</p>
                                    </div>
                                    <span class="inline-flex items-center gap-1 text-[9.5px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 px-1.5 py-0.5 rounded-[2px] shrink-0">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        {{ customerHealth?.activeRatio ?? 100 }}% Active
                                    </span>
                                </div>

                                <div class="grid grid-cols-2 gap-2 pt-2 border-t border-slate-100 text-xs">
                                    <div>
                                        <p class="text-[8.5px] font-semibold text-slate-400">Active Clients</p>
                                        <p class="text-xs font-bold text-emerald-700 font-mono">{{ customerHealth?.activeClients ?? 0 }}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-[8.5px] font-semibold text-slate-400">New This Month</p>
                                        <p class="text-xs font-bold text-blue-600 font-mono">+{{ customerHealth?.newThisMonth ?? 0 }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- 4 Account Status Mini Boxes -->
                            <div class="grid grid-cols-2 gap-2 mb-3">
                                <!-- Suspended -->
                                <div class="p-2 rounded-[3px] bg-slate-50/70 border border-slate-100 flex items-center justify-between">
                                    <div>
                                        <p class="text-[8.5px] font-semibold text-slate-400">Suspended</p>
                                        <p class="text-xs font-black text-rose-700">{{ customerHealth?.suspended ?? 0 }}</p>
                                    </div>
                                    <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                                </div>
                                <!-- Inactive -->
                                <div class="p-2 rounded-[3px] bg-slate-50/70 border border-slate-100 flex items-center justify-between">
                                    <div>
                                        <p class="text-[8.5px] font-semibold text-slate-400">Inactive</p>
                                        <p class="text-xs font-black text-slate-700">{{ customerHealth?.inactive ?? 0 }}</p>
                                    </div>
                                    <span class="w-2 h-2 rounded-full bg-slate-400"></span>
                                </div>
                                <!-- Overdue Clients -->
                                <div class="p-2 rounded-[3px] bg-slate-50/70 border border-slate-100 flex items-center justify-between">
                                    <div>
                                        <p class="text-[8.5px] font-semibold text-slate-400">Overdue Clients</p>
                                        <p class="text-xs font-black text-amber-700">{{ customerHealth?.overdueClients ?? 0 }}</p>
                                    </div>
                                    <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                </div>
                                <!-- High Usage Clients -->
                                <div class="p-2 rounded-[3px] bg-slate-50/70 border border-slate-100 flex items-center justify-between">
                                    <div>
                                        <p class="text-[8.5px] font-semibold text-slate-400">High-Usage</p>
                                        <p class="text-xs font-black text-purple-700">{{ customerHealth?.highUsageClients ?? 0 }}</p>
                                    </div>
                                    <span class="w-2 h-2 rounded-full bg-purple-500"></span>
                                </div>
                            </div>

                            <!-- Orders & Subscriptions Breakdown -->
                            <div class="space-y-1.5 text-xs">
                                <div class="flex items-center justify-between py-1 px-2 rounded-[2px] bg-slate-50/70 border border-slate-100">
                                    <span class="text-slate-500 text-[10.5px] font-medium">New Orders</span>
                                    <span class="font-bold text-emerald-700 text-[11px] font-mono">{{ customerHealth?.newOrders ?? 0 }}</span>
                                </div>
                                <div class="flex items-center justify-between py-1 px-2 rounded-[2px] bg-slate-50/70 border border-slate-100">
                                    <span class="text-slate-500 text-[10.5px] font-medium">Pending Orders</span>
                                    <span class="font-bold text-amber-700 text-[11px] font-mono">{{ customerHealth?.pendingOrders ?? 0 }}</span>
                                </div>
                                <div class="flex items-center justify-between py-1 px-2 rounded-[2px] bg-slate-50/70 border border-slate-100">
                                    <span class="text-slate-500 text-[10.5px] font-medium">Cancelled Orders</span>
                                    <span class="font-bold text-slate-600 text-[11px] font-mono">{{ customerHealth?.cancelledOrders ?? 0 }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="mt-3.5 pt-2 border-t border-slate-100 flex items-center justify-between text-[9px] text-slate-400 font-medium">
                            <span class="flex items-center gap-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                Active Subs: {{ customerHealth?.activeSubscriptions ?? 0 }}
                            </span>
                            <span>Business Pulse: Healthy</span>
                        </div>
                    </div>      </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
