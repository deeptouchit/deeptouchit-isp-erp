<script setup>
import { ref, computed } from 'vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import { 
    BoltIcon, 
    CheckCircleIcon, 
    ArrowPathIcon,
    DevicePhoneMobileIcon,
    ComputerDesktopIcon,
    SparklesIcon,
    GlobeAltIcon,
    ClockIcon,
    ShieldCheckIcon,
    CpuChipIcon,
    RocketLaunchIcon,
    AdjustmentsHorizontalIcon,
    ArrowTopRightOnSquareIcon
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
    websites: {
        type: Array,
        default: () => []
    },
    currentDomain: {
        type: String,
        default: 'somitysoft.com'
    },
    metrics: {
        type: Object,
        default: () => ({
            performance_score: 96,
            ttfb: '42 ms',
            fcp: '0.8 s',
            lcp: '1.1 s',
            cls: '0.002',
            tbt: '25 ms',
            speed_index: '0.9 s'
        })
    },
    optimizations: {
        type: Object,
        default: () => ({
            gzip_brotli: true,
            browser_caching: true,
            webp_compression: true,
            http2_multiplexing: true,
            minify_assets: true,
            keep_alive: true,
        })
    },
    audits: {
        type: Array,
        default: () => []
    },
    lastAuditTime: {
        type: String,
        default: ''
    }
})

// Selected Device (Mobile / Desktop)
const activeDevice = ref('mobile')

// Domain selector
const selectedDomain = ref(props.currentDomain)
const changeDomain = () => {
    router.get(route('performance.pagespeed', { domain: selectedDomain.value }), {}, {
        preserveScroll: true
    })
}

// Running Audit Simulation
const isAuditing = ref(false)
const auditProgress = ref(0)

const runSpeedAudit = () => {
    isAuditing.value = true
    auditProgress.value = 10

    const interval = setInterval(() => {
        if (auditProgress.value < 90) {
            auditProgress.value += 20
        } else {
            clearInterval(interval)
            router.post(route('performance.pagespeed.analyze'), {
                domain: selectedDomain.value,
                device: activeDevice.value
            }, {
                preserveScroll: true,
                onFinish: () => {
                    auditProgress.value = 100
                    setTimeout(() => {
                        isAuditing.value = false
                        auditProgress.value = 0
                    }, 400)
                }
            })
        }
    }, 200)
}

// Toggle Speed Optimization Feature
const toggleForm = useForm({
    feature: '',
    enabled: true
})

const activeTogglingFeature = ref(null)

const toggleOptimization = (key, currentValue) => {
    activeTogglingFeature.value = key
    toggleForm.feature = key
    toggleForm.enabled = !currentValue
    toggleForm.post(route('performance.pagespeed.toggle'), {
        preserveScroll: true,
        onFinish: () => {
            activeTogglingFeature.value = null
        }
    })
}

const getScoreColorClass = (score) => {
    if (score >= 90) return 'text-emerald-600 bg-emerald-50 border-emerald-200'
    if (score >= 50) return 'text-amber-600 bg-amber-50 border-amber-200'
    return 'text-rose-600 bg-rose-50 border-rose-200'
}
</script>

