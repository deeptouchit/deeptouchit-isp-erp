<script setup>
import { ref, computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import { 
    GlobeAltIcon, 
    CircleStackIcon, 
    ShieldCheckIcon, 
    BanknotesIcon,
    PlusIcon,
    FolderIcon,
    ServerIcon,
    EnvelopeIcon,
    KeyIcon,
    LifebuoyIcon,
    ArrowTopRightOnSquareIcon,
    ArrowPathIcon,
    CpuChipIcon,
    DocumentTextIcon,
    CreditCardIcon,
    ClipboardDocumentIcon,
    CheckIcon,
    CommandLineIcon,
    ArrowUpCircleIcon,
    ClockIcon,
    LockClosedIcon,
    WrenchScrewdriverIcon,
    ArrowRightIcon,
    ExclamationTriangleIcon,
    ChevronRightIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    user: {
        type: Object,
        default: () => ({}),
    },
    activeSubscription: {
        type: Object,
        default: null,
    },
    subscriptions: {
        type: Array,
        default: () => [],
    },
    websites: {
        type: Array,
        default: () => [],
    },
    databases: {
        type: Array,
        default: () => [],
    },
    serverInfo: {
        type: Object,
        default: () => ({
            public_ip: '103.59.177.138',
            primary_ns: 'ns1.deeptouchit.com',
            secondary_ns: 'ns2.deeptouchit.com',
            hostname: 'node1.deeptouchit.com'
        })
    },
    stats: {
        type: Object,
        default: () => ({
            websites_count: 0,
            databases_count: 0,
            emails_count: 0,
            ftp_count: 0,
            unpaid_invoices_count: 0,
            unpaid_amount: 0,
            open_tickets_count: 0,
            disk_used_mb: 0,
            disk_limit_mb: 5120,
            disk_usage_percent: 0,
        }),
    },
    recentInvoices: {
        type: Array,
        default: () => [],
    },
    recentTickets: {
        type: Array,
        default: () => [],
    },
})

// Clipboard feedback
const copiedItem = ref('')
const copyToClipboard = (text, label) => {
    navigator.clipboard.writeText(text)
    copiedItem.value = label
    setTimeout(() => {
        copiedItem.value = ''
    }, 2000)
}

const formatCurrency = (amount) => {
    return Number(amount || 0).toLocaleString('en-BD', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    })
}

const formatDate = (dateStr) => {
    if (!dateStr) return 'N/A'
    if (typeof dateStr === 'string' && dateStr.length <= 12 && dateStr.includes(',')) {
        return dateStr
    }
    try {
        const d = new Date(dateStr)
        if (isNaN(d.getTime())) return dateStr
        return d.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' })
    } catch {
        return String(dateStr).substring(0, 10)
    }
}

// Quick tools directory
const quickTools = [
    {
        title: 'WordPress Install',
        desc: '1-Click Auto Installer',
        icon: GlobeAltIcon,
        href: route('website.wordpress'),
        color: 'text-blue-600 bg-blue-50 border-blue-100',
    },
    {
        title: 'File Manager',
        desc: 'Browse & Edit Code',
        icon: FolderIcon,
        href: route('file.browse'),
        color: 'text-amber-600 bg-amber-50 border-amber-100',
    },
    {
        title: 'phpMyAdmin',
        desc: 'Direct SSO SQL Access',
        icon: CircleStackIcon,
        href: route('databases.sso'),
        target: '_blank',
        color: 'text-purple-600 bg-purple-50 border-purple-100',
    },
    {
        title: 'SSL Security',
        desc: "Free Let's Encrypt",
        icon: ShieldCheckIcon,
        href: route('security.ssl'),
        color: 'text-emerald-600 bg-emerald-50 border-emerald-100',
    },
    {
        title: 'Auto Backups',
        desc: 'Snapshots & Restore',
        icon: ServerIcon,
        href: route('files.backups'),
        color: 'text-indigo-600 bg-indigo-50 border-indigo-100',
    },
    {
        title: 'FTP Accounts',
        desc: 'Locked Directory FTP',
        icon: KeyIcon,
        href: route('ftp-accounts.index'),
        color: 'text-teal-600 bg-teal-50 border-teal-100',
    },
    {
        title: 'DNS Zone Editor',
        desc: 'A, CNAME & MX Records',
        icon: CommandLineIcon,
        href: route('advanced.dns'),
        color: 'text-rose-600 bg-rose-50 border-rose-100',
    },
    {
        title: 'Resource Telemetry',
        desc: 'CPU, RAM & Inodes',
        icon: CpuChipIcon,
        href: route('hosting.resources'),
        color: 'text-sky-600 bg-sky-50 border-sky-100',
    },
]
</script>

