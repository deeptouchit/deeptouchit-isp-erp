<script setup>
import { ref, computed } from 'vue'
import { Head, router, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import { 
    BoltIcon, 
    SparklesIcon, 
    ArrowPathIcon, 
    CheckIcon, 
    ServerIcon, 
    ShieldCheckIcon,
    TrashIcon,
    CircleStackIcon,
    CpuChipIcon,
    MagnifyingGlassIcon,
    ClipboardDocumentIcon,
    InformationCircleIcon,
    XMarkIcon,
    ExclamationTriangleIcon,
    ArrowUturnLeftIcon,
    CodeBracketIcon,
    CheckCircleIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    allowRedis: {
        type: Boolean,
        default: true
    },
    redisMemoryLimit: {
        type: Number,
        default: 64
    },
    redis: {
        type: Object,
        default: () => ({
            status: 'online',
            version: '7.0',
            host: '127.0.0.1:6379',
            used_memory: '1.2M',
            peak_memory: '2.5M',
            total_keys: 5,
            clients: 1,
            uptime_days: 1,
            hit_rate: '99.2%'
        })
    },
    sampleKeys: {
        type: Array,
        default: () => []
    },
    opcache: {
        type: Object,
        default: () => ({
            status: 'enabled',
            used_memory_mb: 45.2,
            total_memory_mb: 128,
            cached_scripts: 1420,
            hit_rate: 98.4,
            wasted_memory_mb: 0.8,
            jit_enabled: true
        })
    },
    appCache: {
        type: Object,
        default: () => ({
            view_cache: 'Active',
            cache_driver: 'redis',
            session_driver: 'redis',
            domain: 'somitysoft.com'
        })
    }
})

// Active Tab
const activeTab = ref('keys') // 'keys' | 'opcache' | 'guide'

// Search Keys
const keySearch = ref('')
const filteredKeys = computed(() => {
    if (!keySearch.value.trim()) return props.sampleKeys
    const q = keySearch.value.toLowerCase().trim()
    return props.sampleKeys.filter(k => 
        k.key.toLowerCase().includes(q) || 
        k.type.toLowerCase().includes(q)
    )
})

// Modals
const showPurgeAllModal = ref(false)
const showDeleteKeyModal = ref(false)
const deletingKey = ref(null)

// Action loaders
const purgingType = ref(null) // 'all' | 'redis' | 'opcache' | 'app'

// Copy helper
const copiedField = ref(null)
const copyToClipboard = (text, fieldName) => {
    navigator.clipboard.writeText(text)
    copiedField.value = fieldName
    setTimeout(() => {
        copiedField.value = null
    }, 2000)
}

// Purge Handlers
const executePurgeAll = () => {
    purgingType.value = 'all'
    router.post(route('advanced.cache.purge-all'), {}, {
        preserveScroll: true,
        onFinish: () => {
            purgingType.value = null
            showPurgeAllModal.value = false
        }
    })
}

const executePurgeRedis = () => {
    purgingType.value = 'redis'
    router.post(route('advanced.cache.purge-redis'), {}, {
        preserveScroll: true,
        onFinish: () => {
            purgingType.value = null
        }
    })
}

const executePurgeOpcache = () => {
    purgingType.value = 'opcache'
    router.post(route('advanced.cache.purge-opcache'), {}, {
        preserveScroll: true,
        onFinish: () => {
            purgingType.value = null
        }
    })
}

const executePurgeApp = () => {
    purgingType.value = 'app'
    router.post(route('advanced.cache.purge-app'), {}, {
        preserveScroll: true,
        onFinish: () => {
            purgingType.value = null
        }
    })
}

const confirmDeleteKey = (k) => {
    deletingKey.value = k
    showDeleteKeyModal.value = true
}

const executeDeleteKey = () => {
    if (!deletingKey.value) return
    router.post(route('advanced.cache.delete-key'), {
        key: deletingKey.value.key
    }, {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteKeyModal.value = false
            deletingKey.value = null
        }
    })
}

