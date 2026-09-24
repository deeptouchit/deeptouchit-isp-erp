<script setup>
import { ref, computed } from 'vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import { 
    GlobeAltIcon, 
    CheckCircleIcon, 
    ArrowPathIcon,
    ShieldCheckIcon,
    BoltIcon,
    SparklesIcon,
    RocketLaunchIcon,
    ServerIcon,
    TrashIcon,
    ClockIcon,
    CpuChipIcon,
    AdjustmentsHorizontalIcon,
    XMarkIcon,
    SignalIcon
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
    currentWebsite: {
        type: Object,
        default: null
    },
    stats: {
        type: Object,
        default: () => ({
            total_requests: 148920,
            cached_requests: 137450,
            cache_hit_rate: 92.3,
            bandwidth_saved_gb: 16.4,
            origin_bandwidth_gb: 2.8,
            ddos_attacks_mitigated: 42,
            ssl_edge_status: 'TLS 1.3 Active',
            last_purged_at: ''
        })
    },
    edgeNodes: {
        type: Array,
        default: () => []
    }
})

// Active Domain selection
const selectedDomain = ref(props.currentWebsite?.domain || props.subscription?.domain || '')
const changeDomain = () => {
    router.get(route('performance.cdn', { domain: selectedDomain.value }), {}, {
        preserveScroll: true
    })
}

// 1. Master CDN Toggle (ON / OFF)
const toggleCdnForm = useForm({
    website_id: props.currentWebsite?.id,
    enabled: true
})

const isTogglingCdn = ref(false)

const toggleCdn = () => {
    if (!props.currentWebsite) return
    isTogglingCdn.value = true
    toggleCdnForm.website_id = props.currentWebsite.id
    toggleCdnForm.enabled = !props.currentWebsite.cdn_enabled
    toggleCdnForm.post(route('performance.cdn.toggle'), {
        preserveScroll: true,
        onFinish: () => {
            isTogglingCdn.value = false
        }
    })
}

// 2. Purge Edge Cache
const showPurgeModal = ref(false)
const purgeForm = useForm({
    website_id: props.currentWebsite?.id,
    purge_type: 'everything'
})

const submitPurge = () => {
    if (!props.currentWebsite) return
    purgeForm.website_id = props.currentWebsite.id
    purgeForm.post(route('performance.cdn.purge'), {
        preserveScroll: true,
        onSuccess: () => {
            showPurgeModal.value = false
        }
    })
}

// 3. Settings Form (Dev Mode, Always Online, Brotli, WAF, TTL)
const settingsForm = useForm({
    website_id: props.currentWebsite?.id,
    cdn_dev_mode: props.currentWebsite?.cdn_dev_mode ?? false,
    cdn_always_online: props.currentWebsite?.cdn_always_online ?? true,
    cdn_brotli: props.currentWebsite?.cdn_brotli ?? true,
    cdn_waf_enabled: props.currentWebsite?.cdn_waf_enabled ?? true,
    cdn_cache_level: props.currentWebsite?.cdn_cache_level ?? 'standard',
    cdn_browser_ttl: props.currentWebsite?.cdn_browser_ttl ?? 14400,
})

const updateSetting = (field, value) => {
    if (!props.currentWebsite) return
    settingsForm.website_id = props.currentWebsite.id
    settingsForm[field] = value
    settingsForm.post(route('performance.cdn.update-settings'), {
        preserveScroll: true
    })
}
</script>

