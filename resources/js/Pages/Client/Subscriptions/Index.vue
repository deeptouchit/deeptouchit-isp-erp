<script setup>
import { ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import { 
    ServerIcon, 
    ArrowRightIcon, 
    GlobeAltIcon, 
    CircleStackIcon, 
    EnvelopeIcon, 
    FolderIcon, 
    PlusIcon, 
    CheckIcon, 
    ClipboardDocumentIcon, 
    ClockIcon, 
    ShieldCheckIcon, 
    ArrowTopRightOnSquareIcon, 
    CpuChipIcon, 
    MapPinIcon, 
    ArchiveBoxIcon, 
    CommandLineIcon,
    ArrowUpCircleIcon,
    ArrowPathIcon,
    SparklesIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    subscriptions: {
        type: Array,
        default: () => []
    },
    metrics: {
        type: Object,
        default: () => ({
            total_active: 0,
            monthly_commitment: 0,
            yearly_commitment: 0,
            next_renewal: null
        })
    },
    serverInfo: {
        type: Object,
        default: () => ({
            public_ip: '103.59.177.138',
            primary_ns: 'ns1.deeptouchit.com',
            secondary_ns: 'ns2.deeptouchit.com'
        })
    }
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
</script>

<template>
    <Head title="Hosting Subscriptions - DeepTouch Host" />

    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto space-y-4">
            
            <!-- 1. Smart Minimal Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Hosting Plan', href: '#' },
                    { label: 'Subscriptions & Packages' }
                ]"
                :show-refresh="true"
                @refreshed="router.reload({ preserveScroll: true })"
            >
                <template #actions>
                    <Link 
                        :href="route('public.shared')" 
                        class="px-2.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold flex items-center gap-1.5 shadow-2xs transition cursor-pointer"
                    >
                        <PlusIcon class="w-3.5 h-3.5 stroke-[2.5]" />
                        <span>Order New Package</span>
                    </Link>
                </template>
            </PageHeader>

            <!-- 2. Top Analytics & Infrastructure Overview Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                
                <!-- Card 1: Active Services -->
                <div class="bg-white rounded-lg border border-slate-200 p-4 sm:p-5 shadow-2xs flex items-center justify-between">
                    <div>
                        <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">Active Hosting Plans</span>
                        <span class="text-2xl font-black text-slate-900 mt-1 block">{{ metrics.total_active }}</span>
                        <span class="text-[11px] text-emerald-600 font-bold flex items-center gap-1 mt-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            <span>High-Performance NVMe Ready</span>
                        </span>
                    </div>
                    <div class="w-11 h-11 rounded-md bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center shrink-0">
                        <ServerIcon class="w-5 h-5 stroke-[2.2]" />
                    </div>
                </div>

                <!-- Card 2: Recurring Billing Summary -->
                <div class="bg-white rounded-lg border border-slate-200 p-4 sm:p-5 shadow-2xs flex flex-col justify-between space-y-3">
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">Billing Commitment</span>
                            <span class="text-xl font-black text-slate-900 mt-1 block">
                                ৳{{ formatCurrency(metrics.monthly_commitment > 0 ? metrics.monthly_commitment : metrics.yearly_commitment) }}
                                <span class="text-xs text-slate-500 font-normal">/ {{ metrics.monthly_commitment > 0 ? 'mo' : 'yr' }}</span>
                            </span>
                        </div>
                        <div class="w-10 h-10 rounded-md bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center shrink-0">
                            <ClockIcon class="w-5 h-5" />
                        </div>
                    </div>

                    <div class="flex items-center justify-between text-[11px] text-slate-500 pt-2 border-t border-slate-100 font-medium">
                        <span>Next Scheduled Renewal:</span>
                        <span class="text-slate-900 font-bold">{{ formatDate(metrics.next_renewal) }}</span>
                    </div>
                </div>

                <!-- Card 3: DNS & Nameservers Reference -->
                <div class="bg-white rounded-lg border border-slate-200 p-4 shadow-2xs space-y-2">
                    <div class="flex items-center justify-between pb-1.5 border-b border-slate-100">
                        <span class="text-[11px] font-bold text-slate-700 uppercase tracking-wider flex items-center gap-1.5">
                            <GlobeAltIcon class="w-3.5 h-3.5 text-indigo-600" />
                            <span>Nameservers & IP</span>
                        </span>
                        <span v-if="copiedItem" class="text-[10px] text-emerald-600 font-bold">✓ Copied!</span>
                    </div>
                    
                    <div class="space-y-1 text-xs font-mono">
                        <div class="flex items-center justify-between bg-slate-50 px-2 py-0.5 rounded border border-slate-200/80">
                            <span class="text-slate-500 text-[11px]">Server IP:</span>
                            <button 
                                @click="copyToClipboard(serverInfo.public_ip, 'ip')"
                                class="font-bold text-slate-800 hover:text-blue-600 flex items-center gap-1 transition cursor-pointer"
                                title="Copy IP"
                            >
                                <span>{{ serverInfo.public_ip }}</span>
                                <ClipboardDocumentIcon class="w-3.5 h-3.5 text-slate-400" />
                            </button>
                        </div>

                        <div class="flex items-center justify-between bg-slate-50 px-2 py-0.5 rounded border border-slate-200/80">
                            <span class="text-slate-500 text-[11px]">NS1:</span>
                            <button 
                                @click="copyToClipboard(serverInfo.primary_ns, 'ns1')"
                                class="font-bold text-indigo-700 hover:text-indigo-900 flex items-center gap-1 transition cursor-pointer"
                                title="Copy NS1"
                            >
                                <span>{{ serverInfo.primary_ns }}</span>
                                <ClipboardDocumentIcon class="w-3.5 h-3.5 text-slate-400" />
                            </button>
                        </div>

                        <div class="flex items-center justify-between bg-slate-50 px-2 py-0.5 rounded border border-slate-200/80">
                            <span class="text-slate-500 text-[11px]">NS2:</span>
                            <button 
                                @click="copyToClipboard(serverInfo.secondary_ns, 'ns2')"
                                class="font-bold text-indigo-700 hover:text-indigo-900 flex items-center gap-1 transition cursor-pointer"
                                title="Copy NS2"
                            >
                                <span>{{ serverInfo.secondary_ns }}</span>
                                <ClipboardDocumentIcon class="w-3.5 h-3.5 text-slate-400" />
                            </button>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Empty State -->
            <div v-if="subscriptions.length === 0" class="bg-white rounded-lg border border-slate-200 p-12 text-center shadow-2xs">
                <div class="w-12 h-12 rounded-md bg-blue-50 text-blue-600 flex items-center justify-center mx-auto mb-3">
                    <ServerIcon class="w-6 h-6" />
                </div>
                <h3 class="text-base font-bold text-slate-900 mb-1">No Active Hosting Subscriptions</h3>
                <p class="text-xs text-slate-500 max-w-sm mx-auto mb-5">
                    You do not currently have any active cloud hosting subscriptions. Deploy an NVMe BDIX package in seconds.
                </p>
                <Link 
                    :href="route('public.shared')" 
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold shadow-2xs inline-flex items-center gap-1.5 transition"
                >
                    <PlusIcon class="w-3.5 h-3.5" />
                    <span>Browse Hosting Packages</span>
                </Link>
            </div>

            <!-- Subscriptions List -->
            <div v-else class="space-y-4">
                <div 
                    v-for="sub in subscriptions" 
                    :key="sub.id" 
                    class="bg-white rounded-lg border border-slate-200 p-5 shadow-2xs hover:border-slate-300 transition space-y-4 relative overflow-hidden"
                >
                    <!-- 1. Top Bar: Domain, Package, Status & Price -->
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3.5 border-b border-slate-100">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-md bg-blue-50 border border-blue-200/80 text-blue-600 flex items-center justify-center font-bold shrink-0">
                                <ServerIcon class="w-5 h-5 stroke-[2.2]" />
                            </div>
                            <div>
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h3 class="text-base font-black text-slate-900 tracking-tight">{{ sub.domain }}</h3>
                                    <a 
                                        :href="'http://' + sub.domain" 
                                        target="_blank" 
                                        class="text-slate-400 hover:text-blue-600 transition flex items-center gap-0.5 text-xs font-semibold"
                                        title="Open in new tab"
                                    >
                                        <ArrowTopRightOnSquareIcon class="w-3.5 h-3.5" />
                                    </a>
                                    <span class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200 uppercase">
                                        {{ sub.plan.name }}
                                    </span>
                                </div>
                                <p class="text-xs text-slate-500 font-mono mt-0.5 flex items-center gap-2">
                                    <span>vHost User: <strong class="text-slate-800">{{ sub.username }}</strong></span>
                                    <span class="text-slate-300">•</span>
                                    <span>Doc Root: <strong class="text-slate-700">/public_html</strong></span>
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3 self-start sm:self-center">
                            <!-- Status Badge -->
                            <span 
                                class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold border uppercase tracking-wide flex items-center gap-1"
                                :class="sub.status === 'active' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-amber-50 text-amber-700 border-amber-200'"
                            >
                                <span class="w-1.5 h-1.5 rounded-full" :class="sub.status === 'active' ? 'bg-emerald-500 animate-pulse' : 'bg-amber-500'"></span>
                                <span>{{ sub.status }}</span>
                            </span>

                            <!-- Pricing Tag -->
                            <div class="text-right">
                                <span class="text-base font-black text-slate-900 block">৳{{ formatCurrency(sub.price) }}</span>
                                <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider block">/ {{ sub.period }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Hosting Specifications Matrix (8-Box Grid) -->
                    <div class="space-y-2">
                        <div class="flex items-center gap-1.5">
                            <CpuChipIcon class="w-4 h-4 text-blue-600" />
                            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-800">Hosting Specifications & Allocations</h4>
                        </div>

                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 text-xs">
                            <!-- Disk Space -->
                            <div class="p-3 bg-slate-50 rounded-md border border-slate-200/70 flex flex-col justify-between">
                                <span class="text-[10px] uppercase font-bold text-slate-400 block">NVMe Disk Space</span>
                                <span class="text-sm font-black text-slate-900 mt-0.5 block">{{ sub.hosting_details.disk_space }}</span>
                                <span class="text-[10px] text-slate-400 mt-0.5 block">High-Speed Array</span>
                            </div>

                            <!-- RAM -->
                            <div class="p-3 bg-slate-50 rounded-md border border-slate-200/70 flex flex-col justify-between">
                                <span class="text-[10px] uppercase font-bold text-slate-400 block">Dedicated RAM</span>
                                <span class="text-sm font-black text-slate-900 mt-0.5 block">{{ sub.hosting_details.ram }}</span>
                                <span class="text-[10px] text-slate-400 mt-0.5 block">DDR4 Memory Pool</span>
                            </div>

                            <!-- CPU Cores -->
                            <div class="p-3 bg-slate-50 rounded-md border border-slate-200/70 flex flex-col justify-between">
                                <span class="text-[10px] uppercase font-bold text-slate-400 block">CPU Limit</span>
                                <span class="text-sm font-black text-slate-900 mt-0.5 block">{{ sub.hosting_details.cpu_cores }}</span>
                                <span class="text-[10px] text-slate-400 mt-0.5 block">Isolated vCPU Core</span>
                            </div>

                            <!-- Inodes -->
                            <div class="p-3 bg-slate-50 rounded-md border border-slate-200/70 flex flex-col justify-between">
                                <span class="text-[10px] uppercase font-bold text-slate-400 block">Inodes (Files)</span>
                                <span class="text-sm font-black text-slate-900 mt-0.5 block">{{ sub.hosting_details.inodes }}</span>
                                <span class="text-[10px] text-slate-400 mt-0.5 block">File Quota</span>
                            </div>

                            <!-- Addons / Websites -->
                            <div class="p-3 bg-slate-50 rounded-md border border-slate-200/70 flex flex-col justify-between">
                                <span class="text-[10px] uppercase font-bold text-slate-400 block">Allowed Domains</span>
                                <div class="flex items-center justify-between mt-0.5">
                                    <span class="text-sm font-black text-slate-900">{{ sub.hosting_details.addons_websites }}</span>
                                    <span 
                                        v-if="sub.usage.websites_count >= sub.plan.max_domains"
                                        class="text-[9px] font-bold text-amber-700 bg-amber-100 px-1 py-0.2 rounded"
                                    >
                                        Full
                                    </span>
                                </div>
                                <span class="text-[10px] text-slate-400 mt-0.5 block">VirtualHosts</span>
                            </div>

                            <!-- Max Processes -->
                            <div class="p-3 bg-slate-50 rounded-md border border-slate-200/70 flex flex-col justify-between">
                                <span class="text-[10px] uppercase font-bold text-slate-400 block">Max Processes</span>
                                <span class="text-sm font-black text-slate-900 mt-0.5 block">{{ sub.hosting_details.max_processes }}</span>
                                <span class="text-[10px] text-slate-400 mt-0.5 block">Concurrent NPROC</span>
                            </div>

                            <!-- PHP Workers -->
                            <div class="p-3 bg-slate-50 rounded-md border border-slate-200/70 flex flex-col justify-between">
                                <span class="text-[10px] uppercase font-bold text-slate-400 block">PHP Engine</span>
                                <span class="text-sm font-black text-slate-900 mt-0.5 block">PHP {{ sub.php_version }} FPM</span>
                                <span class="text-[10px] text-slate-400 mt-0.5 block">{{ sub.hosting_details.php_workers }}</span>
                            </div>

                            <!-- Bandwidth -->
                            <div class="p-3 bg-slate-50 rounded-md border border-slate-200/70 flex flex-col justify-between">
                                <span class="text-[10px] uppercase font-bold text-slate-400 block">Monthly Bandwidth</span>
                                <span class="text-sm font-black text-slate-900 mt-0.5 block">{{ sub.hosting_details.bandwidth }}</span>
                                <span class="text-[10px] text-slate-400 mt-0.5 block">10Gbps Port</span>
                            </div>
                        </div>
                    </div>

                    <!-- 3. Infrastructure & Server Details (3-Box Grid) -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 text-xs">
                        <div class="p-3 bg-slate-50 rounded-md border border-slate-200/70 flex flex-col justify-between">
                            <span class="text-[10px] uppercase font-bold text-slate-400 block">Server Node</span>
                            <span class="font-mono font-bold text-slate-900 text-xs mt-0.5 block">{{ sub.server_details.server_name }}</span>
                            <span class="text-[10px] text-emerald-600 font-bold mt-1 flex items-center gap-1">
                                ● Dedicated Node Active
                            </span>
                        </div>

                        <div class="p-3 bg-slate-50 rounded-md border border-slate-200/70 flex flex-col justify-between">
                            <span class="text-[10px] uppercase font-bold text-slate-400 block">Datacenter Location</span>
                            <span class="font-bold text-slate-900 text-xs mt-0.5 block flex items-center gap-1">
                                <MapPinIcon class="w-3.5 h-3.5 text-rose-500 shrink-0" />
                                <span>{{ sub.server_details.server_location }}</span>
                            </span>
                            <span class="text-[10px] text-slate-400 mt-1 block">Ultra-Low Latency BDIX</span>
                        </div>

                        <div class="p-3 bg-slate-50 rounded-md border border-slate-200/70 flex flex-col justify-between">
                            <span class="text-[10px] uppercase font-bold text-slate-400 block">Automated Backups</span>
                            <span class="font-bold text-slate-900 text-xs mt-0.5 block flex items-center gap-1">
                                <ArchiveBoxIcon class="w-3.5 h-3.5 text-amber-500 shrink-0" />
                                <span>{{ sub.server_details.backups_location }}</span>
                            </span>
                            <span class="text-[10px] text-slate-400 mt-1 block">Daily Offsite S3 Snapshots</span>
                        </div>
                    </div>

                    <!-- 3.5 Services & Stack Entitlements -->
                    <div class="p-3 bg-slate-50/70 rounded-md border border-slate-200/80 space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] uppercase font-bold text-slate-600 flex items-center gap-1">
                                <SparklesIcon class="w-3.5 h-3.5 text-amber-500" />
                                <span>Plan Features & Stacks</span>
                            </span>
                            <span class="text-[10px] text-slate-400">Dynamic Plan Provisioning</span>
                        </div>
                        <div class="flex flex-wrap items-center gap-1.5 text-[11px]">
                            <!-- Redis -->
                            <span 
                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-[3px] font-bold border"
                                :class="sub.plan?.allow_redis ? 'bg-rose-50 text-rose-700 border-rose-200' : 'bg-slate-100 text-slate-400 border-slate-200 line-through'"
                            >
                                <span>Redis ({{ sub.plan?.redis_memory_mb || 64 }}MB)</span>
                            </span>

                            <!-- Memcached -->
                            <span 
                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-[3px] font-bold border"
                                :class="sub.plan?.allow_memcached ? 'bg-indigo-50 text-indigo-700 border-indigo-200' : 'bg-slate-100 text-slate-400 border-slate-200 line-through'"
                            >
                                <span>Memcached</span>
                            </span>

                            <!-- Node.js -->
                            <span 
                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-[3px] font-bold border"
                                :class="sub.plan?.allow_nodejs ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-400 border-slate-200 line-through'"
                            >
                                <span>Node.js</span>
                            </span>

                            <!-- Python -->
                            <span 
                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-[3px] font-bold border"
                                :class="sub.plan?.allow_python ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-slate-100 text-slate-400 border-slate-200 line-through'"
                            >
                                <span>Python / WSGI</span>
                            </span>

                            <!-- Cron Jobs -->
                            <span 
                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-[3px] font-bold border"
                                :class="sub.plan?.allow_cron_jobs ? 'bg-blue-50 text-blue-700 border-blue-200' : 'bg-slate-100 text-slate-400 border-slate-200 line-through'"
                            >
                                <span>Cron Jobs</span>
                            </span>

                            <!-- Auto Backups -->
                            <span 
                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-[3px] font-bold border"
                                :class="sub.plan?.allow_backups ? 'bg-teal-50 text-teal-700 border-teal-200' : 'bg-slate-100 text-slate-400 border-slate-200 line-through'"
                            >
                                <span>Daily Backups</span>
                            </span>

                            <!-- SSH Access -->
                            <span 
                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-[3px] font-bold border"
                                :class="sub.plan?.allow_ssh_access ? 'bg-violet-50 text-violet-700 border-violet-200' : 'bg-slate-100 text-slate-400 border-slate-200 line-through'"
                            >
                                <span>SSH Terminal</span>
                            </span>

                            <!-- Git Deploy -->
                            <span 
                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-[3px] font-bold border"
                                :class="sub.plan?.allow_git_deploy ? 'bg-orange-50 text-orange-700 border-orange-200' : 'bg-slate-100 text-slate-400 border-slate-200 line-through'"
                            >
                                <span>Git CI/CD</span>
                            </span>
                        </div>
                    </div>

                    <!-- 4. Environment & Expiry Info Bar -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between text-xs text-slate-600 bg-slate-50/90 px-3.5 py-2.5 rounded-md border border-slate-200/80 gap-2">
                        <div class="flex flex-wrap items-center gap-3">
                            <div class="flex items-center gap-1 font-mono">
                                <span class="text-slate-400 text-[11px]">Server IP:</span>
                                <strong class="text-slate-800">{{ serverInfo.public_ip }}</strong>
                            </div>
                            <span class="text-slate-300">•</span>
                            <div class="flex items-center gap-1 font-mono">
                                <span class="text-slate-400 text-[11px]">PHP Socket:</span>
                                <strong class="text-blue-700">PHP {{ sub.php_version }} FPM</strong>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <span class="text-slate-400 text-[11px]">Renewal Date:</span>
                            <strong class="text-slate-800">{{ formatDate(sub.expires_at) }}</strong>
                            <span 
                                v-if="sub.days_remaining !== null" 
                                class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold border"
                                :class="sub.is_expiring_soon ? 'bg-rose-50 text-rose-700 border-rose-200' : 'bg-slate-100 text-slate-700 border-slate-200'"
                            >
                                {{ sub.days_remaining }} days left
                            </span>
                        </div>
                    </div>

                    <!-- 5. 1-Click Launchers & Action Controls -->
                    <div class="flex flex-wrap items-center justify-between gap-3 pt-3 border-t border-slate-100 text-xs">
                        <div class="flex flex-wrap items-center gap-2">
                            <Link 
                                :href="route('websites.index')" 
                                class="px-2.5 py-1 bg-white hover:bg-slate-50 text-slate-700 rounded-[3px] border border-slate-200 font-bold flex items-center gap-1.5 shadow-2xs transition"
                            >
                                <GlobeAltIcon class="w-3.5 h-3.5 text-blue-600" />
                                <span>Websites ({{ sub.usage.websites_count }})</span>
                            </Link>

                            <Link 
                                :href="route('file.browse')" 
                                class="px-2.5 py-1 bg-white hover:bg-slate-50 text-slate-700 rounded-[3px] border border-slate-200 font-bold flex items-center gap-1.5 shadow-2xs transition"
                            >
                                <FolderIcon class="w-3.5 h-3.5 text-amber-600" />
                                <span>File Manager</span>
                            </Link>

                            <Link 
                                :href="route('databases.index')" 
                                class="px-2.5 py-1 bg-white hover:bg-slate-50 text-slate-700 rounded-[3px] border border-slate-200 font-bold flex items-center gap-1.5 shadow-2xs transition"
                            >
                                <CircleStackIcon class="w-3.5 h-3.5 text-purple-600" />
                                <span>Databases ({{ sub.usage.databases_count }})</span>
                            </Link>

                            <a 
                                :href="route('databases.sso')" 
                                target="_blank"
                                class="px-2.5 py-1 bg-purple-50 hover:bg-purple-100 text-purple-700 rounded-[3px] border border-purple-200 font-bold flex items-center gap-1 shadow-2xs transition"
                            >
                                <span>phpMyAdmin</span>
                                <ArrowTopRightOnSquareIcon class="w-3 h-3 text-purple-500" />
                            </a>

                            <Link 
                                :href="route('email-accounts.index')" 
                                class="px-2.5 py-1 bg-white hover:bg-slate-50 text-slate-700 rounded-[3px] border border-slate-200 font-bold flex items-center gap-1.5 shadow-2xs transition"
                            >
                                <EnvelopeIcon class="w-3.5 h-3.5 text-emerald-600" />
                                <span>Emails ({{ sub.usage.email_accounts_count }})</span>
                            </Link>
                        </div>

                        <div class="flex items-center gap-2">
                            <Link 
                                :href="route('hosting.renew')" 
                                class="px-2.5 py-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 rounded-[3px] font-bold flex items-center gap-1 shadow-2xs transition"
                            >
                                <ClockIcon class="w-3.5 h-3.5 text-emerald-600" />
                                <span>Renew</span>
                            </Link>

                            <Link 
                                :href="route('hosting.upgrade')" 
                                class="px-2.5 py-1 bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-200 rounded-[3px] font-bold flex items-center gap-1 shadow-2xs transition"
                            >
                                <ArrowUpCircleIcon class="w-3.5 h-3.5 text-amber-600" />
                                <span>Upgrade</span>
                            </Link>

                            <Link 
                                :href="route('subscriptions.show', sub.id)" 
                                class="px-2.5 py-1 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] font-bold flex items-center gap-1 shadow-2xs transition"
                            >
                                <span>Manage Details</span>
                                <ArrowRightIcon class="w-3.5 h-3.5" />
                            </Link>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>