// Key badge class
const getKeyTypeBadge = (type) => {
    switch(type) {
        case 'string': return 'bg-blue-50 text-blue-700 border-blue-200'
        case 'hash': return 'bg-purple-50 text-purple-700 border-purple-200'
        case 'set': return 'bg-emerald-50 text-emerald-700 border-emerald-200'
        case 'zset': return 'bg-amber-50 text-amber-700 border-amber-200'
        case 'list': return 'bg-indigo-50 text-indigo-700 border-indigo-200'
        default: return 'bg-slate-50 text-slate-700 border-slate-200'
    }
}
</script>

<template>
    <Head title="Cache & Acceleration Manager - DeepTouch Host" />

    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto space-y-4">
            
            <!-- 1. Page Header with Breadcrumbs & Actions -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Advanced Tools', href: '#' },
                    { label: 'Cache & Acceleration' }
                ]"
                :show-refresh="true"
                @refreshed="router.reload({ preserveScroll: true })"
            >
                <template #actions>
                    <button 
                        @click="showPurgeAllModal = true"
                        class="px-3.5 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-[3px] text-xs font-bold flex items-center gap-1.5 shadow-2xs transition cursor-pointer"
                    >
                        <TrashIcon class="w-3.5 h-3.5 stroke-[2.5]" />
                        <span>Purge All Caches</span>
                    </button>
                </template>
            </PageHeader>

            <!-- 2. Top 3 KPI / Cache Acceleration Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                
                <!-- Card 1: Redis In-Memory Cache -->
                <div class="bg-white border border-slate-200 rounded-lg p-4 shadow-2xs space-y-2.5 relative overflow-hidden">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                            <CircleStackIcon class="w-4 h-4 text-rose-600" />
                            <span>Redis In-Memory Engine</span>
                        </span>
                        <span 
                            v-if="allowRedis"
                            class="text-[10px] font-bold px-1.5 py-0.5 rounded font-mono uppercase"
                            :class="redis.status === 'online' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200'"
                        >
                            {{ redis.status }}
                        </span>
                        <span 
                            v-else
                            class="text-[10px] font-black px-1.5 py-0.5 rounded font-mono uppercase bg-amber-50 text-amber-700 border border-amber-200 flex items-center gap-1"
                        >
                            <span>Locked</span>
                        </span>
                    </div>

                    <div v-if="allowRedis">
                        <div class="flex items-baseline gap-2">
                            <span class="text-2xl font-black text-slate-900 font-mono">{{ redis.used_memory }}</span>
                            <span class="text-xs text-slate-400 font-bold font-mono">/ {{ redis.total_keys }} Keys (Max {{ redisMemoryLimit }} MB)</span>
                        </div>
                        <p class="text-[11px] text-slate-500 mt-1 font-mono">
                            Host: <strong class="text-slate-800">{{ redis.host }}</strong> (v{{ redis.version }})
                        </p>
                    </div>
                    <div v-else class="space-y-1">
                        <div class="flex items-baseline gap-2">
                            <span class="text-lg font-black text-slate-400 font-mono">Plan Upgrade Required</span>
                        </div>
                        <p class="text-[11px] text-slate-500">
                            Redis caching is not included in your active plan.
                        </p>
                    </div>

                    <div class="flex items-center justify-between text-[11px] text-slate-500 pt-1 border-t border-slate-100 font-mono">
                        <span>{{ allowRedis ? 'Hit Rate:' : 'Package Feature:' }}</span>
                        <strong :class="allowRedis ? 'text-emerald-600' : 'text-slate-400'">{{ allowRedis ? redis.hit_rate : 'Disabled' }}</strong>
                    </div>
                </div>

                <!-- Card 2: PHP Zend OPcache -->
                <div class="bg-white border border-slate-200 rounded-lg p-4 shadow-2xs space-y-2.5">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                            <CpuChipIcon class="w-4 h-4 text-blue-600" />
                            <span>Zend OPcache Byte-code</span>
                        </span>
                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-blue-50 text-blue-700 border border-blue-200 font-mono">
                            {{ opcache.status === 'enabled' ? 'ACTIVE' : 'OFF' }}
                        </span>
                    </div>

                    <div>
                        <div class="flex items-baseline gap-2">
                            <span class="text-2xl font-black text-slate-900 font-mono">{{ opcache.used_memory_mb }} MB</span>
                            <span class="text-xs text-slate-400 font-bold font-mono">/ {{ opcache.total_memory_mb }} MB</span>
                        </div>
                        <p class="text-[11px] text-slate-500 mt-1 font-mono">
                            Precompiled Scripts: <strong class="text-slate-800">{{ opcache.cached_scripts }} files</strong>
                        </p>
                    </div>

                    <div class="flex items-center justify-between text-[11px] text-slate-500 pt-1 border-t border-slate-100 font-mono">
                        <span>JIT Compiler:</span>
                        <strong class="text-slate-700">{{ opcache.jit_enabled ? 'Enabled (CR_JIT)' : 'Standard' }}</strong>
                    </div>
                </div>

                <!-- Card 3: Application & Template Layer -->
                <div class="bg-gradient-to-br from-indigo-50/70 to-blue-50/50 border border-indigo-100 rounded-lg p-4 shadow-2xs flex flex-col justify-between space-y-2.5">
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-indigo-900 flex items-center gap-1.5">
                                <SparklesIcon class="w-4 h-4 text-indigo-600" />
                                <span>Application & View Caching</span>
                            </span>
                            <span class="text-[10px] font-bold px-1.5 py-0.5 bg-indigo-100 text-indigo-800 rounded font-mono">
                                HIGH-SPEED
                            </span>
                        </div>
                        <p class="text-[11px] text-slate-600 mt-1 line-clamp-2">
                            Compiled Blade views, Laravel route dispatch trees, and Redis session store.
                        </p>
                    </div>

                    <div class="pt-2 border-t border-indigo-100 flex items-center justify-between text-[11px] text-indigo-900 font-mono">
                        <span>Drivers:</span>
                        <span class="font-bold">Cache: {{ appCache.cache_driver }} • Session: {{ appCache.session_driver }}</span>
                    </div>
                </div>

            </div>

            <!-- 3. Three Individual Purge Action Boxes -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                
                <!-- Action 1: Purge Redis -->
                <div class="bg-white border border-slate-200 rounded-lg p-3.5 shadow-2xs flex items-center justify-between">
                    <div class="space-y-0.5">
                        <h4 class="text-xs font-bold text-slate-900">Redis Object Cache</h4>
                        <p class="text-[11px] text-slate-500">Flush in-memory queries & sessions</p>
                    </div>
                    <button 
                        @click="executePurgeRedis"
                        :disabled="purgingType === 'redis'"
                        class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-800 rounded-[3px] text-xs font-bold transition cursor-pointer disabled:opacity-50 flex items-center gap-1"
                    >
                        <ArrowPathIcon class="w-3.5 h-3.5" :class="{ 'animate-spin': purgingType === 'redis' }" />
                        <span>Purge Redis</span>
                    </button>
                </div>

                <!-- Action 2: Purge OPcache -->
                <div class="bg-white border border-slate-200 rounded-lg p-3.5 shadow-2xs flex items-center justify-between">
                    <div class="space-y-0.5">
                        <h4 class="text-xs font-bold text-slate-900">Zend OPcache</h4>
                        <p class="text-[11px] text-slate-500">Reset compiled PHP byte-code</p>
                    </div>
                    <button 
                        @click="executePurgeOpcache"
                        :disabled="purgingType === 'opcache'"
                        class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-800 rounded-[3px] text-xs font-bold transition cursor-pointer disabled:opacity-50 flex items-center gap-1"
                    >
                        <ArrowPathIcon class="w-3.5 h-3.5" :class="{ 'animate-spin': purgingType === 'opcache' }" />
                        <span>Reset OPcache</span>
                    </button>
                </div>

                <!-- Action 3: Purge App Cache -->
                <div class="bg-white border border-slate-200 rounded-lg p-3.5 shadow-2xs flex items-center justify-between">
                    <div class="space-y-0.5">
                        <h4 class="text-xs font-bold text-slate-900">App Views & Routes</h4>
                        <p class="text-[11px] text-slate-500">Clear blade templates & config</p>
                    </div>
                    <button 
                        @click="executePurgeApp"
                        :disabled="purgingType === 'app'"
                        class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-800 rounded-[3px] text-xs font-bold transition cursor-pointer disabled:opacity-50 flex items-center gap-1"
                    >
                        <ArrowPathIcon class="w-3.5 h-3.5" :class="{ 'animate-spin': purgingType === 'app' }" />
                        <span>Clear Views</span>
                    </button>
                </div>

            </div>

            <!-- 4. Main Navigation Tabs -->
            <div class="border-b border-slate-200 flex items-center gap-4 text-xs font-bold">
                <button 
                    @click="activeTab = 'keys'"
                    class="pb-2.5 border-b-2 transition cursor-pointer flex items-center gap-1.5"
                    :class="activeTab === 'keys' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-800'"
                >
                    <CircleStackIcon class="w-4 h-4" />
                    <span>Redis Key Browser ({{ sampleKeys.length }})</span>
                </button>

                <button 
                    @click="activeTab = 'opcache'"
                    class="pb-2.5 border-b-2 transition cursor-pointer flex items-center gap-1.5"
                    :class="activeTab === 'opcache' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-800'"
                >
                    <CpuChipIcon class="w-4 h-4" />
                    <span>OPcache Allocation & Health</span>
                </button>

                <button 
                    @click="activeTab = 'guide'"
                    class="pb-2.5 border-b-2 transition cursor-pointer flex items-center gap-1.5"
                    :class="activeTab === 'guide' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-800'"
                >
                    <CodeBracketIcon class="w-4 h-4" />
                    <span>Integration Snippets (WordPress & Laravel)</span>
                </button>
            </div>

            <!-- 5. TAB 1: Redis Key Browser Table -->
            <div v-if="activeTab === 'keys'" class="bg-white border border-slate-200 rounded-lg shadow-2xs overflow-hidden">
                
                <div class="px-4 py-3 bg-slate-50/80 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <CircleStackIcon class="w-4 h-4 text-rose-600" />
                        <span class="text-xs font-bold text-slate-900">Redis In-Memory Key Store</span>
                        <span class="text-[11px] text-slate-400 font-mono">({{ filteredKeys.length }} keys)</span>
                    </div>

                    <div class="relative w-full sm:w-64">
                        <MagnifyingGlassIcon class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-1/2 -translate-y-1/2" />
                        <input 
                            v-model="keySearch"
                            type="text" 
                            placeholder="Filter Redis keys..." 
                            class="w-full pl-8 pr-2.5 py-1.5 bg-white border border-slate-300 rounded-[3px] text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-blue-500 font-medium shadow-2xs"
                        />
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="filteredKeys.length === 0" class="p-12 text-center text-slate-500 text-xs">
                    <CircleStackIcon class="w-12 h-12 text-slate-300 mx-auto mb-2" />
                    <h4 class="text-sm font-bold text-slate-900 mb-1">
                        {{ keySearch ? 'No Redis keys match your search' : 'No Keys in Redis Database' }}
                    </h4>
                    <p class="text-[11px] text-slate-400 max-w-sm mx-auto">
                        {{ keySearch ? 'Try clearing your search query.' : 'Redis in-memory store is currently empty or recently purged.' }}
                    </p>
                </div>

                <!-- Table Content -->
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-700">
                        <thead class="bg-slate-50/90 border-b border-slate-200 text-slate-500 uppercase font-bold tracking-wider text-[10.5px] select-none">
                            <tr>
                                <th class="py-2.5 px-4 w-28">Type</th>
                                <th class="py-2.5 px-4">Key Name</th>
                                <th class="py-2.5 px-4 w-32">Time To Live (TTL)</th>
                                <th class="py-2.5 px-4 w-24 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-medium">
                            <tr v-for="k in filteredKeys" :key="k.key" class="hover:bg-slate-50/80 transition-colors">
                                
                                <td class="py-2.5 px-4 font-mono">
                                    <span 
                                        class="px-2 py-0.5 rounded-[3px] font-bold border text-[10.5px] uppercase"
                                        :class="getKeyTypeBadge(k.type)"
                                    >
                                        {{ k.type }}
                                    </span>
                                </td>

                                <td class="py-2.5 px-4 font-mono font-bold text-slate-900 text-[11.5px]">
                                    <div class="flex items-center gap-1.5 max-w-xl truncate">
                                        <span class="truncate">{{ k.key }}</span>
                                        <button 
                                            @click="copyToClipboard(k.key, 'key_' + k.key)"
                                            class="text-slate-400 hover:text-blue-600 cursor-pointer shrink-0"
                                            title="Copy key"
                                        >
                                            <CheckIcon v-if="copiedField === 'key_' + k.key" class="w-3.5 h-3.5 text-emerald-600" />
                                            <ClipboardDocumentIcon v-else class="w-3.5 h-3.5" />
                                        </button>
                                    </div>
                                </td>

                                <td class="py-2.5 px-4 font-mono text-[11px] text-slate-500">
                                    {{ k.ttl }}
                                </td>

                                <td class="py-2.5 px-4 text-right">
                                    <button 
                                        @click="confirmDeleteKey(k)"
                                        title="Delete this key"
                                        class="p-1 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded transition cursor-pointer"
                                    >
                                        <TrashIcon class="w-4 h-4" />
                                    </button>
                                </td>

                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="px-4 py-2.5 bg-slate-50 border-t border-slate-200 text-[11px] text-slate-500 flex items-center justify-between">
                    <span>Redis v{{ redis.version }} standalone server running on localhost:6379.</span>
                    <span class="font-mono">Total DB Size: {{ redis.total_keys }} keys</span>
                </div>

            </div>

            <!-- 6. TAB 2: OPcache Allocation & Health -->
            <div v-else-if="activeTab === 'opcache'" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                
                <div class="bg-white border border-slate-200 rounded-lg p-5 shadow-2xs space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <div class="flex items-center gap-2">
                            <CpuChipIcon class="w-5 h-5 text-blue-600" />
                            <h3 class="font-bold text-slate-900 text-sm">OPcache Memory Consumption</h3>
                        </div>
                    </div>

                    <div class="space-y-3 text-xs">
                        <div>
                            <div class="flex items-center justify-between text-slate-600 mb-1 font-mono">
                                <span>Used Memory: <strong>{{ opcache.used_memory_mb }} MB</strong></span>
                                <span>Limit: <strong>{{ opcache.total_memory_mb }} MB</strong></span>
                            </div>
                            <div class="w-full bg-slate-100 h-3 rounded-full overflow-hidden flex">
                                <div 
                                    class="bg-blue-600 h-full rounded-full transition-all"
                                    :style="{ width: `${Math.min(100, Math.round((opcache.used_memory_mb / opcache.total_memory_mb) * 100))}%` }"
                                ></div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3 pt-2">
                            <div class="p-2.5 bg-slate-50 rounded border border-slate-200/60 font-mono">
                                <span class="text-slate-500 font-sans block text-[11px]">Cache Hit Rate</span>
                                <span class="text-lg font-black text-emerald-600">{{ opcache.hit_rate }}%</span>
                            </div>
                            <div class="p-2.5 bg-slate-50 rounded border border-slate-200/60 font-mono">
                                <span class="text-slate-500 font-sans block text-[11px]">Cached Scripts</span>
                                <span class="text-lg font-black text-slate-900">{{ opcache.cached_scripts }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white border border-slate-200 rounded-lg p-5 shadow-2xs space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <div class="flex items-center gap-2">
                            <SparklesIcon class="w-5 h-5 text-indigo-600" />
                            <h3 class="font-bold text-slate-900 text-sm">JIT Compiler Profile</h3>
                        </div>
                    </div>

                    <div class="space-y-2.5 text-xs">
                        <div class="flex items-center justify-between p-2 bg-slate-50 rounded border border-slate-200/60 font-mono">
                            <span class="text-slate-500 font-sans">JIT Compilation</span>
                            <span class="font-bold text-emerald-600">{{ opcache.jit_enabled ? 'Active (CR_JIT)' : 'Disabled' }}</span>
                        </div>
                        <div class="flex items-center justify-between p-2 bg-slate-50 rounded border border-slate-200/60 font-mono">
                            <span class="text-slate-500 font-sans">Wasted Memory</span>
                            <span class="font-bold text-slate-700">{{ opcache.wasted_memory_mb }} MB</span>
                        </div>
                        <div class="flex items-center justify-between p-2 bg-slate-50 rounded border border-slate-200/60 font-mono">
                            <span class="text-slate-500 font-sans">Byte-code Invalidation</span>
                            <span class="font-bold text-slate-700">Timestamp Based</span>
                        </div>
                    </div>
                </div>

            </div>

            <!-- 7. TAB 3: Integration Snippets -->
            <div v-else-if="activeTab === 'guide'" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                
                <!-- WordPress Configuration -->
                <div class="bg-white border border-slate-200 rounded-lg p-5 shadow-2xs space-y-3.5">
                    <div class="flex items-center justify-between">
                        <h3 class="font-bold text-slate-900 text-sm flex items-center gap-2">
                            <span class="w-6 h-6 rounded bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-xs">W</span>
                            <span>WordPress Redis Cache</span>
                        </h3>
                        <span class="text-[10px] font-bold px-1.5 py-0.5 bg-blue-50 text-blue-700 border border-blue-200 rounded font-mono">
                            wp-config.php
                        </span>
                    </div>

                    <p class="text-xs text-slate-500 leading-relaxed">
                        Add these lines to your <code>wp-config.php</code> file to activate Redis Object Cache for WordPress:
                    </p>

                    <div class="bg-slate-900 text-slate-100 p-3 rounded font-mono text-[11px] leading-relaxed relative">
                        <button 
                            @click="copyToClipboard(`define('WP_REDIS_HOST', '127.0.0.1');\ndefine('WP_REDIS_PORT', 6379);\ndefine('WP_CACHE', true);`, 'wp_snippet')"
                            class="absolute right-2 top-2 text-slate-400 hover:text-white cursor-pointer"
                        >
                            <CheckIcon v-if="copiedField === 'wp_snippet'" class="w-3.5 h-3.5 text-emerald-400" />
                            <ClipboardDocumentIcon v-else class="w-3.5 h-3.5" />
                        </button>
                        <pre>define('WP_REDIS_HOST', '127.0.0.1');
define('WP_REDIS_PORT', 6379);
define('WP_CACHE', true);</pre>
                    </div>
                </div>

                <!-- Laravel Configuration -->
                <div class="bg-white border border-slate-200 rounded-lg p-5 shadow-2xs space-y-3.5">
                    <div class="flex items-center justify-between">
                        <h3 class="font-bold text-slate-900 text-sm flex items-center gap-2">
                            <span class="w-6 h-6 rounded bg-rose-50 text-rose-600 flex items-center justify-center font-bold text-xs">L</span>
                            <span>Laravel Environment Cache</span>
                        </h3>
                        <span class="text-[10px] font-bold px-1.5 py-0.5 bg-rose-50 text-rose-700 border border-rose-200 rounded font-mono">
                            .env
                        </span>
                    </div>

                    <p class="text-xs text-slate-500 leading-relaxed">
                        Set your cache and session drivers in your <code>.env</code> file:
                    </p>

                    <div class="bg-slate-900 text-slate-100 p-3 rounded font-mono text-[11px] leading-relaxed relative">
                        <button 
                            @click="copyToClipboard(`CACHE_STORE=redis\nSESSION_DRIVER=redis\nREDIS_HOST=127.0.0.1\nREDIS_PORT=6379`, 'laravel_snippet')"
                            class="absolute right-2 top-2 text-slate-400 hover:text-white cursor-pointer"
                        >
                            <CheckIcon v-if="copiedField === 'laravel_snippet'" class="w-3.5 h-3.5 text-emerald-400" />
                            <ClipboardDocumentIcon v-else class="w-3.5 h-3.5" />
                        </button>
                        <pre>CACHE_STORE=redis
SESSION_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379</pre>
                    </div>
                </div>

            </div>

        </div>

        <!-- ========================================================= -->
        <!-- MODALS                                                    -->
        <!-- ========================================================= -->
        <Teleport to="body">
            
            <!-- 1. Purge All Caches Confirmation Modal -->
            <div v-if="showPurgeAllModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-2xs flex items-center justify-center p-4 z-50">
                <div class="bg-white rounded-lg max-w-md w-full p-5 shadow-2xl border border-slate-200 space-y-4">
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 rounded-lg bg-rose-50 border border-rose-100 text-rose-600 flex items-center justify-center shrink-0">
                            <TrashIcon class="w-5 h-5 stroke-[1.75]" />
                        </div>
                        <div class="flex-1">
                            <h3 class="text-sm font-black text-slate-900">Purge All Server Caches?</h3>
                            <p class="text-xs text-slate-500 mt-0.5">
                                This will instantly flush <strong>Redis in-memory store</strong>, reset <strong>PHP Zend OPcache</strong> byte-codes, and clear all <strong>compiled view templates</strong>.
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                        <button 
                            @click="showPurgeAllModal = false" 
                            type="button" 
                            class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-[3px] text-xs font-bold transition cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button 
                            @click="executePurgeAll" 
                            type="button"
                            class="px-4 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-[3px] text-xs font-bold shadow-2xs transition cursor-pointer flex items-center gap-1.5"
                        >
                            <TrashIcon class="w-3.5 h-3.5" />
                            <span>Purge Everything</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- 2. Delete Redis Key Modal -->
            <div v-if="showDeleteKeyModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-2xs flex items-center justify-center p-4 z-50">
                <div class="bg-white rounded-lg max-w-md w-full p-5 shadow-2xl border border-slate-200 space-y-4">
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 rounded-lg bg-rose-50 border border-rose-100 text-rose-600 flex items-center justify-center shrink-0">
                            <TrashIcon class="w-5 h-5 stroke-[1.75]" />
                        </div>
                        <div class="flex-1">
                            <h3 class="text-sm font-black text-slate-900">Delete Redis Key?</h3>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Are you sure you want to delete <strong class="text-slate-900 font-mono">{{ deletingKey?.key }}</strong> from Redis cache?
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                        <button 
                            @click="showDeleteKeyModal = false; deletingKey = null" 
                            type="button" 
                            class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-[3px] text-xs font-bold transition cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button 
                            @click="executeDeleteKey" 
                            type="button"
                            class="px-4 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-[3px] text-xs font-bold shadow-2xs transition cursor-pointer flex items-center gap-1.5"
                        >
                            <TrashIcon class="w-3.5 h-3.5" />
                            <span>Delete Key</span>
                        </button>
                    </div>
                </div>
            </div>

        </Teleport>

    </AuthenticatedLayout>
</template>