<template>
    <Head title="PageSpeed & Core Web Vitals - DeepTouch Host" />

    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto space-y-4">
            
            <!-- 1. Page Header with Breadcrumbs & Actions -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Performance', href: '#' },
                    { label: 'PageSpeed & Web Vitals' }
                ]"
                :show-refresh="true"
                @refreshed="runSpeedAudit"
            >
                <template #actions>
                    <!-- Domain Selector -->
                    <div class="flex items-center gap-1.5">
                        <select 
                            v-model="selectedDomain" 
                            @change="changeDomain"
                            class="text-xs font-bold rounded-[3px] border border-slate-300 bg-white py-1 px-2.5 text-slate-800 focus:ring-blue-500 focus:border-blue-500 shadow-2xs cursor-pointer"
                        >
                            <option v-for="site in websites" :key="site.id" :value="site.domain">
                                {{ site.domain }}
                            </option>
                            <option v-if="websites.length === 0" :value="subscription?.domain">
                                {{ subscription?.domain }}
                            </option>
                        </select>
                    </div>

                    <!-- Device Switcher (Mobile / Desktop) -->
                    <div class="inline-flex rounded-[3px] border border-slate-200 bg-slate-100 p-0.5">
                        <button 
                            type="button"
                            @click="activeDevice = 'mobile'"
                            class="px-2 py-1 text-xs font-bold rounded-[2px] transition flex items-center gap-1 cursor-pointer"
                            :class="activeDevice === 'mobile' ? 'bg-white text-blue-600 shadow-2xs' : 'text-slate-600 hover:text-slate-900'"
                        >
                            <DevicePhoneMobileIcon class="w-3.5 h-3.5" />
                            <span>Mobile</span>
                        </button>
                        <button 
                            type="button"
                            @click="activeDevice = 'desktop'"
                            class="px-2 py-1 text-xs font-bold rounded-[2px] transition flex items-center gap-1 cursor-pointer"
                            :class="activeDevice === 'desktop' ? 'bg-white text-blue-600 shadow-2xs' : 'text-slate-600 hover:text-slate-900'"
                        >
                            <ComputerDesktopIcon class="w-3.5 h-3.5" />
                            <span>Desktop</span>
                        </button>
                    </div>

                    <button 
                        @click="runSpeedAudit" 
                        :disabled="isAuditing"
                        class="px-2.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold flex items-center gap-1.5 shadow-2xs transition cursor-pointer disabled:opacity-50"
                    >
                        <ArrowPathIcon class="w-3.5 h-3.5" :class="{ 'animate-spin': isAuditing }" />
                        <span>{{ isAuditing ? 'Testing Core Vitals...' : 'Audit PageSpeed' }}</span>
                    </button>
                </template>
            </PageHeader>

            <!-- Audit Progress Banner (If Testing) -->
            <div v-if="isAuditing" class="bg-blue-50 border border-blue-200 p-3.5 rounded-lg shadow-2xs space-y-2 animate-pulse">
                <div class="flex items-center justify-between text-xs font-bold text-blue-900">
                    <span class="flex items-center gap-2">
                        <RocketLaunchIcon class="w-4 h-4 text-blue-600 animate-spin" />
                        <span>Measuring First Contentful Paint (FCP), TTFB, and LCP on {{ selectedDomain }}...</span>
                    </span>
                    <span class="font-mono">{{ auditProgress }}%</span>
                </div>
                <div class="w-full bg-blue-200/80 h-1.5 rounded-full overflow-hidden">
                    <div class="bg-blue-600 h-full rounded-full transition-all duration-300" :style="{ width: `${auditProgress}%` }"></div>
                </div>
            </div>

            <!-- 2. Google Core Web Vitals 4-Card Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                
                <!-- Card 1: Performance Score -->
                <div class="bg-white rounded-lg border border-slate-200 p-4 shadow-2xs space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                            <SparklesIcon class="w-4 h-4 text-emerald-600" />
                            <span>Performance Score</span>
                        </span>
                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-200 uppercase">
                            PASSED
                        </span>
                    </div>

                    <div class="flex items-baseline justify-between pt-1">
                        <div class="flex items-baseline gap-1 font-mono">
                            <span class="text-3xl font-black text-emerald-600">{{ metrics.performance_score }}</span>
                            <span class="text-xs text-slate-400 font-bold">/ 100</span>
                        </div>
                        <span class="text-[11px] text-emerald-700 font-bold bg-emerald-50 px-1.5 py-0.5 rounded">
                            Fast (90-100)
                        </span>
                    </div>

                    <div class="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden mt-1">
                        <div class="bg-emerald-500 h-full rounded-full w-[96%]"></div>
                    </div>
                </div>

                <!-- Card 2: TTFB (Time to First Byte) -->
                <div class="bg-white rounded-lg border border-slate-200 p-4 shadow-2xs space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                            <BoltIcon class="w-4 h-4 text-blue-600" />
                            <span>Server TTFB (Latency)</span>
                        </span>
                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-blue-50 text-blue-700 border border-blue-200 uppercase">
                            BDIX Edge
                        </span>
                    </div>

                    <div class="flex items-baseline justify-between pt-1">
                        <span class="text-3xl font-black text-slate-900 font-mono">{{ metrics.ttfb }}</span>
                        <span class="text-[11px] text-emerald-700 font-bold bg-emerald-50 px-1.5 py-0.5 rounded">
                            Target &lt; 100ms
                        </span>
                    </div>

                    <p class="text-[11px] text-slate-500 truncate">
                        Ultra-fast initial server response via local peering.
                    </p>
                </div>

                <!-- Card 3: Largest Contentful Paint (LCP) -->
                <div class="bg-white rounded-lg border border-slate-200 p-4 shadow-2xs space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                            <ClockIcon class="w-4 h-4 text-purple-600" />
                            <span>Largest Paint (LCP)</span>
                        </span>
                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-purple-50 text-purple-700 border border-purple-200 uppercase">
                            Core Vital
                        </span>
                    </div>

                    <div class="flex items-baseline justify-between pt-1">
                        <span class="text-3xl font-black text-slate-900 font-mono">{{ metrics.lcp }}</span>
                        <span class="text-[11px] text-emerald-700 font-bold bg-emerald-50 px-1.5 py-0.5 rounded">
                            Good &lt; 2.5s
                        </span>
                    </div>

                    <p class="text-[11px] text-slate-500 truncate">
                        Hero image & main DOM render time.
                    </p>
                </div>

                <!-- Card 4: Cumulative Layout Shift (CLS) -->
                <div class="bg-white rounded-lg border border-slate-200 p-4 shadow-2xs space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                            <AdjustmentsHorizontalIcon class="w-4 h-4 text-amber-600" />
                            <span>Layout Shift (CLS)</span>
                        </span>
                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-amber-50 text-amber-700 border border-amber-200 uppercase">
                            Stability
                        </span>
                    </div>

                    <div class="flex items-baseline justify-between pt-1">
                        <span class="text-3xl font-black text-slate-900 font-mono">{{ metrics.cls }}</span>
                        <span class="text-[11px] text-emerald-700 font-bold bg-emerald-50 px-1.5 py-0.5 rounded">
                            Good &lt; 0.1
                        </span>
                    </div>

                    <p class="text-[11px] text-slate-500 truncate">
                        Zero visual jitter or unexpected layout movements.
                    </p>
                </div>

            </div>

            <!-- 3. Server Edge Acceleration & Optimization Toggles -->
            <div class="bg-white rounded-lg border border-slate-200 p-5 shadow-2xs space-y-3">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div class="flex items-center gap-2">
                        <RocketLaunchIcon class="w-5 h-5 text-blue-600" />
                        <div>
                            <h3 class="font-bold text-slate-900 text-xs uppercase tracking-wider">
                                Edge Acceleration & Cache Engine
                            </h3>
                            <p class="text-[11px] text-slate-500">
                                Native server-level optimizations applied to Nginx and PHP-FPM for {{ selectedDomain }}.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 pt-1">
                    
                    <!-- Toggle 1: Brotli & Gzip -->
                    <div class="p-3 bg-slate-50/80 rounded border border-slate-200/80 flex items-start justify-between gap-3">
                        <div class="space-y-0.5">
                            <span class="text-xs font-bold text-slate-800 block">Brotli & Gzip Compression</span>
                            <p class="text-[10.5px] text-slate-500">Compresses HTML/CSS/JS by up to 80% before transmission.</p>
                        </div>
                        <button 
                            @click="toggleOptimization('gzip_brotli', optimizations.gzip_brotli)"
                            :disabled="activeTogglingFeature !== null"
                            class="text-[10px] font-bold px-2 py-0.5 rounded border uppercase transition cursor-pointer shrink-0"
                            :class="optimizations.gzip_brotli ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-200 text-slate-600 border-slate-300'"
                        >
                            {{ optimizations.gzip_brotli ? 'ENABLED' : 'DISABLED' }}
                        </button>
                    </div>

                    <!-- Toggle 2: Browser Cache TTL -->
                    <div class="p-3 bg-slate-50/80 rounded border border-slate-200/80 flex items-start justify-between gap-3">
                        <div class="space-y-0.5">
                            <span class="text-xs font-bold text-slate-800 block">Browser Cache TTL (365 Days)</span>
                            <p class="text-[10.5px] text-slate-500">Adds immutable max-age headers for instant return visits.</p>
                        </div>
                        <button 
                            @click="toggleOptimization('browser_caching', optimizations.browser_caching)"
                            :disabled="activeTogglingFeature !== null"
                            class="text-[10px] font-bold px-2 py-0.5 rounded border uppercase transition cursor-pointer shrink-0"
                            :class="optimizations.browser_caching ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-200 text-slate-600 border-slate-300'"
                        >
                            {{ optimizations.browser_caching ? 'ENABLED' : 'DISABLED' }}
                        </button>
                    </div>

                    <!-- Toggle 3: WebP Auto-Conversion -->
                    <div class="p-3 bg-slate-50/80 rounded border border-slate-200/80 flex items-start justify-between gap-3">
                        <div class="space-y-0.5">
                            <span class="text-xs font-bold text-slate-800 block">WebP Image Engine</span>
                            <p class="text-[10.5px] text-slate-500">Transfers next-gen WebP images to supported browsers.</p>
                        </div>
                        <button 
                            @click="toggleOptimization('webp_compression', optimizations.webp_compression)"
                            :disabled="activeTogglingFeature !== null"
                            class="text-[10px] font-bold px-2 py-0.5 rounded border uppercase transition cursor-pointer shrink-0"
                            :class="optimizations.webp_compression ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-200 text-slate-600 border-slate-300'"
                        >
                            {{ optimizations.webp_compression ? 'ENABLED' : 'DISABLED' }}
                        </button>
                    </div>

                    <!-- Toggle 4: HTTP/2 Multiplexing -->
                    <div class="p-3 bg-slate-50/80 rounded border border-slate-200/80 flex items-start justify-between gap-3">
                        <div class="space-y-0.5">
                            <span class="text-xs font-bold text-slate-800 block">HTTP/2 Protocol Multiplexing</span>
                            <p class="text-[10.5px] text-slate-500">Downloads multiple assets simultaneously over single socket.</p>
                        </div>
                        <button 
                            @click="toggleOptimization('http2_multiplexing', optimizations.http2_multiplexing)"
                            :disabled="activeTogglingFeature !== null"
                            class="text-[10px] font-bold px-2 py-0.5 rounded border uppercase transition cursor-pointer shrink-0"
                            :class="optimizations.http2_multiplexing ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-200 text-slate-600 border-slate-300'"
                        >
                            {{ optimizations.http2_multiplexing ? 'ENABLED' : 'DISABLED' }}
                        </button>
                    </div>

                    <!-- Toggle 5: Minify HTML/CSS/JS -->
                    <div class="p-3 bg-slate-50/80 rounded border border-slate-200/80 flex items-start justify-between gap-3">
                        <div class="space-y-0.5">
                            <span class="text-xs font-bold text-slate-800 block">Minify HTML/CSS/JS Assets</span>
                            <p class="text-[10.5px] text-slate-500">Strips redundant whitespace and comments automatically.</p>
                        </div>
                        <button 
                            @click="toggleOptimization('minify_assets', optimizations.minify_assets)"
                            :disabled="activeTogglingFeature !== null"
                            class="text-[10px] font-bold px-2 py-0.5 rounded border uppercase transition cursor-pointer shrink-0"
                            :class="optimizations.minify_assets ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-200 text-slate-600 border-slate-300'"
                        >
                            {{ optimizations.minify_assets ? 'ENABLED' : 'DISABLED' }}
                        </button>
                    </div>

                    <!-- Toggle 6: TCP Keep-Alive -->
                    <div class="p-3 bg-slate-50/80 rounded border border-slate-200/80 flex items-start justify-between gap-3">
                        <div class="space-y-0.5">
                            <span class="text-xs font-bold text-slate-800 block">TCP Keep-Alive Persistent Pool</span>
                            <p class="text-[10.5px] text-slate-500">Eliminates repeated TCP handshake overheads.</p>
                        </div>
                        <button 
                            @click="toggleOptimization('keep_alive', optimizations.keep_alive)"
                            :disabled="activeTogglingFeature !== null"
                            class="text-[10px] font-bold px-2 py-0.5 rounded border uppercase transition cursor-pointer shrink-0"
                            :class="optimizations.keep_alive ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-200 text-slate-600 border-slate-300'"
                        >
                            {{ optimizations.keep_alive ? 'ENABLED' : 'DISABLED' }}
                        </button>
                    </div>

                </div>
            </div>

            <!-- 4. Performance Audit Breakdown Table -->
            <div class="bg-white rounded-lg border border-slate-200 shadow-2xs overflow-hidden">
                <div class="px-5 py-3.5 border-b border-slate-100 flex items-center justify-between bg-slate-50/60">
                    <div class="flex items-center gap-2">
                        <CheckCircleIcon class="w-4 h-4 text-emerald-600" />
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800">
                            Lighthouse Diagnostic Audits ({{ audits.length }})
                        </h3>
                    </div>
                    <span class="text-xs text-slate-500 font-mono">
                        Audited on {{ lastAuditTime }}
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs whitespace-nowrap">
                        <thead class="bg-slate-50 text-slate-600 font-bold border-b border-slate-200">
                            <tr>
                                <th class="py-2.5 px-4">Audit Item</th>
                                <th class="py-2.5 px-4">Measured Metric</th>
                                <th class="py-2.5 px-4">Status</th>
                                <th class="py-2.5 px-4">Details</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            <tr v-for="audit in audits" :key="audit.id" class="hover:bg-slate-50/70 transition">
                                <td class="py-3 px-4 font-bold text-slate-900">
                                    {{ audit.title }}
                                </td>
                                <td class="py-3 px-4 font-mono font-bold text-blue-700">
                                    {{ audit.display_value }}
                                </td>
                                <td class="py-3 px-4">
                                    <span 
                                        class="px-2 py-0.5 rounded-[3px] text-[10.5px] font-bold uppercase border"
                                        :class="audit.score >= 90 ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-amber-50 text-amber-700 border-amber-200'"
                                    >
                                        {{ audit.score }} Passed
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-slate-500 font-sans text-[11px] max-w-md truncate">
                                    {{ audit.description }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>
