<script setup>
import { ref, computed } from 'vue'
import { Head, useForm, router, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import { 
    CpuChipIcon, 
    SparklesIcon, 
    ArrowPathIcon, 
    CheckIcon, 
    ServerIcon, 
    ShieldCheckIcon,
    AdjustmentsHorizontalIcon,
    CommandLineIcon,
    GlobeAltIcon,
    InformationCircleIcon,
    PuzzlePieceIcon,
    ArrowUturnLeftIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    subscriptions: {
        type: Array,
        default: () => []
    },
    activeSubscription: {
        type: Object,
        default: () => ({
            id: 1,
            domain: 'somitysoft.com',
            username: 'somitysoft',
            php_version: '8.2'
        })
    },
    currentVersion: {
        type: String,
        default: '8.2'
    },
    availableVersions: {
        type: Array,
        default: () => []
    },
    iniSettings: {
        type: Object,
        default: () => ({
            upload_max_filesize: '256M',
            post_max_size: '256M',
            memory_limit: '512M',
            max_execution_time: 300,
            max_input_vars: 3000,
            display_errors: false,
            allow_url_fopen: true,
            short_open_tag: true,
            opcache_enable: true,
        })
    },
    extensions: {
        type: Array,
        default: () => []
    }
})

// Active Tab
const activeTab = ref('version') // 'version' | 'ini' | 'extensions'

// Domain Selector
const onDomainChange = (event) => {
    const subId = event.target.value
    router.get(route('advanced.php-config'), { subscription_id: subId }, {
        preserveScroll: true,
        preserveState: true,
    })
}

// 1. Version Form
const versionForm = useForm({
    subscription_id: props.activeSubscription?.id || '',
    php_version: props.currentVersion || '8.2',
})

const selectedVersion = ref(props.currentVersion || '8.2')

const selectVersion = (v) => {
    selectedVersion.value = v
    versionForm.php_version = v
}

const submitVersionChange = () => {
    versionForm.subscription_id = props.activeSubscription?.id
    versionForm.post(route('advanced.php-config.update-version'), {
        preserveScroll: true,
    })
}

// 2. INI Form
const iniForm = useForm({
    subscription_id: props.activeSubscription?.id || '',
    upload_max_filesize: props.iniSettings.upload_max_filesize || '256M',
    post_max_size: props.iniSettings.post_max_size || '256M',
    memory_limit: props.iniSettings.memory_limit || '512M',
    max_execution_time: props.iniSettings.max_execution_time || 300,
    max_input_vars: props.iniSettings.max_input_vars || 3000,
    display_errors: props.iniSettings.display_errors || false,
    allow_url_fopen: props.iniSettings.allow_url_fopen !== false,
    short_open_tag: props.iniSettings.short_open_tag !== false,
})

const applyPreset = (presetName) => {
    if (presetName === 'wordpress') {
        iniForm.upload_max_filesize = '128M'
        iniForm.post_max_size = '128M'
        iniForm.memory_limit = '256M'
        iniForm.max_execution_time = 120
        iniForm.max_input_vars = 3000
        iniForm.display_errors = false
    } else if (presetName === 'woocommerce') {
        iniForm.upload_max_filesize = '512M'
        iniForm.post_max_size = '512M'
        iniForm.memory_limit = '1024M'
        iniForm.max_execution_time = 300
        iniForm.max_input_vars = 5000
        iniForm.display_errors = false
    } else if (presetName === 'laravel') {
        iniForm.upload_max_filesize = '256M'
        iniForm.post_max_size = '256M'
        iniForm.memory_limit = '512M'
        iniForm.max_execution_time = 300
        iniForm.max_input_vars = 5000
        iniForm.display_errors = false
    }
}

const submitIni = () => {
    iniForm.subscription_id = props.activeSubscription?.id
    iniForm.post(route('advanced.php-config.update-ini'), {
        preserveScroll: true,
    })
}

const resetIni = () => {
    if (!confirm(`Reset PHP.INI directives for ${props.activeSubscription?.domain} to server defaults?`)) return
    router.post(route('advanced.php-config.reset-ini'), {
        subscription_id: props.activeSubscription?.id
    }, {
        preserveScroll: true,
    })
}