<template>
    <Head title="Client Dashboard - DeepTouch Host" />

    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto space-y-4">
            
            <!-- 1. Smart Minimal Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Client Portal', href: '#' },
                    { label: 'Executive Dashboard' }
                ]"
                :show-refresh="true"
                @refreshed="router.reload({ preserveScroll: true })"
            >
                <template #actions>
                    <Link 
                        :href="route('websites.create')" 
                        class="px-2.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold flex items-center gap-1.5 shadow-2xs transition cursor-pointer"
                    >
                        <PlusIcon class="w-3.5 h-3.5 stroke-[2.5]" />
                        <span>Add Website</span>
                    </Link>

                    <Link 
                        :href="route('tickets.create')" 
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-[3px] text-xs font-bold flex items-center gap-1.5 transition cursor-pointer shadow-2xs"
                    >
                        <LifebuoyIcon class="w-3.5 h-3.5 text-slate-500" />
                        <span>New Ticket</span>
                    </Link>
                </template>
            </PageHeader>

            <!-- 2. Active Primary Hosting Overview Card (Hostinger / cPanel Enterprise Architecture) -->
            <div v-if="activeSubscription" class="bg-white rounded-lg border border-slate-200 p-4 sm:p-5 shadow-2xs space-y-4">
                <!-- Top Row: Plan info & Quick Actions -->
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3 pb-3.5 border-b border-slate-100">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-md bg-blue-50 border border-blue-200/80 text-blue-600 flex items-center justify-center shrink-0">
                            <ServerIcon class="w-5 h-5 stroke-[2.2]" />
                        </div>
                        <div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <h2 class="text-sm sm:text-base font-bold text-slate-900 tracking-tight">
                                    {{ activeSubscription.plan?.name || 'High-Performance NVMe Cloud' }}
                                </h2>
                                <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-[3px] bg-emerald-50 text-emerald-700 border border-emerald-200 uppercase tracking-wide">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                    <span>{{ activeSubscription.status }}</span>
                                </span>
                            </div>
                            <div class="flex items-center gap-2 text-xs text-slate-500 font-mono mt-0.5 flex-wrap">
                                <span>Domain: <strong class="text-slate-800">{{ activeSubscription.domain }}</strong></span>
                                <span class="text-slate-300">•</span>
                                <span>User: <strong class="text-slate-800">{{ activeSubscription.username }}</strong></span>
                                <span class="text-slate-300">•</span>
                                <span>Node: <strong class="text-slate-600">{{ serverInfo.hostname }}</strong></span>
                            </div>
                        </div>
                    </div>

                    <!-- Top Right Quick Action Buttons -->
                    <div class="flex items-center gap-2 flex-wrap self-start lg:self-center">
                        <Link 
                            :href="route('file.browse')" 
                            class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-semibold rounded-[3px] text-xs border border-slate-200 shadow-2xs transition flex items-center gap-1.5"
                        >
                            <FolderIcon class="w-3.5 h-3.5 text-slate-500" />
                            <span>File Manager</span>
                        </Link>
                        <Link 
                            :href="route('databases.index')" 
                            class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-semibold rounded-[3px] text-xs border border-slate-200 shadow-2xs transition flex items-center gap-1.5"
                        >
                            <CircleStackIcon class="w-3.5 h-3.5 text-slate-500" />
                            <span>Databases</span>
                        </Link>
                        <a 
                            :href="route('databases.sso')" 
                            target="_blank" 
                            class="px-2.5 py-1.5 bg-purple-50 hover:bg-purple-100 text-purple-700 font-semibold rounded-[3px] text-xs border border-purple-200 shadow-2xs transition flex items-center gap-1.5"
                        >
                            <span>phpMyAdmin</span>
                            <ArrowTopRightOnSquareIcon class="w-3.5 h-3.5 text-purple-500" />
                        </a>
                        <Link 
                            :href="route('hosting.upgrade')" 
                            class="px-2.5 py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-800 font-semibold rounded-[3px] text-xs border border-amber-200 shadow-2xs transition flex items-center gap-1.5"
                        >
                            <ArrowUpCircleIcon class="w-3.5 h-3.5 text-amber-600" />
                            <span>Upgrade</span>
                        </Link>
                    </div>
                </div>

                <!-- Bottom 3 Balanced Structured Micro-Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3.5">
                    
                    <!-- Tile 1: Storage Quota -->
                    <div class="p-3.5 rounded-md bg-slate-50 border border-slate-200/80 flex flex-col justify-between space-y-2.5">
                        <div class="flex items-center justify-between pb-1.5 border-b border-slate-200/60">
                            <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                                <FolderIcon class="w-3.5 h-3.5 text-blue-600 shrink-0" />
                                <span>Disk Storage Usage</span>
                            </span>
                            <span class="text-xs font-mono font-bold text-slate-900">
                                {{ stats.disk_used_mb }} MB / {{ Math.round(stats.disk_limit_mb / 1024) }} GB
                            </span>
                        </div>
                        <div class="w-full bg-slate-200/80 h-2 rounded-full overflow-hidden">
                            <div 
                                class="bg-blue-600 h-full rounded-full transition-all duration-500" 
                                :style="{ width: `${Math.max(4, stats.disk_usage_percent)}%` }"
                            ></div>
                        </div>
                        <div class="flex items-center justify-between text-[11px] text-slate-500">
                            <span class="font-medium text-slate-600">NVMe High-Speed Array</span>
                            <span class="font-bold text-blue-700 font-mono">{{ stats.disk_usage_percent }}% Used</span>
                        </div>
                    </div>

                    <!-- Tile 2: Nameservers & Routing -->
                    <div class="p-3.5 rounded-md bg-slate-50 border border-slate-200/80 flex flex-col justify-between space-y-2">
                        <div class="flex items-center justify-between pb-1.5 border-b border-slate-200/60">
                            <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                                <GlobeAltIcon class="w-3.5 h-3.5 text-indigo-600 shrink-0" />
                                <span>DNS & Nameservers</span>
                            </span>
                            <span class="text-[10px] font-bold text-indigo-700 uppercase bg-indigo-50 px-1.5 py-0.5 rounded-[3px] border border-indigo-200">
                                Active
                            </span>
                        </div>
                        <div class="text-[11px] space-y-1 font-mono">
                            <div class="flex items-center justify-between">
                                <span class="text-slate-500">Primary NS:</span>
                                <span class="font-bold text-slate-800">{{ serverInfo.primary_ns }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-slate-500">Secondary NS:</span>
                                <span class="font-bold text-slate-800">{{ serverInfo.secondary_ns }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-slate-500">Server IP:</span>
                                <span class="font-bold text-slate-800">{{ serverInfo.public_ip }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Tile 3: PHP Runtime & SSL Security -->
                    <div class="p-3.5 rounded-md bg-slate-50 border border-slate-200/80 flex flex-col justify-between space-y-2">
                        <div class="flex items-center justify-between pb-1.5 border-b border-slate-200/60">
                            <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                                <CpuChipIcon class="w-3.5 h-3.5 text-emerald-600 shrink-0" />
                                <span>Runtime & Security</span>
                            </span>
                            <span class="text-[10px] font-bold text-emerald-700 uppercase bg-emerald-50 px-1.5 py-0.5 rounded-[3px] border border-emerald-200">
                                Protected
                            </span>
                        </div>
                        <div class="text-[11px] space-y-1">
                            <div class="flex items-center justify-between">
                                <span class="text-slate-500">PHP Socket:</span>
                                <span class="font-mono font-bold text-slate-800">PHP {{ activeSubscription.php_version || '8.2' }} FPM</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-slate-500">SSL Certificate:</span>
                                <span class="font-semibold text-emerald-600 flex items-center gap-1">
                                    <ShieldCheckIcon class="w-3 h-3 text-emerald-600 shrink-0" />
                                    <span>Let's Encrypt Active</span>
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-slate-500">Renewal Due:</span>
                                <span class="text-slate-700 font-medium">{{ activeSubscription.expires_at ? formatDate(activeSubscription.expires_at) : 'Auto-Renew Active' }}</span>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- 3. Key Performance Metric Cards (4-Column Grid) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                
                <!-- Card 1: Websites -->
                <Link 
                    :href="route('websites.index')" 
                    class="bg-white rounded-lg border border-slate-200 p-4 sm:p-5 shadow-2xs hover:border-blue-300 hover:bg-slate-50/40 transition flex items-center justify-between group cursor-pointer"
                >
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Hosted Websites</p>
                        <p class="text-2xl font-black text-slate-900 mt-1">{{ stats.websites_count }}</p>
                        <span class="text-[11px] text-blue-600 font-bold mt-1 flex items-center gap-1 group-hover:underline">
                            <span>Manage Domains</span>
                            <ChevronRightIcon class="w-3 h-3 stroke-[2.5]" />
                        </span>
                    </div>
                    <div class="w-11 h-11 rounded-lg bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center shrink-0">
                        <GlobeAltIcon class="w-5 h-5" />
                    </div>
                </Link>

                <!-- Card 2: Databases -->
                <Link 
                    :href="route('databases.index')" 
                    class="bg-white rounded-lg border border-slate-200 p-4 sm:p-5 shadow-2xs hover:border-purple-300 hover:bg-slate-50/40 transition flex items-center justify-between group cursor-pointer"
                >
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500">MySQL Databases</p>
                        <p class="text-2xl font-black text-slate-900 mt-1">{{ stats.databases_count }}</p>
                        <span class="text-[11px] text-purple-600 font-bold mt-1 flex items-center gap-1 group-hover:underline">
                            <span>Manage Databases</span>
                            <ChevronRightIcon class="w-3 h-3 stroke-[2.5]" />
                        </span>
                    </div>
                    <div class="w-11 h-11 rounded-lg bg-purple-50 text-purple-600 border border-purple-100 flex items-center justify-center shrink-0">
                        <CircleStackIcon class="w-5 h-5" />
                    </div>
                </Link>

                <!-- Card 3: Email Accounts -->
                <Link 
                    :href="route('email-accounts.index')" 
                    class="bg-white rounded-lg border border-slate-200 p-4 sm:p-5 shadow-2xs hover:border-emerald-300 hover:bg-slate-50/40 transition flex items-center justify-between group cursor-pointer"
                >
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Email Mailboxes</p>
                        <p class="text-2xl font-black text-slate-900 mt-1">{{ stats.emails_count }}</p>
                        <span class="text-[11px] text-emerald-600 font-bold mt-1 flex items-center gap-1 group-hover:underline">
                            <span>Open Webmail</span>
                            <ChevronRightIcon class="w-3 h-3 stroke-[2.5]" />
                        </span>
                    </div>
                    <div class="w-11 h-11 rounded-lg bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center shrink-0">
                        <EnvelopeIcon class="w-5 h-5" />
                    </div>
                </Link>

                <!-- Card 4: Billing & Invoices -->
                <Link 
                    :href="route('billing.invoices')" 
                    class="bg-white rounded-lg border border-slate-200 p-4 sm:p-5 shadow-2xs hover:border-amber-300 hover:bg-slate-50/40 transition flex items-center justify-between group cursor-pointer"
                >
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Outstanding Due</p>
                        <p class="text-2xl font-black text-slate-900 mt-1">৳{{ formatCurrency(stats.unpaid_amount) }}</p>
                        <span 
                            class="text-[11px] font-bold mt-1 flex items-center gap-1 group-hover:underline"
                            :class="stats.unpaid_invoices_count > 0 ? 'text-rose-600' : 'text-emerald-600'"
                        >
                            <span>{{ stats.unpaid_invoices_count > 0 ? `${stats.unpaid_invoices_count} Due Invoices (Pay)` : 'All Paid Up' }}</span>
                            <ChevronRightIcon class="w-3 h-3 stroke-[2.5]" />
                        </span>
                    </div>
                    <div class="w-11 h-11 rounded-lg bg-amber-50 text-amber-600 border border-amber-100 flex items-center justify-center shrink-0">
                        <BanknotesIcon class="w-5 h-5" />
                    </div>
                </Link>

            </div>

            <!-- 4. Quick-Launch Utilities Grid (Hostinger hPanel Architecture) -->
            <div class="bg-white rounded-lg border border-slate-200 p-5 shadow-2xs space-y-3.5">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-2">
                        <WrenchScrewdriverIcon class="w-4 h-4 text-blue-600" />
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800">Quick-Launch Hosting Tools</h3>
                    </div>
                    <span class="text-[11px] text-slate-400 font-medium">1-Click Fast Utilities</span>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-3">
                    <component 
                        :is="tool.target ? 'a' : Link"
                        v-for="tool in quickTools" 
                        :key="tool.title"
                        :href="tool.href"
                        :target="tool.target || null"
                        class="flex flex-col items-center text-center p-3 rounded-lg border border-slate-200 hover:border-blue-400 hover:bg-slate-50 transition shadow-2xs group cursor-pointer"
                    >
                        <div :class="['w-9 h-9 rounded-md flex items-center justify-center mb-2 border transition group-hover:scale-105', tool.color]">
                            <component :is="tool.icon" class="w-4 h-4" />
                        </div>
                        <span class="text-xs font-bold text-slate-800 line-clamp-1 group-hover:text-blue-600">{{ tool.title }}</span>
                        <span class="text-[10px] text-slate-400 line-clamp-1 mt-0.5">{{ tool.desc }}</span>
                    </component>
                </div>
            </div>

            <!-- 5. Two-Column Operations Layout (Websites Management & Activity Feed) -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                
                <!-- Left 2-Cols: Hosted Websites & VirtualHosts Table -->
                <div class="lg:col-span-2 bg-white rounded-lg border border-slate-200 shadow-2xs overflow-hidden flex flex-col justify-between">
                    <div>
                        <div class="px-5 py-3.5 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                            <div class="flex items-center gap-2">
                                <GlobeAltIcon class="w-4 h-4 text-blue-600" />
                                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800">Hosted Websites & VirtualHosts</h3>
                            </div>
                            <Link 
                                :href="route('websites.create')" 
                                class="text-xs font-bold text-blue-600 hover:text-blue-700 flex items-center gap-1"
                            >
                                <PlusIcon class="w-3.5 h-3.5 stroke-[2.5]" />
                                <span>Add Website</span>
                            </Link>
                        </div>

                        <div v-if="websites.length > 0" class="overflow-x-auto">
                            <table class="w-full text-left text-xs whitespace-nowrap">
                                <thead class="bg-slate-50 text-slate-600 font-bold border-b border-slate-200">
                                    <tr>
                                        <th class="py-2.5 px-4">Domain Name</th>
                                        <th class="py-2.5 px-4">Engine</th>
                                        <th class="py-2.5 px-4">SSL Security</th>
                                        <th class="py-2.5 px-4">Document Root</th>
                                        <th class="py-2.5 px-4 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 text-slate-700">
                                    <tr v-for="site in websites" :key="site.id" class="hover:bg-slate-50/70 transition">
                                        <td class="py-3 px-4 font-bold text-slate-900">
                                            <div class="flex items-center gap-2">
                                                <a 
                                                    :href="`https://${site.domain}`" 
                                                    target="_blank" 
                                                    class="hover:text-blue-600 hover:underline flex items-center gap-1 font-bold text-slate-900"
                                                >
                                                    <span>{{ site.domain }}</span>
                                                    <ArrowTopRightOnSquareIcon class="w-3.5 h-3.5 text-slate-400" />
                                                </a>
                                                <span v-if="site.is_primary" class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-blue-50 text-blue-700 border border-blue-200 uppercase">
                                                    Primary
                                                </span>
                                            </div>
                                        </td>
                                        <td class="py-3 px-4 font-mono text-[11px] text-slate-600">
                                            PHP {{ site.php_version || '8.2' }}
                                        </td>
                                        <td class="py-3 px-4">
                                            <span class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                                                <ShieldCheckIcon class="w-3.5 h-3.5 text-emerald-600" />
                                                <span>Active</span>
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 font-mono text-[11px] text-slate-500 truncate max-w-[160px]">
                                            /public_html
                                        </td>
                                        <td class="py-3 px-4 text-right space-x-1.5">
                                            <Link 
                                                :href="route('file.browse')" 
                                                class="px-2 py-1 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 font-bold rounded-[3px] text-xs transition shadow-2xs"
                                            >
                                                Files
                                            </Link>
                                            <Link 
                                                :href="route('websites.show', site.id)" 
                                                class="px-2 py-1 bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 font-bold rounded-[3px] text-xs transition shadow-2xs"
                                            >
                                                Manage
                                            </Link>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div v-else class="text-center py-12 px-4">
                            <GlobeAltIcon class="w-10 h-10 text-slate-300 mx-auto mb-2" />
                            <p class="text-xs font-bold text-slate-700">No websites added yet</p>
                            <p class="text-[11px] text-slate-500 mt-0.5">Click below to provision your first virtual host.</p>
                            <Link 
                                :href="route('websites.create')" 
                                class="mt-3 inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 text-white rounded-[3px] text-xs font-bold hover:bg-blue-700 transition shadow-2xs"
                            >
                                <PlusIcon class="w-3.5 h-3.5" />
                                <span>Add Website</span>
                            </Link>
                        </div>
                    </div>

                    <div class="px-5 py-2.5 border-t border-slate-100 bg-slate-50/50 flex items-center justify-between text-[11px] text-slate-500 font-medium">
                        <span>Showing {{ websites.length }} of {{ stats.websites_count }} provisioned vHosts</span>
                        <Link :href="route('websites.index')" class="font-bold text-blue-600 hover:underline">
                            View All Websites →
                        </Link>
                    </div>
                </div>

                <!-- Right 1-Col: Server Connection Info & Activity Feed -->
                <div class="space-y-4">
                    
                    <!-- Server & DNS Quick Copy Box -->
                    <div class="bg-white rounded-lg border border-slate-200 p-4 shadow-2xs space-y-3">
                        <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-800 flex items-center gap-1.5">
                                <ServerIcon class="w-3.5 h-3.5 text-blue-600" />
                                <span>DNS & Server Credentials</span>
                            </span>
                        </div>

                        <div class="space-y-2 text-xs">
                            <!-- Server IP -->
                            <div class="p-2 rounded bg-slate-50 border border-slate-200 flex items-center justify-between">
                                <div>
                                    <span class="text-[10px] uppercase font-bold text-slate-400 block">Server Public IP (A Record)</span>
                                    <span class="font-mono font-bold text-slate-800">{{ serverInfo.public_ip }}</span>
                                </div>
                                <button 
                                    @click="copyToClipboard(serverInfo.public_ip, 'ip')" 
                                    class="p-1 rounded text-slate-400 hover:text-slate-600 transition cursor-pointer"
                                    title="Copy IP"
                                >
                                    <CheckIcon v-if="copiedItem === 'ip'" class="w-3.5 h-3.5 text-emerald-600" />
                                    <ClipboardDocumentIcon v-else class="w-3.5 h-3.5" />
                                </button>
                            </div>

                            <!-- Primary NS -->
                            <div class="p-2 rounded bg-slate-50 border border-slate-200 flex items-center justify-between">
                                <div>
                                    <span class="text-[10px] uppercase font-bold text-slate-400 block">Primary Nameserver</span>
                                    <span class="font-mono font-bold text-indigo-700">{{ serverInfo.primary_ns }}</span>
                                </div>
                                <button 
                                    @click="copyToClipboard(serverInfo.primary_ns, 'ns1')" 
                                    class="p-1 rounded text-slate-400 hover:text-slate-600 transition cursor-pointer"
                                    title="Copy NS1"
                                >
                                    <CheckIcon v-if="copiedItem === 'ns1'" class="w-3.5 h-3.5 text-emerald-600" />
                                    <ClipboardDocumentIcon v-else class="w-3.5 h-3.5" />
                                </button>
                            </div>

                            <!-- Secondary NS -->
                            <div class="p-2 rounded bg-slate-50 border border-slate-200 flex items-center justify-between">
                                <div>
                                    <span class="text-[10px] uppercase font-bold text-slate-400 block">Secondary Nameserver</span>
                                    <span class="font-mono font-bold text-indigo-700">{{ serverInfo.secondary_ns }}</span>
                                </div>
                                <button 
                                    @click="copyToClipboard(serverInfo.secondary_ns, 'ns2')" 
                                    class="p-1 rounded text-slate-400 hover:text-slate-600 transition cursor-pointer"
                                    title="Copy NS2"
                                >
                                    <CheckIcon v-if="copiedItem === 'ns2'" class="w-3.5 h-3.5 text-emerald-600" />
                                    <ClipboardDocumentIcon v-else class="w-3.5 h-3.5" />
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Invoices Summary -->
                    <div class="bg-white rounded-lg border border-slate-200 p-4 shadow-2xs space-y-3">
                        <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-800 flex items-center gap-1.5">
                                <CreditCardIcon class="w-3.5 h-3.5 text-amber-600" />
                                <span>Recent Invoices</span>
                            </span>
                            <Link :href="route('billing.invoices')" class="text-[11px] font-bold text-blue-600 hover:underline">
                                View All
                            </Link>
                        </div>

                        <div v-if="recentInvoices.length > 0" class="space-y-2 text-xs">
                            <div 
                                v-for="inv in recentInvoices.slice(0, 3)" 
                                :key="inv.id" 
                                class="p-2.5 rounded bg-slate-50 border border-slate-200 flex items-center justify-between"
                            >
                                <div>
                                    <span class="font-mono font-bold text-slate-800 block">#{{ inv.invoice_no }}</span>
                                    <span class="text-[10px] text-slate-500">৳{{ formatCurrency(inv.total_amount) }} • {{ formatDate(inv.issue_date) }}</span>
                                </div>
                                <div>
                                    <Link 
                                        v-if="inv.status === 'unpaid'"
                                        :href="route('billing.invoice.show', inv.id)" 
                                        class="px-2 py-1 bg-rose-600 hover:bg-rose-700 text-white rounded-[3px] text-[10px] font-bold shadow-2xs transition"
                                    >
                                        Pay Due
                                    </Link>
                                    <span 
                                        v-else 
                                        class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 uppercase"
                                    >
                                        Paid
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div v-else class="text-center py-4 text-xs text-slate-400">
                            No recent invoices found.
                        </div>
                    </div>

                    <!-- Recent Support Tickets -->
                    <div class="bg-white rounded-lg border border-slate-200 p-4 shadow-2xs space-y-3">
                        <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-800 flex items-center gap-1.5">
                                <LifebuoyIcon class="w-3.5 h-3.5 text-blue-600" />
                                <span>Support Tickets</span>
                            </span>
                            <Link :href="route('tickets.index')" class="text-[11px] font-bold text-blue-600 hover:underline">
                                View All
                            </Link>
                        </div>

                        <div v-if="recentTickets.length > 0" class="space-y-2 text-xs">
                            <Link 
                                v-for="ticket in recentTickets.slice(0, 3)" 
                                :key="ticket.id" 
                                :href="route('tickets.show', ticket.id)"
                                class="p-2.5 rounded bg-slate-50 border border-slate-200 flex items-center justify-between hover:bg-blue-50/50 hover:border-blue-200 transition group block"
                            >
                                <div class="min-w-0 pr-2">
                                    <span class="font-bold text-slate-900 group-hover:text-blue-600 truncate block">{{ ticket.subject }}</span>
                                    <span class="text-[10px] text-slate-400 font-mono">#{{ ticket.ticket_no }}</span>
                                </div>
                                <span 
                                    class="px-2 py-0.5 rounded text-[10px] font-bold border capitalize shrink-0"
                                    :class="[
                                        ticket.status === 'open' || ticket.status === 'waiting' ? 'bg-amber-50 text-amber-700 border-amber-200' :
                                        ticket.status === 'answered' ? 'bg-violet-50 text-violet-700 border-violet-200' :
                                        'bg-slate-100 text-slate-600 border-slate-200'
                                    ]"
                                >
                                    {{ ticket.status }}
                                </span>
                            </Link>
                        </div>

                        <div v-else class="text-center py-4 text-xs text-slate-400">
                            No active tickets. 24/7 support is ready.
                        </div>
                    </div>

                </div>

            </div>

        </div>
    </AuthenticatedLayout>
</template>
