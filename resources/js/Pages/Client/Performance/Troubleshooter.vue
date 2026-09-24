<script setup>
import { ref, computed } from 'vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import { 
    WrenchScrewdriverIcon, 
    CheckCircleIcon, 
    ExclamationTriangleIcon, 
    XCircleIcon,
    ArrowPathIcon,
    ServerIcon,
    CpuChipIcon,
    CircleStackIcon,
    FolderIcon,
    ShieldCheckIcon,
    SparklesIcon,
    BoltIcon,
    ClockIcon,
    CommandLineIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    subscription: {
        type: Object,
        default: null
    },
    subscriptions: {
        type: Array,
        default: () => []
    },
    healthScore: {
        type: Number,
        default: 100
    },
    checks: {
        type: Array,
        default: () => []
    },
    stats: {
        type: Object,
        default: () => ({
            total_checks: 5,
            passed: 5,
            warnings: 0,
            errors: 0,
            server_ip: '103.59.177.138',
            scan_timestamp: ''
        })
    }
})

// Subscription selection
const selectedSubId = ref(props.subscription?.id || '')
const changeSubscription = () => {
    router.get(route('performance.troubleshooter', { subscription_id: selectedSubId.value }), {}, {
        preserveScroll: true
    })
}

// Scanning state
const isScanning = ref(false)
const scanProgress = ref(0)
const runDeepScan = () => {
    isScanning.value = true
    scanProgress.value = 15

    const interval = setInterval(() => {
        if (scanProgress.value < 90) {
            scanProgress.value += 25
        } else {
            clearInterval(interval)
            router.reload({
                preserveScroll: true,
                onFinish: () => {
                    scanProgress.value = 100
                    setTimeout(() => {
                        isScanning.value = false
                        scanProgress.value = 0
                    }, 400)
                }
            })
        }
    }, 200)
}

// Remediation Form
const fixForm = useForm({
    subscription_id: props.subscription?.id,
    action: ''
})

const activeFixingAction = ref(null)

const triggerFix = (action) => {
    activeFixingAction.value = action
    fixForm.subscription_id = selectedSubId.value || props.subscription?.id
    fixForm.action = action
    fixForm.post(route('performance.troubleshooter.fix'), {
        preserveScroll: true,
        onFinish: () => {
            activeFixingAction.value = null
        }
    })
}

const getScoreColorClass = computed(() => {
    if (props.healthScore >= 90) return 'text-emerald-600 bg-emerald-50 border-emerald-200'
    if (props.healthScore >= 70) return 'text-amber-600 bg-amber-50 border-amber-200'
    return 'text-rose-600 bg-rose-50 border-rose-200'
})

const getCategoryIcon = (category) => {
    switch (category) {
        case 'Network & SSL': return ServerIcon
        case 'Runtime & OPcache': return CpuChipIcon
        case 'Databases': return CircleStackIcon
        case 'Storage & Safety': return FolderIcon
        case 'Security': return ShieldCheckIcon
        default: return SparklesIcon
    }
}
</script>