// Extension search
const extensionSearch = ref('')
const filteredExtensions = computed(() => {
    if (!extensionSearch.value.trim()) return props.extensions
    const q = extensionSearch.value.toLowerCase().trim()
    return props.extensions.filter(ext => 
        ext.name.toLowerCase().includes(q) ||
        ext.category.toLowerCase().includes(q) ||
        ext.description.toLowerCase().includes(q)
    )
})
</script>

<template>
    <Head title="PHP Configuration & INI Tuning - DeepTouch Host" />

    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto space-y-4">
            
            <!-- 1. Page Header with Breadcrumbs & Domain Selector -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Advanced Tools', href: '#' },
                    { label: 'PHP Configuration' }
                ]"
                :show-refresh="true"
                @refreshed="router.reload({ preserveScroll: true })"
            >
                <template #actions>
                    <div v-if="subscriptions.length > 0" class="flex items-center gap-2">
                        <span class="text-xs font-bold text-slate-500">Domain:</span>
                        <select 
                            :value="activeSubscription?.id"
                            @change="onDomainChange"
                            class="bg-white border border-slate-300 rounded-[3px] text-xs font-bold text-slate-800 py-1.5 pl-2.5 pr-8 focus:ring-1 focus:ring-blue-500 cursor-pointer shadow-2xs"
                        >
                            <option v-for="sub in subscriptions" :key="sub.id" :value="sub.id">
                                {{ sub.domain }}
                            </option>
                        </select>
                    </div>

                    <a 
                        :href="route('advanced.phpinfo')" 
                        target="_blank"
                        class="px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 rounded-[3px] text-xs font-bold flex items-center gap-1.5 transition cursor-pointer shadow-2xs"
                    >
                        <InformationCircleIcon class="w-3.5 h-3.5 text-indigo-600" />
                        <span>View phpinfo() ↗</span>
                    </a>
                </template>
            </PageHeader>

            <!-- 2. Top 3 KPI / Engine Profile Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                
                <!-- Card 1: Active PHP Version -->
                <div class="bg-white border border-slate-200 rounded-lg p-4 shadow-2xs space-y-2.5">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                            <CpuChipIcon class="w-4 h-4 text-blue-600" />
                            <span>Active PHP Engine</span>
                        </span>
                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-200 font-mono uppercase">
                            PHP {{ currentVersion }}-FPM
                        </span>
                    </div>

                    <div>
                        <div class="flex items-baseline gap-2">
                            <span class="text-2xl font-black text-slate-900 font-mono">PHP {{ currentVersion }}</span>
                            <span class="text-xs text-slate-400 font-bold">FastCGI Process Manager</span>
                        </div>
                        <p class="text-[11px] text-slate-500 mt-1">
                            Domain Target: <strong class="text-slate-800 font-mono">{{ activeSubscription?.domain }}</strong>
                        </p>
                    </div>

                    <div class="flex items-center justify-between text-[11px] text-slate-500 pt-1 border-t border-slate-100 font-mono">
                        <span>OPcache: <strong class="text-emerald-600">Active</strong></span>
                        <span>JIT Compiler: <strong class="text-blue-600">Enabled</strong></span>
                    </div>
                </div>

                <!-- Card 2: Memory & Execution Limits -->
                <div class="bg-white border border-slate-200 rounded-lg p-4 shadow-2xs space-y-2.5">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                            <AdjustmentsHorizontalIcon class="w-4 h-4 text-emerald-600" />
                            <span>Resource Profile</span>
                        </span>
                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-blue-50 text-blue-700 border border-blue-200 font-mono">
                            {{ iniSettings.memory_limit }} MEMORY
                        </span>
                    </div>

                    <div class="grid grid-cols-2 gap-2 text-xs font-mono">
                        <div class="bg-slate-50 p-2 rounded border border-slate-200/70">
                            <span class="text-[10px] text-slate-400 block font-bold uppercase font-sans">Max Upload</span>
                            <span class="font-bold text-slate-900 text-sm">{{ iniSettings.upload_max_filesize }}</span>
                        </div>
                        <div class="bg-slate-50 p-2 rounded border border-slate-200/70">
                            <span class="text-[10px] text-slate-400 block font-bold uppercase font-sans">Max Timeout</span>
                            <span class="font-bold text-slate-900 text-sm">{{ iniSettings.max_execution_time }}s</span>
                        </div>
                    </div>

                    <div class="flex items-center justify-between text-[11px] text-slate-500 pt-1 border-t border-slate-100 font-mono">
                        <span>Max Input Vars:</span>
                        <strong class="text-slate-700">{{ iniSettings.max_input_vars }}</strong>
                    </div>
                </div>

                <!-- Card 3: Extensions Status Profile -->
                <div class="bg-gradient-to-br from-indigo-50/70 to-blue-50/50 border border-indigo-100 rounded-lg p-4 shadow-2xs flex flex-col justify-between space-y-2.5">
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-indigo-900 flex items-center gap-1.5">
                                <PuzzlePieceIcon class="w-4 h-4 text-indigo-600" />
                                <span>Extensions & Modules</span>
                            </span>
                            <span class="text-[10px] font-bold px-1.5 py-0.5 bg-indigo-100 text-indigo-800 rounded font-mono">
                                14 LOADED
                            </span>
                        </div>
                        <p class="text-[11px] text-slate-600 mt-1 line-clamp-2">
                            Optimized with Redis cache, Imagick, cURL, Intl, BCMath & MySQLi drivers.
                        </p>
                    </div>

                    <div class="pt-2 border-t border-indigo-100 flex items-center justify-between text-[11px] text-indigo-900 font-mono">
                        <span>Handler:</span>
                        <span class="font-bold">unix:/var/run/php-fpm.sock</span>
                    </div>
                </div>

            </div>

            <!-- 3. Navigation Tabs -->
            <div class="border-b border-slate-200 flex items-center gap-4 text-xs font-bold">
                <button 
                    @click="activeTab = 'version'"
                    class="pb-2.5 border-b-2 transition cursor-pointer flex items-center gap-1.5"
                    :class="activeTab === 'version' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-800'"
                >
                    <CpuChipIcon class="w-4 h-4" />
                    <span>PHP Version Selector</span>
                </button>

                <button 
                    @click="activeTab = 'ini'"
                    class="pb-2.5 border-b-2 transition cursor-pointer flex items-center gap-1.5"
                    :class="activeTab === 'ini' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-800'"
                >
                    <AdjustmentsHorizontalIcon class="w-4 h-4" />
                    <span>PHP.INI Directive Tuning</span>
                </button>

                <button 
                    @click="activeTab = 'extensions'"
                    class="pb-2.5 border-b-2 transition cursor-pointer flex items-center gap-1.5"
                    :class="activeTab === 'extensions' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-800'"
                >
                    <PuzzlePieceIcon class="w-4 h-4" />
                    <span>Extensions & Modules ({{ extensions.length }})</span>
                </button>
            </div>

            <!-- 4. TAB 1: PHP Version Selector -->
            <div v-if="activeTab === 'version'" class="bg-white border border-slate-200 rounded-lg p-5 shadow-2xs space-y-5">
                
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div>
                        <h3 class="font-bold text-slate-900 text-sm">Select PHP Version for {{ activeSubscription?.domain }}</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Choose your desired PHP runtime environment with 1-click FastCGI pool switching</p>
                    </div>

                    <span class="text-xs font-mono font-bold px-2.5 py-1 bg-slate-100 rounded text-slate-700 border border-slate-200">
                        Current: PHP {{ currentVersion }}
                    </span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                    <div 
                        v-for="ver in availableVersions" 
                        :key="ver.version"
                        @click="selectVersion(ver.version)"
                        class="p-4 rounded-lg border-2 transition cursor-pointer flex flex-col justify-between space-y-3"
                        :class="selectedVersion === ver.version ? 'border-blue-600 bg-blue-50/40 shadow-xs' : 'border-slate-200 hover:border-slate-300 bg-white'"
                    >
                        <div class="flex items-start justify-between">
                            <div>
                                <div class="flex items-center gap-2">
                                    <h4 class="font-black text-slate-900 text-sm font-mono">{{ ver.label }}</h4>
                                    <span 
                                        v-if="currentVersion === ver.version"
                                        class="text-[10px] font-bold px-1.5 py-0.2 bg-emerald-100 text-emerald-800 rounded font-mono"
                                    >
                                        CURRENT
                                    </span>
                                </div>
                                <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                                    {{ ver.description }}
                                </p>
                            </div>

                            <div 
                                class="w-5 h-5 rounded-full border flex items-center justify-center shrink-0 mt-0.5"
                                :class="selectedVersion === ver.version ? 'border-blue-600 bg-blue-600 text-white' : 'border-slate-300'"
                            >
                                <CheckIcon v-if="selectedVersion === ver.version" class="w-3.5 h-3.5 stroke-[3]" />
                            </div>
                        </div>

                        <div class="flex items-center justify-between text-[11px] pt-2 border-t border-slate-100">
                            <span class="text-slate-400 font-mono">Release Status:</span>
                            <span class="font-bold text-slate-700">{{ ver.status }}</span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-between pt-4 border-t border-slate-100">
                    <span class="text-xs text-slate-500">
                        Switching PHP version takes less than 3 seconds without downtime.
                    </span>

                    <button 
                        @click="submitVersionChange"
                        :disabled="versionForm.processing || selectedVersion === currentVersion"
                        class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold shadow-2xs transition cursor-pointer disabled:opacity-50 flex items-center gap-1.5"
                    >
                        <CheckCircleIcon class="w-4 h-4" />
                        <span>{{ versionForm.processing ? 'Switching...' : `Apply PHP ${selectedVersion}` }}</span>
                    </button>
                </div>

            </div>

            <!-- 5. TAB 2: PHP.INI Directives Tuning -->
            <div v-else-if="activeTab === 'ini'" class="bg-white border border-slate-200 rounded-lg p-5 shadow-2xs space-y-5">
                
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-3">
                    <div>
                        <h3 class="font-bold text-slate-900 text-sm">Tune PHP.INI Directives</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Customize memory limits, max upload size, execution timeout, and error logging</p>
                    </div>

                    <!-- Quick Preset Buttons -->
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <span class="text-[11px] font-bold text-slate-400 mr-1">Presets:</span>
                        <button 
                            type="button" 
                            @click="applyPreset('wordpress')"
                            class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-[3px] text-xs font-bold transition cursor-pointer"
                        >
                            WordPress
                        </button>
                        <button 
                            type="button" 
                            @click="applyPreset('woocommerce')"
                            class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-[3px] text-xs font-bold transition cursor-pointer"
                        >
                            WooCommerce
                        </button>
                        <button 
                            type="button" 
                            @click="applyPreset('laravel')"
                            class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-[3px] text-xs font-bold transition cursor-pointer"
                        >
                            Laravel
                        </button>
                    </div>
                </div>

                <form @submit.prevent="submitIni" class="space-y-4">
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs font-medium">
                        
                        <!-- Upload Max Filesize -->
                        <div class="space-y-1.5 bg-slate-50 p-3 rounded-[3px] border border-slate-200">
                            <label class="block font-bold text-slate-700 font-mono">upload_max_filesize</label>
                            <select 
                                v-model="iniForm.upload_max_filesize"
                                class="w-full bg-white border border-slate-300 rounded-[3px] px-2.5 py-1.5 text-xs font-mono font-bold text-slate-900 focus:ring-1 focus:ring-blue-600 cursor-pointer"
                            >
                                <option value="32M">32M</option>
                                <option value="64M">64M</option>
                                <option value="128M">128M</option>
                                <option value="256M">256M</option>
                                <option value="512M">512M</option>
                                <option value="1024M">1024M (1 GB)</option>
                            </select>
                            <p class="text-[10.5px] text-slate-400">Maximum allowed size for uploaded files.</p>
                        </div>

                        <!-- Post Max Size -->
                        <div class="space-y-1.5 bg-slate-50 p-3 rounded-[3px] border border-slate-200">
                            <label class="block font-bold text-slate-700 font-mono">post_max_size</label>
                            <select 
                                v-model="iniForm.post_max_size"
                                class="w-full bg-white border border-slate-300 rounded-[3px] px-2.5 py-1.5 text-xs font-mono font-bold text-slate-900 focus:ring-1 focus:ring-blue-600 cursor-pointer"
                            >
                                <option value="32M">32M</option>
                                <option value="64M">64M</option>
                                <option value="128M">128M</option>
                                <option value="256M">256M</option>
                                <option value="512M">512M</option>
                                <option value="1024M">1024M (1 GB)</option>
                            </select>
                            <p class="text-[10.5px] text-slate-400">Maximum size of POST data accepted.</p>
                        </div>

                        <!-- Memory Limit -->
                        <div class="space-y-1.5 bg-slate-50 p-3 rounded-[3px] border border-slate-200">
                            <label class="block font-bold text-slate-700 font-mono">memory_limit</label>
                            <select 
                                v-model="iniForm.memory_limit"
                                class="w-full bg-white border border-slate-300 rounded-[3px] px-2.5 py-1.5 text-xs font-mono font-bold text-slate-900 focus:ring-1 focus:ring-blue-600 cursor-pointer"
                            >
                                <option value="128M">128M</option>
                                <option value="256M">256M</option>
                                <option value="512M">512M</option>
                                <option value="1024M">1024M (1 GB)</option>
                                <option value="2048M">2048M (2 GB)</option>
                            </select>
                            <p class="text-[10.5px] text-slate-400">Maximum amount of memory a script may consume.</p>
                        </div>

                        <!-- Max Execution Time -->
                        <div class="space-y-1.5 bg-slate-50 p-3 rounded-[3px] border border-slate-200">
                            <label class="block font-bold text-slate-700 font-mono">max_execution_time (seconds)</label>
                            <input 
                                v-model.number="iniForm.max_execution_time"
                                type="number" 
                                min="30" 
                                max="1200"
                                class="w-full bg-white border border-slate-300 rounded-[3px] px-2.5 py-1.5 text-xs font-mono font-bold text-slate-900 focus:ring-1 focus:ring-blue-600"
                            />
                            <p class="text-[10.5px] text-slate-400">Execution timeout limit before termination.</p>
                        </div>

                        <!-- Max Input Vars -->
                        <div class="space-y-1.5 bg-slate-50 p-3 rounded-[3px] border border-slate-200">
                            <label class="block font-bold text-slate-700 font-mono">max_input_vars</label>
                            <input 
                                v-model.number="iniForm.max_input_vars"
                                type="number" 
                                min="1000" 
                                max="20000"
                                class="w-full bg-white border border-slate-300 rounded-[3px] px-2.5 py-1.5 text-xs font-mono font-bold text-slate-900 focus:ring-1 focus:ring-blue-600"
                            />
                            <p class="text-[10.5px] text-slate-400">Max input variables allowed per form request.</p>
                        </div>

                        <!-- Display Errors Toggle -->
                        <div class="space-y-1.5 bg-slate-50 p-3 rounded-[3px] border border-slate-200 flex flex-col justify-between">
                            <div>
                                <label class="block font-bold text-slate-700 font-mono">display_errors</label>
                                <p class="text-[10.5px] text-slate-400">Show PHP warnings directly in browser output.</p>
                            </div>
                            <label class="flex items-center gap-2 cursor-pointer pt-1">
                                <input 
                                    v-model="iniForm.display_errors" 
                                    type="checkbox" 
                                    class="w-4 h-4 text-blue-600 rounded-[2px] border-slate-300"
                                />
                                <span class="font-bold text-xs" :class="iniForm.display_errors ? 'text-amber-600' : 'text-slate-600'">
                                    {{ iniForm.display_errors ? 'Enabled (Debugging)' : 'Disabled (Production)' }}
                                </span>
                            </label>
                        </div>

                    </div>

                    <!-- Additional Directives Toggles -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs bg-slate-50 p-3 rounded border border-slate-200">
                        <label class="flex items-center gap-2.5 cursor-pointer">
                            <input 
                                v-model="iniForm.allow_url_fopen" 
                                type="checkbox" 
                                class="w-4 h-4 text-blue-600 rounded-[2px] border-slate-300"
                            />
                            <div>
                                <span class="font-bold text-slate-800 font-mono">allow_url_fopen</span>
                                <p class="text-[10.5px] text-slate-400">Allows PHP file functions to retrieve data from remote URLs.</p>
                            </div>
                        </label>

                        <label class="flex items-center gap-2.5 cursor-pointer">
                            <input 
                                v-model="iniForm.short_open_tag" 
                                type="checkbox" 
                                class="w-4 h-4 text-blue-600 rounded-[2px] border-slate-300"
                            />
                            <div>
                                <span class="font-bold text-slate-800 font-mono">short_open_tag</span>
                                <p class="text-[10.5px] text-slate-400">Allows the use of &lt;? ?&gt; shorthand PHP opening tags.</p>
                            </div>
                        </label>
                    </div>

                    <div class="flex items-center justify-between pt-3 border-t border-slate-100">
                        <button 
                            type="button" 
                            @click="resetIni"
                            class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-[3px] text-xs font-bold transition cursor-pointer flex items-center gap-1.5"
                        >
                            <ArrowUturnLeftIcon class="w-3.5 h-3.5" />
                            <span>Reset Defaults</span>
                        </button>

                        <button 
                            type="submit" 
                            :disabled="iniForm.processing"
                            class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold shadow-2xs transition cursor-pointer disabled:opacity-50 flex items-center gap-1.5"
                        >
                            <CheckIcon class="w-4 h-4 stroke-[2.5]" />
                            <span>{{ iniForm.processing ? 'Saving...' : 'Save PHP.INI Configuration' }}</span>
                        </button>
                    </div>

                </form>

            </div>

            <!-- 6. TAB 3: PHP Extensions & Modules -->
            <div v-else-if="activeTab === 'extensions'" class="bg-white border border-slate-200 rounded-lg p-5 shadow-2xs space-y-4">
                
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-3">
                    <div>
                        <h3 class="font-bold text-slate-900 text-sm">Installed PHP Extensions (PHP {{ currentVersion }})</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Core, media, database, and caching extensions active on this server</p>
                    </div>

                    <div class="relative w-48 sm:w-64">
                        <input 
                            v-model="extensionSearch"
                            type="text" 
                            placeholder="Filter extensions..." 
                            class="w-full bg-slate-50 border border-slate-300 rounded-[3px] px-2.5 py-1.5 text-xs text-slate-900 focus:ring-1 focus:ring-blue-600 focus:border-blue-600 font-medium"
                        />
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                    <div 
                        v-for="ext in filteredExtensions" 
                        :key="ext.name"
                        class="p-3 bg-slate-50/70 rounded border border-slate-200/80 flex flex-col justify-between space-y-2 hover:bg-slate-50 transition"
                    >
                        <div class="flex items-start justify-between">
                            <div>
                                <div class="flex items-center gap-1.5">
                                    <span class="font-black text-slate-900 font-mono text-xs">{{ ext.name }}</span>
                                    <span class="text-[9.5px] font-bold px-1.5 py-0.2 bg-slate-200 text-slate-700 rounded font-sans uppercase">
                                        {{ ext.category }}
                                    </span>
                                </div>
                                <p class="text-[11px] text-slate-500 mt-1 leading-relaxed">
                                    {{ ext.description }}
                                </p>
                            </div>

                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 shrink-0 mt-1" title="Extension Active"></span>
                        </div>

                        <div class="flex items-center justify-between text-[10.5px] pt-1.5 border-t border-slate-200/60 font-mono text-emerald-700 font-bold">
                            <span>Status:</span>
                            <span>Loaded & Active</span>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </AuthenticatedLayout>
</template>