<template>
    <Head title="Global Edge CDN & Caching - DeepTouch Host" />

    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto space-y-4">
            
            <!-- 1. Page Header with Breadcrumbs & Actions -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Performance', href: '#' },
                    { label: 'Global Edge CDN' }
                ]"
                :show-refresh="true"
                @refreshed="router.reload({ preserveScroll: true })"
            >
                <template #actions>
                    <!-- Domain Switcher -->
                    <div class="flex items-center gap-1.5">
                        <select 
                            v-model="selectedDomain" 
                            @change="changeDomain"
                            class="text-xs font-bold rounded-[3px] border border-slate-300 bg-white py-1 px-2.5 text-slate-800 focus:ring-blue-500 focus:border-blue-500 shadow-2xs cursor-pointer"
                        >
                            <option v-for="site in websites" :key="site.id" :value="site.domain">
                                {{ site.domain }}
                            </option>
                        </select>
                    </div>

                    <!-- Purge Cache Button -->
                    <button 
                        @click="showPurgeModal = true"
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        <TrashIcon class="w-3.5 h-3.5 text-rose-500" />
                        <span>Purge Edge Cache</span>
                    </button>
                </template>
            </PageHeader>

            <!-- 2. Master CDN Status & ON/OFF Hero Banner -->
            <div 
                class="rounded-lg border p-5 shadow-2xs transition-all duration-300 flex flex-col md:flex-row items-start md:items-center justify-between gap-4"
                :class="currentWebsite?.cdn_enabled ? 'bg-gradient-to-r from-blue-900 via-indigo-900 to-slate-900 border-blue-800 text-white' : 'bg-white border-slate-200 text-slate-900'"
            >
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <span 
                            class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-[3px] text-[11px] font-bold uppercase tracking-wide border font-mono"
                            :class="currentWebsite?.cdn_enabled ? 'bg-emerald-500/20 text-emerald-300 border-emerald-400/30' : 'bg-slate-100 text-slate-600 border-slate-200'"
                        >
                            <span class="w-2 h-2 rounded-full" :class="currentWebsite?.cdn_enabled ? 'bg-emerald-400 animate-pulse' : 'bg-slate-400'"></span>
                            <span>{{ currentWebsite?.cdn_enabled ? 'CDN ACCELERATION ACTIVE' : 'CDN PAUSED (ORIGIN MODE)' }}</span>
                        </span>
                        <span v-if="currentWebsite?.cdn_enabled" class="text-xs text-blue-200 font-mono">
                            285+ Anycast PoPs
                        </span>
                    </div>

                    <h2 class="text-lg font-black tracking-tight" :class="currentWebsite?.cdn_enabled ? 'text-white' : 'text-slate-900'">
                        {{ currentWebsite?.domain }}
                    </h2>
                    <p class="text-xs" :class="currentWebsite?.cdn_enabled ? 'text-slate-300' : 'text-slate-500'">
                        {{ currentWebsite?.cdn_enabled 
                            ? 'All static assets, HTML bytecode, and media files are cached & routed through BDIX Local Edge + Global Anycast PoPs.' 
                            : 'Edge caching is bypassed. Visitors connect directly to the origin server. Ideal for development & debugging.' }}
                    </p>
                </div>

                <!-- Master Toggle Switch Button -->
                <div class="flex items-center gap-3 shrink-0 self-end md:self-center">
                    <span class="text-xs font-bold uppercase tracking-wider font-mono" :class="currentWebsite?.cdn_enabled ? 'text-blue-200' : 'text-slate-600'">
                        {{ currentWebsite?.cdn_enabled ? 'CDN ON' : 'CDN OFF' }}
                    </span>

                    <button 
                        type="button" 
                        @click="toggleCdn"
                        :disabled="isTogglingCdn"
                        class="relative inline-flex h-7 w-14 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none disabled:opacity-50"
                        :class="currentWebsite?.cdn_enabled ? 'bg-emerald-500' : 'bg-slate-300'"
                    >
                        <span 
                            class="pointer-events-none inline-block h-6 w-6 transform rounded-full bg-white shadow-lg ring-0 transition duration-200 ease-in-out"
                            :class="currentWebsite?.cdn_enabled ? 'translate-x-7' : 'translate-x-0'"
                        ></span>
                    </button>
                </div>
            </div>

            <!-- 3. Top CDN Analytics Cards Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                
                <!-- Card 1: Bandwidth Saved -->
                <div class="bg-white rounded-lg border border-slate-200 p-4 shadow-2xs space-y-2.5">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                            <SparklesIcon class="w-4 h-4 text-blue-600" />
                            <span>Bandwidth Offloaded</span>
                        </span>
                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-blue-50 text-blue-700 border border-blue-200 uppercase font-mono">
                            85.4% Saved
                        </span>
                    </div>

                    <div>
                        <div class="flex items-baseline justify-between">
                            <span class="text-2xl font-black text-slate-900 font-mono">{{ stats.bandwidth_saved_gb }} GB</span>
                            <span class="text-xs text-slate-500 font-mono">{{ stats.origin_bandwidth_gb }} GB Origin</span>
                        </div>
                        <div class="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden mt-2">
                            <div class="bg-blue-600 h-full rounded-full w-[85%]"></div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between text-[11px] text-slate-500 pt-1 border-t border-slate-100">
                        <span>Direct Peering:</span>
                        <strong class="text-slate-700">10 Gbps BDIX Backbone</strong>
                    </div>
                </div>

                <!-- Card 2: Cache Hit Rate -->
                <div class="bg-white rounded-lg border border-slate-200 p-4 shadow-2xs space-y-2.5">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                            <BoltIcon class="w-4 h-4 text-emerald-600" />
                            <span>Edge Cache Hit Ratio</span>
                        </span>
                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-200 uppercase font-mono">
                            OPTIMAL
                        </span>
                    </div>

                    <div>
                        <div class="flex items-baseline justify-between">
                            <span class="text-2xl font-black text-emerald-600 font-mono">{{ stats.cache_hit_rate }}%</span>
                            <span class="text-xs text-slate-500 font-mono">{{ stats.cached_requests.toLocaleString() }} Hits</span>
                        </div>
                        <div class="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden mt-2">
                            <div class="bg-emerald-500 h-full rounded-full" :style="{ width: `${stats.cache_hit_rate}%` }"></div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between text-[11px] text-slate-500 pt-1 border-t border-slate-100">
                        <span>Total Edge Traffic:</span>
                        <strong class="text-slate-700 font-mono">{{ stats.total_requests.toLocaleString() }} Requests</strong>
                    </div>
                </div>

                <!-- Card 3: Security & DDoS Shield -->
                <div class="bg-white rounded-lg border border-slate-200 p-4 shadow-2xs space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                            <ShieldCheckIcon class="w-4 h-4 text-purple-600" />
                            <span>Edge Security Shield</span>
                        </span>
                        <span class="text-[10px] font-bold text-slate-500 uppercase">
                            WAF / SSL
                        </span>
                    </div>

                    <div class="space-y-1 text-xs font-mono">
                        <div class="flex items-center justify-between p-1.5 bg-slate-50 rounded border border-slate-200/70">
                            <span class="text-slate-500 text-[11px]">Layer 7 Mitigated:</span>
                            <span class="font-bold text-purple-700">{{ stats.ddos_attacks_mitigated }} Threats</span>
                        </div>

                        <div class="flex items-center justify-between p-1.5 bg-slate-50 rounded border border-slate-200/70">
                            <span class="text-slate-500 text-[11px]">Edge SSL Cipher:</span>
                            <span class="font-bold text-emerald-700">TLS 1.3 / HTTP/3</span>
                        </div>
                    </div>
                </div>

            </div>

            <!-- 4. CDN Edge Caching Configuration & Feature Toggles -->
            <div class="bg-white rounded-lg border border-slate-200 p-5 shadow-2xs space-y-3">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div class="flex items-center gap-2">
                        <AdjustmentsHorizontalIcon class="w-5 h-5 text-blue-600" />
                        <div>
                            <h3 class="font-bold text-slate-900 text-xs uppercase tracking-wider">
                                Edge Caching & Delivery Controls
                            </h3>
                            <p class="text-[11px] text-slate-500">
                                Configure how Edge PoPs cache and deliver content for {{ currentWebsite?.domain }}.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 pt-1 text-xs">
                    
                    <!-- Feature 1: Development Mode -->
                    <div class="p-3 bg-slate-50/80 rounded border border-slate-200/80 flex items-start justify-between gap-3">
                        <div class="space-y-0.5">
                            <span class="font-bold text-slate-800 block">Development Mode</span>
                            <p class="text-[10.5px] text-slate-500">Bypasses edge cache so you immediately see local asset changes.</p>
                        </div>
                        <button 
                            @click="updateSetting('cdn_dev_mode', !currentWebsite?.cdn_dev_mode)"
                            class="text-[10px] font-bold px-2 py-0.5 rounded border uppercase transition cursor-pointer shrink-0"
                            :class="currentWebsite?.cdn_dev_mode ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-slate-200 text-slate-600 border-slate-300'"
                        >
                            {{ currentWebsite?.cdn_dev_mode ? 'ACTIVE' : 'OFF' }}
                        </button>
                    </div>

                    <!-- Feature 2: Always Online -->
                    <div class="p-3 bg-slate-50/80 rounded border border-slate-200/80 flex items-start justify-between gap-3">
                        <div class="space-y-0.5">
                            <span class="font-bold text-slate-800 block">Always Online™ Failover</span>
                            <p class="text-[10.5px] text-slate-500">Serves cached pages if the origin server is restarting or down.</p>
                        </div>
                        <button 
                            @click="updateSetting('cdn_always_online', !currentWebsite?.cdn_always_online)"
                            class="text-[10px] font-bold px-2 py-0.5 rounded border uppercase transition cursor-pointer shrink-0"
                            :class="currentWebsite?.cdn_always_online ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-200 text-slate-600 border-slate-300'"
                        >
                            {{ currentWebsite?.cdn_always_online ? 'ENABLED' : 'DISABLED' }}
                        </button>
                    </div>

                    <!-- Feature 3: Brotli Compression -->
                    <div class="p-3 bg-slate-50/80 rounded border border-slate-200/80 flex items-start justify-between gap-3">
                        <div class="space-y-0.5">
                            <span class="font-bold text-slate-800 block">Brotli Edge Compression</span>
                            <p class="text-[10.5px] text-slate-500">Compresses text assets using Google Brotli at edge nodes.</p>
                        </div>
                        <button 
                            @click="updateSetting('cdn_brotli', !currentWebsite?.cdn_brotli)"
                            class="text-[10px] font-bold px-2 py-0.5 rounded border uppercase transition cursor-pointer shrink-0"
                            :class="currentWebsite?.cdn_brotli ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-200 text-slate-600 border-slate-300'"
                        >
                            {{ currentWebsite?.cdn_brotli ? 'ENABLED' : 'DISABLED' }}
                        </button>
                    </div>

                    <!-- Feature 4: Edge WAF -->
                    <div class="p-3 bg-slate-50/80 rounded border border-slate-200/80 flex items-start justify-between gap-3">
                        <div class="space-y-0.5">
                            <span class="font-bold text-slate-800 block">Edge WAF & Bot Blocker</span>
                            <p class="text-[10.5px] text-slate-500">Filters malicious scrapers, SQLi, and Layer 7 bot attacks.</p>
                        </div>
                        <button 
                            @click="updateSetting('cdn_waf_enabled', !currentWebsite?.cdn_waf_enabled)"
                            class="text-[10px] font-bold px-2 py-0.5 rounded border uppercase transition cursor-pointer shrink-0"
                            :class="currentWebsite?.cdn_waf_enabled ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-200 text-slate-600 border-slate-300'"
                        >
                            {{ currentWebsite?.cdn_waf_enabled ? 'ENABLED' : 'DISABLED' }}
                        </button>
                    </div>

                    <!-- Feature 5: Cache Level -->
                    <div class="p-3 bg-slate-50/80 rounded border border-slate-200/80 flex items-start justify-between gap-3">
                        <div class="space-y-0.5">
                            <span class="font-bold text-slate-800 block">Caching Level</span>
                            <p class="text-[10.5px] text-slate-500">Standard caches query strings; Aggressive caches all static files.</p>
                        </div>
                        <select 
                            :value="currentWebsite?.cdn_cache_level || 'standard'"
                            @change="updateSetting('cdn_cache_level', $event.target.value)"
                            class="text-[11px] font-bold rounded-[3px] border border-slate-300 bg-white py-1 px-2 text-slate-800"
                        >
                            <option value="standard">Standard</option>
                            <option value="aggressive">Aggressive</option>
                            <option value="bypass">Bypass</option>
                        </select>
                    </div>

                    <!-- Feature 6: Browser Cache TTL -->
                    <div class="p-3 bg-slate-50/80 rounded border border-slate-200/80 flex items-start justify-between gap-3">
                        <div class="space-y-0.5">
                            <span class="font-bold text-slate-800 block">Browser Cache TTL</span>
                            <p class="text-[10.5px] text-slate-500">Determines how long visitor browsers keep cached assets.</p>
                        </div>
                        <select 
                            :value="currentWebsite?.cdn_browser_ttl || 14400"
                            @change="updateSetting('cdn_browser_ttl', Number($event.target.value))"
                            class="text-[11px] font-bold rounded-[3px] border border-slate-300 bg-white py-1 px-2 text-slate-800"
                        >
                            <option :value="14400">4 Hours</option>
                            <option :value="86400">1 Day</option>
                            <option :value="604800">7 Days</option>
                            <option :value="31536000">1 Year (Immutable)</option>
                        </select>
                    </div>

                </div>
            </div>

            <!-- 5. Global Edge PoP Status Table -->
            <div class="bg-white rounded-lg border border-slate-200 shadow-2xs overflow-hidden">
                <div class="px-5 py-3.5 border-b border-slate-100 flex items-center justify-between bg-slate-50/60">
                    <div class="flex items-center gap-2">
                        <GlobeAltIcon class="w-4 h-4 text-blue-600" />
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800">
                            Global Edge PoP Routing Nodes ({{ edgeNodes.length }})
                        </h3>
                    </div>
                    <span class="text-xs text-slate-500 font-mono">
                        Anycast Routing Active
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs whitespace-nowrap">
                        <thead class="bg-slate-50 text-slate-600 font-bold border-b border-slate-200">
                            <tr>
                                <th class="py-2.5 px-4">Edge Location</th>
                                <th class="py-2.5 px-4">PoP Identifier</th>
                                <th class="py-2.5 px-4">Latency</th>
                                <th class="py-2.5 px-4">Routing Role</th>
                                <th class="py-2.5 px-4 text-right">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-mono">
                            <tr v-for="node in edgeNodes" :key="node.pop_code" class="hover:bg-slate-50/70 transition">
                                <td class="py-3 px-4 font-bold text-slate-900 font-sans">
                                    {{ node.location }}
                                </td>
                                <td class="py-3 px-4 text-blue-700">
                                    {{ node.pop_code }}
                                </td>
                                <td class="py-3 px-4 font-bold" :class="parseInt(node.latency) < 50 ? 'text-emerald-600' : 'text-slate-700'">
                                    {{ node.latency }}
                                </td>
                                <td class="py-3 px-4 font-sans text-slate-600">
                                    {{ node.role }}
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-200 uppercase font-sans">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        <span>{{ node.status }}</span>
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <!-- Purge Cache Modal -->
        <div v-if="showPurgeModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-2xs flex items-center justify-center p-4 z-50">
            <div class="bg-white rounded-lg max-w-md w-full p-5 shadow-xl space-y-4 border border-slate-200">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div class="flex items-center gap-2">
                        <TrashIcon class="w-5 h-5 text-rose-600" />
                        <h3 class="font-bold text-slate-900 text-sm">Purge CDN Edge Cache</h3>
                    </div>
                    <button @click="showPurgeModal = false" class="text-slate-400 hover:text-slate-600 p-1 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <div class="text-xs text-slate-600 space-y-2.5">
                    <p>
                        Purging the cache will instantly invalidate all cached HTML, images, CSS, and JS files across all <strong>285+ Global Anycast Edge PoPs</strong> for <strong class="text-slate-900">{{ currentWebsite?.domain }}</strong>.
                    </p>
                    <p class="p-2.5 bg-amber-50 rounded border border-amber-200 text-amber-900 text-[11px]">
                        <strong>Notice:</strong> Subsequent requests will be fetched directly from origin until new cache is populated.
                    </p>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                    <button 
                        type="button" 
                        @click="showPurgeModal = false" 
                        class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-[3px] text-xs font-bold cursor-pointer"
                    >
                        Cancel
                    </button>
                    <button 
                        type="button"
                        @click="submitPurge"
                        :disabled="purgeForm.processing"
                        class="px-4 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-[3px] text-xs font-bold shadow-2xs transition cursor-pointer disabled:opacity-50"
                    >
                        {{ purgeForm.processing ? 'Purging Edge...' : 'Purge Everything' }}
                    </button>
                </div>
            </div>
        </div>

    </AuthenticatedLayout>
</template>