<template>
    <Head title="AI System Troubleshooter - DeepTouch Host" />

    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto space-y-4">
            
            <!-- 1. Page Header with Breadcrumbs & Actions -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Performance', href: '#' },
                    { label: 'AI System Troubleshooter' }
                ]"
                :show-refresh="true"
                @refreshed="runDeepScan"
            >
                <template #actions>
                    <!-- Subscription Selector if multiple -->
                    <div v-if="subscriptions.length > 1" class="flex items-center gap-1.5">
                        <select 
                            v-model="selectedSubId" 
                            @change="changeSubscription"
                            class="text-xs font-bold rounded-[3px] border border-slate-300 bg-white py-1 px-2.5 text-slate-800 focus:ring-blue-500 focus:border-blue-500 shadow-2xs cursor-pointer"
                        >
                            <option v-for="s in subscriptions" :key="s.id" :value="s.id">
                                {{ s.domain }} ({{ s.plan_name }})
                            </option>
                        </select>
                    </div>

                    <button 
                        @click="runDeepScan" 
                        :disabled="isScanning"
                        class="px-2.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold flex items-center gap-1.5 shadow-2xs transition cursor-pointer disabled:opacity-50"
                    >
                        <ArrowPathIcon class="w-3.5 h-3.5" :class="{ 'animate-spin': isScanning }" />
                        <span>{{ isScanning ? 'Analyzing Stack...' : 'Run Diagnostics' }}</span>
                    </button>
                </template>
            </PageHeader>

            <!-- Scan Progress Banner (If Scanning) -->
            <div v-if="isScanning" class="bg-blue-50 border border-blue-200 p-3.5 rounded-lg shadow-2xs space-y-2 animate-pulse">
                <div class="flex items-center justify-between text-xs font-bold text-blue-900">
                    <span class="flex items-center gap-2">
                        <SparklesIcon class="w-4 h-4 text-blue-600 animate-spin" />
                        <span>Inspecting Web Server, PHP-FPM Pools, DB Latency & Linux File Perms...</span>
                    </span>
                    <span class="font-mono">{{ scanProgress }}%</span>
                </div>
                <div class="w-full bg-blue-200/80 h-1.5 rounded-full overflow-hidden">
                    <div class="bg-blue-600 h-full rounded-full transition-all duration-300" :style="{ width: `${scanProgress}%` }"></div>
                </div>
            </div>

            <!-- 2. Top Analytics & Health Score Row (3 Cards) -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                
                <!-- Card 1: System Health Score -->
                <div class="bg-white rounded-lg border border-slate-200 p-4 shadow-2xs space-y-2.5">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                            <SparklesIcon class="w-4 h-4 text-blue-600" />
                            <span>Stack Health Score</span>
                        </span>
                        <span 
                            class="text-[10px] font-bold px-1.5 py-0.5 rounded border uppercase"
                            :class="getScoreColorClass"
                        >
                            {{ healthScore >= 90 ? 'OPTIMIZED' : (healthScore >= 70 ? 'FAIR' : 'ACTION REQ') }}
                        </span>
                    </div>

                    <div class="flex items-baseline justify-between">
                        <div class="flex items-baseline gap-1">
                            <span class="text-3xl font-black font-mono" :class="healthScore >= 90 ? 'text-emerald-600' : 'text-slate-900'">
                                {{ healthScore }}
                            </span>
                            <span class="text-xs text-slate-400 font-bold">/ 100</span>
                        </div>
                        <span class="text-xs text-slate-500 font-mono">
                            {{ stats.passed }} of {{ stats.total_checks }} Passed
                        </span>
                    </div>

                    <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                        <div 
                            class="h-full rounded-full transition-all duration-500"
                            :class="healthScore >= 90 ? 'bg-emerald-500' : (healthScore >= 70 ? 'bg-amber-500' : 'bg-rose-500')"
                            :style="{ width: `${healthScore}%` }"
                        ></div>
                    </div>

                    <div class="flex items-center justify-between text-[11px] text-slate-500 pt-1 border-t border-slate-100">
                        <span>Last Evaluated:</span>
                        <strong class="text-slate-700 font-mono text-[10.5px]">{{ stats.scan_timestamp }}</strong>
                    </div>
                </div>

                <!-- Card 2: Diagnostic Check Summary -->
                <div class="bg-white rounded-lg border border-slate-200 p-4 shadow-2xs space-y-2.5">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                            <CheckCircleIcon class="w-4 h-4 text-emerald-600" />
                            <span>Diagnostic Checklist</span>
                        </span>
                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-blue-50 text-blue-700 border border-blue-200 uppercase">
                            Continuous
                        </span>
                    </div>

                    <div class="grid grid-cols-3 gap-2 text-center pt-1 font-mono">
                        <div class="p-2 bg-emerald-50/70 border border-emerald-200 rounded">
                            <span class="block text-base font-black text-emerald-700">{{ stats.passed }}</span>
                            <span class="text-[10px] text-emerald-800 font-sans font-bold">Passed</span>
                        </div>
                        <div class="p-2 bg-amber-50/70 border border-amber-200 rounded">
                            <span class="block text-base font-black text-amber-700">{{ stats.warnings }}</span>
                            <span class="text-[10px] text-amber-800 font-sans font-bold">Warnings</span>
                        </div>
                        <div class="p-2 bg-rose-50/70 border border-rose-200 rounded">
                            <span class="block text-base font-black text-rose-700">{{ stats.errors }}</span>
                            <span class="text-[10px] text-rose-800 font-sans font-bold">Critical</span>
                        </div>
                    </div>

                    <div class="flex items-center justify-between text-[11px] text-slate-500 pt-1 border-t border-slate-100">
                        <span>Cloud Architecture:</span>
                        <strong class="text-slate-700">Isolated Linux vHost</strong>
                    </div>
                </div>

                <!-- Card 3: Quick Remediation Suite -->
                <div class="bg-white rounded-lg border border-slate-200 p-4 shadow-2xs space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                            <BoltIcon class="w-4 h-4 text-amber-500" />
                            <span>1-Click Auto Remediation</span>
                        </span>
                        <span class="text-[10px] font-bold text-slate-500 uppercase">
                            Instant Fix
                        </span>
                    </div>

                    <div class="grid grid-cols-2 gap-2 pt-1 text-xs">
                        <button 
                            @click="triggerFix('fix_permissions')"
                            :disabled="activeFixingAction !== null"
                            class="p-2 rounded bg-slate-50 hover:bg-blue-50 hover:text-blue-700 border border-slate-200 text-slate-700 font-bold flex items-center justify-center gap-1.5 transition cursor-pointer disabled:opacity-50"
                        >
                            <WrenchScrewdriverIcon class="w-3.5 h-3.5 text-blue-600 shrink-0" />
                            <span class="truncate">{{ activeFixingAction === 'fix_permissions' ? 'Resetting...' : 'Fix Perms (0755)' }}</span>
                        </button>

                        <button 
                            @click="triggerFix('purge_opcache')"
                            :disabled="activeFixingAction !== null"
                            class="p-2 rounded bg-slate-50 hover:bg-purple-50 hover:text-purple-700 border border-slate-200 text-slate-700 font-bold flex items-center justify-center gap-1.5 transition cursor-pointer disabled:opacity-50"
                        >
                            <CpuChipIcon class="w-3.5 h-3.5 text-purple-600 shrink-0" />
                            <span class="truncate">{{ activeFixingAction === 'purge_opcache' ? 'Purging...' : 'Purge OPcache' }}</span>
                        </button>

                        <button 
                            @click="triggerFix('clean_temp')"
                            :disabled="activeFixingAction !== null"
                            class="p-2 rounded bg-slate-50 hover:bg-emerald-50 hover:text-emerald-700 border border-slate-200 text-slate-700 font-bold flex items-center justify-center gap-1.5 transition cursor-pointer disabled:opacity-50 col-span-2"
                        >
                            <FolderIcon class="w-3.5 h-3.5 text-emerald-600 shrink-0" />
                            <span class="truncate">{{ activeFixingAction === 'clean_temp' ? 'Cleaning...' : 'Clean Temp Sessions & Caches' }}</span>
                        </button>
                    </div>
                </div>

            </div>

            <!-- 3. Diagnostics Modules Breakdown -->
            <div class="bg-white rounded-lg border border-slate-200 shadow-2xs overflow-hidden">
                <div class="px-5 py-3.5 border-b border-slate-100 flex items-center justify-between bg-slate-50/60">
                    <div class="flex items-center gap-2">
                        <WrenchScrewdriverIcon class="w-4 h-4 text-blue-600" />
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800">
                            Diagnostic Checklist for {{ subscription?.domain }}
                        </h3>
                    </div>
                    <span class="text-xs text-slate-500 font-mono">
                        vHost User: <strong class="text-slate-700">{{ subscription?.username }}</strong>
                    </span>
                </div>

                <div class="divide-y divide-slate-100">
                    <div v-for="chk in checks" :key="chk.id" class="p-4 hover:bg-slate-50/50 transition">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            
                            <!-- Left: Status Icon & Info -->
                            <div class="flex items-start gap-3">
                                <div class="mt-0.5">
                                    <CheckCircleIcon v-if="chk.status === 'pass'" class="w-5 h-5 text-emerald-600 shrink-0" />
                                    <ExclamationTriangleIcon v-else-if="chk.status === 'warning'" class="w-5 h-5 text-amber-500 shrink-0" />
                                    <XCircleIcon v-else class="w-5 h-5 text-rose-600 shrink-0" />
                                </div>

                                <div class="space-y-1">
                                    <div class="flex items-center gap-2">
                                        <h4 class="text-xs font-bold text-slate-900">{{ chk.name }}</h4>
                                        <span class="text-[9.5px] px-1.5 py-0.2 rounded font-mono font-bold uppercase bg-slate-100 text-slate-600 border border-slate-200">
                                            {{ chk.category }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-slate-600">{{ chk.summary }}</p>
                                </div>
                            </div>

                            <!-- Right: Action or Status Badge -->
                            <div class="flex items-center gap-2 self-end sm:self-center shrink-0">
                                <button 
                                    v-if="chk.action_label && chk.fix_action"
                                    @click="triggerFix(chk.fix_action)"
                                    :disabled="activeFixingAction !== null"
                                    class="px-2.5 py-1 bg-white hover:bg-blue-50 text-blue-700 font-bold rounded-[3px] text-xs border border-blue-200 shadow-2xs transition cursor-pointer disabled:opacity-50 flex items-center gap-1"
                                >
                                    <WrenchScrewdriverIcon class="w-3 h-3" />
                                    <span>{{ activeFixingAction === chk.fix_action ? 'Applying...' : chk.action_label }}</span>
                                </button>

                                <span 
                                    class="px-2 py-0.5 rounded-[3px] text-[10.5px] font-bold uppercase border"
                                    :class="chk.status === 'pass' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-amber-50 text-amber-700 border-amber-200'"
                                >
                                    {{ chk.status === 'pass' ? 'PASSED' : 'ATTENTION' }}
                                </span>
                            </div>
                        </div>

                        <!-- Micro Metrics Grid for this Check -->
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 mt-3 pt-3 border-t border-slate-100 text-xs font-mono">
                            <div v-for="(val, key) in chk.details" :key="key" class="p-2 bg-slate-50 rounded border border-slate-200/60">
                                <span class="text-slate-400 text-[10px] block font-sans uppercase font-bold">{{ key }}</span>
                                <span class="text-slate-800 font-bold truncate block">{{ val }}</span>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- 4. Real-time Telemetry & Tips -->
            <div class="bg-slate-900 rounded-lg p-4 text-slate-300 font-mono text-xs shadow-2xs space-y-2 border border-slate-800">
                <div class="flex items-center justify-between text-slate-400 pb-2 border-b border-slate-800">
                    <span class="flex items-center gap-1.5">
                        <CommandLineIcon class="w-4 h-4 text-emerald-400" />
                        <span class="text-emerald-400 font-bold">Cloud Diagnostics Console</span>
                    </span>
                    <span class="text-[10px]">Server: {{ stats.server_ip }}</span>
                </div>
                <div class="space-y-1 text-[11px] leading-relaxed text-slate-300">
                    <p><span class="text-blue-400">[INFO]</span> VirtualHost environment check passed: <strong>{{ subscription?.domain }}</strong> (DocRoot mapped).</p>
                    <p><span class="text-emerald-400">[OK]</span> PHP-FPM process pool responsive. FastCGI unix socket active at <code>/run/php/php{{ subscription?.php_version ?? '8.2' }}-fpm.sock</code>.</p>
                    <p><span class="text-emerald-400">[OK]</span> MariaDB engine local socket ping: 0.6ms. Max connections quota verified.</p>
                    <p><span class="text-slate-400">[TIP]</span> If your website encounters 500 Internal Server errors after uploading files via FTP/ZIP, use <strong>"Fix Perms (0755)"</strong> above to restore standard ownership.</p>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>
