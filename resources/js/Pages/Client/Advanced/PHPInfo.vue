<script setup>
import { ref, computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
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
    MagnifyingGlassIcon,
    ClipboardDocumentIcon,
    ArrowDownTrayIcon,
    DocumentTextIcon,
    CheckCircleIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    sysInfo: {
        type: Object,
        default: () => ({
            php_version: '8.3.0',
            zend_version: '4.3.0',
            sapi_name: 'fpm-fcgi',
            os: 'Linux (x86_64)',
            server_software: 'Nginx / PHP-FPM Engine',
            domain: 'somitysoft.com',
            loaded_ini: '/etc/php/8.3/fpm/php.ini',
            scanned_inis: 45
        })
    },
    opcache: {
        type: Object,
        default: () => ({
            enabled: true,
            memory_used_mb: 45.2,
            memory_total_mb: 128,
            cached_scripts: 1240,
            hit_rate: 98.4,
            jit_enabled: true
        })
    },
    extensions: {
        type: Array,
        default: () => []
    },
    directives: {
        type: Array,
        default: () => []
    }
})

// Active Tab
const activeTab = ref('directives') // 'directives' | 'extensions' | 'system'

// Search & Filter
const directiveSearch = ref('')
const filteredDirectives = computed(() => {
    if (!directiveSearch.value.trim()) return props.directives
    const q = directiveSearch.value.toLowerCase().trim()
    return props.directives.filter(d => 
        d.directive.toLowerCase().includes(q) ||
        String(d.local_value).toLowerCase().includes(q) ||
        String(d.master_value).toLowerCase().includes(q)
    )
})

const extensionSearch = ref('')
const selectedCategory = ref('ALL')

const categories = computed(() => {
    const set = new Set(props.extensions.map(e => e.category))
    return ['ALL', ...Array.from(set)]
})

const filteredExtensions = computed(() => {
    return props.extensions.filter(ext => {
        const matchesCat = selectedCategory.value === 'ALL' || ext.category === selectedCategory.value
        const q = extensionSearch.value.toLowerCase().trim()
        const matchesSearch = !q || ext.name.toLowerCase().includes(q) || ext.category.toLowerCase().includes(q)
        return matchesCat && matchesSearch
    })
})

// Copy helper
const copiedField = ref(null)
const copyToClipboard = (text, fieldName) => {
    navigator.clipboard.writeText(text)
    copiedField.value = fieldName
    setTimeout(() => {
        copiedField.value = null
    }, 2000)
}

// Download JSON Report
const downloadReport = () => {
    const reportData = {
        generated_at: new Date().toISOString(),
        system: props.sysInfo,
        opcache: props.opcache,
        directives: props.directives,
        extensions: props.extensions
    }
    const blob = new Blob([JSON.stringify(reportData, null, 2)], { type: 'application/json' })
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `phpinfo_${props.sysInfo.domain}_php${props.sysInfo.php_version}.json`
    document.body.appendChild(a)
    a.click()
    document.body.removeChild(a)
    URL.revokeObjectURL(url)
}
</script>

<template>
    <Head title="PHP Environment & Runtime Inspector - DeepTouch Host" />

    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto space-y-4">
            
            <!-- 1. Page Header with Breadcrumbs & Actions -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Advanced Tools', href: '#' },
                    { label: 'PHP Runtime Environment' }
                ]"
                :show-refresh="true"
                @refreshed="router.reload({ preserveScroll: true })"
            >
                <template #actions>
                    <button 
                        @click="downloadReport"
                        class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200 rounded-[3px] text-xs font-bold flex items-center gap-1.5 transition cursor-pointer shadow-2xs"
                    >
                        <ArrowDownTrayIcon class="w-3.5 h-3.5" />
                        <span>Export Report (.json)</span>
                    </button>

                    <Link 
                        :href="route('advanced.php-config')" 
                        class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold flex items-center gap-1.5 shadow-2xs transition cursor-pointer"
                    >
                        <AdjustmentsHorizontalIcon class="w-3.5 h-3.5 stroke-[2.5]" />
                        <span>Tune PHP Config</span>
                    </Link>
                </template>
            </PageHeader>

            <!-- 2. Top 3 KPI / Server Runtime Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                
                <!-- Card 1: Active PHP Version & SAPI -->
                <div class="bg-white border border-slate-200 rounded-lg p-4 shadow-2xs space-y-2.5">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                            <CpuChipIcon class="w-4 h-4 text-blue-600" />
                            <span>PHP Runtime Engine</span>
                        </span>
                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-200 font-mono">
                            {{ sysInfo.sapi_name }}
                        </span>
                    </div>

                    <div>
                        <div class="flex items-baseline gap-2">
                            <span class="text-2xl font-black text-slate-900 font-mono">PHP {{ sysInfo.php_version }}</span>
                            <span class="text-xs text-slate-400 font-bold font-mono">Zend v{{ sysInfo.zend_version }}</span>
                        </div>
                        <p class="text-[11px] text-slate-500 mt-1">
                            Server OS: <strong class="text-slate-800 font-mono">{{ sysInfo.os }}</strong>
                        </p>
                    </div>

                    <div class="flex items-center justify-between text-[11px] text-slate-500 pt-1 border-t border-slate-100 font-mono">
                        <span>Architecture:</span>
                        <strong class="text-slate-700">64-bit (x86_64)</strong>
                    </div>
                </div>

                <!-- Card 2: Configuration INI Details -->
                <div class="bg-white border border-slate-200 rounded-lg p-4 shadow-2xs space-y-2.5">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                            <DocumentTextIcon class="w-4 h-4 text-emerald-600" />
                            <span>Loaded Configuration</span>
                        </span>
                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-blue-50 text-blue-700 border border-blue-200 font-mono">
                            {{ sysInfo.scanned_inis }} SCAN INIs
                        </span>
                    </div>

                    <div class="space-y-1 text-xs font-mono">
                        <div class="bg-slate-50 p-1.5 rounded border border-slate-200/70 flex items-center justify-between">
                            <span class="font-bold text-slate-900 truncate" :title="sysInfo.loaded_ini">{{ sysInfo.loaded_ini }}</span>
                            <button 
                                @click="copyToClipboard(sysInfo.loaded_ini, 'ini_path')"
                                class="text-slate-400 hover:text-blue-600 cursor-pointer ml-1 shrink-0"
                                title="Copy INI path"
                            >
                                <CheckIcon v-if="copiedField === 'ini_path'" class="w-3.5 h-3.5 text-emerald-600" />
                                <ClipboardDocumentIcon v-else class="w-3.5 h-3.5" />
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center justify-between text-[11px] text-slate-500 pt-1 border-t border-slate-100 font-mono">
                        <span>Domain Scope:</span>
                        <strong class="text-slate-700">{{ sysInfo.domain }}</strong>
                    </div>
                </div>

                <!-- Card 3: Zend OPcache & JIT Status -->
                <div class="bg-gradient-to-br from-indigo-50/70 to-blue-50/50 border border-indigo-100 rounded-lg p-4 shadow-2xs flex flex-col justify-between space-y-2.5">
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-indigo-900 flex items-center gap-1.5">
                                <SparklesIcon class="w-4 h-4 text-indigo-600" />
                                <span>Zend OPcache & JIT</span>
                            </span>
                            <span 
                                class="text-[10px] font-bold px-1.5 py-0.5 rounded font-mono uppercase"
                                :class="opcache.enabled ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-700'"
                            >
                                {{ opcache.enabled ? 'OPCACHE ACTIVE' : 'DISABLED' }}
                            </span>
                        </div>
                        <p class="text-[11px] text-slate-600 mt-1 line-clamp-2">
                            Byte-code caching engine with <strong>{{ opcache.hit_rate }}% cache hit rate</strong> and <strong>{{ opcache.cached_scripts }} precompiled scripts</strong>.
                        </p>
                    </div>

                    <div class="pt-2 border-t border-indigo-100 flex items-center justify-between text-[11px] text-indigo-900 font-mono">
                        <span>JIT Compiler:</span>
                        <span class="font-bold text-emerald-700">{{ opcache.jit_enabled ? 'Active (CR_JIT)' : 'Standard' }}</span>
                    </div>
                </div>

            </div>

            <!-- 3. Navigation Tabs -->
            <div class="border-b border-slate-200 flex items-center gap-4 text-xs font-bold">
                <button 
                    @click="activeTab = 'directives'"
                    class="pb-2.5 border-b-2 transition cursor-pointer flex items-center gap-1.5"
                    :class="activeTab === 'directives' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-800'"
                >
                    <AdjustmentsHorizontalIcon class="w-4 h-4" />
                    <span>Core Directives & Settings ({{ directives.length }})</span>
                </button>

                <button 
                    @click="activeTab = 'extensions'"
                    class="pb-2.5 border-b-2 transition cursor-pointer flex items-center gap-1.5"
                    :class="activeTab === 'extensions' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-800'"
                >
                    <PuzzlePieceIcon class="w-4 h-4" />
                    <span>Loaded Extensions ({{ extensions.length }})</span>
                </button>

                <button 
                    @click="activeTab = 'system'"
                    class="pb-2.5 border-b-2 transition cursor-pointer flex items-center gap-1.5"
                    :class="activeTab === 'system' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-800'"
                >
                    <ServerIcon class="w-4 h-4" />
                    <span>Environment & SAPI Architecture</span>
                </button>
            </div>

            <!-- 4. TAB 1: Core Directives Table -->
            <div v-if="activeTab === 'directives'" class="bg-white border border-slate-200 rounded-lg shadow-2xs overflow-hidden">
                
                <div class="px-4 py-3 bg-slate-50/80 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <AdjustmentsHorizontalIcon class="w-4 h-4 text-indigo-600" />
                        <span class="text-xs font-bold text-slate-900">Runtime PHP.INI Directives</span>
                        <span class="text-[11px] text-slate-400 font-mono">({{ filteredDirectives.length }})</span>
                    </div>

                    <div class="relative w-full sm:w-64">
                        <MagnifyingGlassIcon class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-1/2 -translate-y-1/2" />
                        <input 
                            v-model="directiveSearch"
                            type="text" 
                            placeholder="Search directive name or value..." 
                            class="w-full pl-8 pr-2.5 py-1.5 bg-white border border-slate-300 rounded-[3px] text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-blue-500 font-medium shadow-2xs"
                        />
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="filteredDirectives.length === 0" class="p-12 text-center text-slate-500 text-xs">
                    <InformationCircleIcon class="w-12 h-12 text-slate-300 mx-auto mb-2" />
                    <h4 class="text-sm font-bold text-slate-900 mb-1">No Directives Match Search</h4>
                    <p class="text-[11px] text-slate-400 max-w-sm mx-auto mb-4">
                        Try clearing your search query to view all core PHP settings.
                    </p>
                    <button 
                        @click="directiveSearch = ''"
                        class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-[3px] text-xs font-bold transition cursor-pointer"
                    >
                        Clear Search
                    </button>
                </div>

                <!-- Table Content -->
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-700">
                        <thead class="bg-slate-50/90 border-b border-slate-200 text-slate-500 uppercase font-bold tracking-wider text-[10.5px] select-none">
                            <tr>
                                <th class="py-2.5 px-4 w-72">Directive Name</th>
                                <th class="py-2.5 px-4">Local Value (Per-Directory / Runtime)</th>
                                <th class="py-2.5 px-4">Master Value (Global php.ini)</th>
                                <th class="py-2.5 px-4 w-24 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-medium">
                            <tr v-for="d in filteredDirectives" :key="d.directive" class="hover:bg-slate-50/80 transition-colors">
                                
                                <!-- Directive Name -->
                                <td class="py-3 px-4 font-mono font-bold text-slate-900 text-[11.5px]">
                                    <div class="flex items-center gap-1.5">
                                        <span>{{ d.directive }}</span>
                                        <button 
                                            @click="copyToClipboard(d.directive, 'dir_' + d.directive)"
                                            class="text-slate-400 hover:text-blue-600 cursor-pointer"
                                            title="Copy directive name"
                                        >
                                            <CheckIcon v-if="copiedField === 'dir_' + d.directive" class="w-3.5 h-3.5 text-emerald-600" />
                                            <ClipboardDocumentIcon v-else class="w-3.5 h-3.5" />
                                        </button>
                                    </div>
                                </td>

                                <!-- Local Value -->
                                <td class="py-3 px-4 font-mono text-[11px]">
                                    <span 
                                        class="px-2 py-0.5 rounded-[3px] font-bold border"
                                        :class="d.local_value === 'On' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : (d.local_value === 'Off' ? 'bg-slate-100 text-slate-600 border-slate-200' : 'bg-blue-50 text-blue-700 border-blue-200')"
                                    >
                                        {{ d.local_value }}
                                    </span>
                                </td>

                                <!-- Master Value -->
                                <td class="py-3 px-4 font-mono text-[11px] text-slate-500">
                                    {{ d.master_value }}
                                </td>

                                <!-- Actions -->
                                <td class="py-3 px-4 text-right">
                                    <Link 
                                        :href="route('advanced.php-config')" 
                                        class="text-blue-600 hover:underline text-[11px] font-bold"
                                        title="Tune in PHP Config"
                                    >
                                        Tune ↗
                                    </Link>
                                </td>

                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="px-4 py-2.5 bg-slate-50 border-t border-slate-200 text-[11px] text-slate-500 flex items-center justify-between">
                    <span>Directives loaded dynamically from Zend Engine runtime.</span>
                    <span class="font-mono">Local values override master configuration</span>
                </div>

            </div>

            <!-- 5. TAB 2: Loaded Extensions -->
            <div v-else-if="activeTab === 'extensions'" class="bg-white border border-slate-200 rounded-lg p-5 shadow-2xs space-y-4">
                
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-3">
                    <!-- Category Chips -->
                    <div class="flex items-center gap-1 overflow-x-auto pb-1 sm:pb-0">
                        <button 
                            v-for="cat in categories" 
                            :key="cat"
                            @click="selectedCategory = cat"
                            class="px-2.5 py-1 rounded-[3px] text-xs font-bold transition cursor-pointer"
                            :class="selectedCategory === cat ? 'bg-blue-600 text-white shadow-2xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
                        >
                            {{ cat }}
                        </button>
                    </div>

                    <div class="relative w-full sm:w-64">
                        <MagnifyingGlassIcon class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-1/2 -translate-y-1/2" />
                        <input 
                            v-model="extensionSearch"
                            type="text" 
                            placeholder="Search extensions..." 
                            class="w-full pl-8 pr-2.5 py-1.5 bg-slate-50 border border-slate-300 rounded-[3px] text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-blue-500 font-medium"
                        />
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                    <div 
                        v-for="ext in filteredExtensions" 
                        :key="ext.name"
                        class="p-3 bg-slate-50/70 rounded border border-slate-200/80 flex items-center justify-between hover:bg-slate-50 transition"
                    >
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded bg-white border border-slate-200 text-blue-600 flex items-center justify-center font-black text-xs font-mono shadow-2xs">
                                {{ ext.name.slice(0, 2).toUpperCase() }}
                            </div>
                            <div>
                                <span class="font-bold text-slate-900 font-mono text-xs block">{{ ext.name }}</span>
                                <span class="text-[10px] text-slate-500">{{ ext.category }}</span>
                            </div>
                        </div>

                        <div class="text-right">
                            <span class="text-[10px] font-mono font-bold px-1.5 py-0.2 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded">
                                {{ ext.version }}
                            </span>
                        </div>
                    </div>
                </div>

            </div>

            <!-- 6. TAB 3: Environment & SAPI Architecture -->
            <div v-else-if="activeTab === 'system'" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                
                <div class="bg-white border border-slate-200 rounded-lg p-5 shadow-2xs space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <div class="flex items-center gap-2">
                            <ServerIcon class="w-5 h-5 text-indigo-600" />
                            <h3 class="font-bold text-slate-900 text-sm">Server & OS Coordinates</h3>
                        </div>
                    </div>

                    <div class="space-y-2.5 text-xs">
                        <div class="flex items-center justify-between p-2 bg-slate-50 rounded border border-slate-200/60 font-mono">
                            <span class="text-slate-500 font-sans">PHP Version</span>
                            <span class="font-bold text-slate-900">{{ sysInfo.php_version }}</span>
                        </div>
                        <div class="flex items-center justify-between p-2 bg-slate-50 rounded border border-slate-200/60 font-mono">
                            <span class="text-slate-500 font-sans">Zend Engine Version</span>
                            <span class="font-bold text-slate-900">v{{ sysInfo.zend_version }}</span>
                        </div>
                        <div class="flex items-center justify-between p-2 bg-slate-50 rounded border border-slate-200/60 font-mono">
                            <span class="text-slate-500 font-sans">Server API (SAPI)</span>
                            <span class="font-bold text-slate-900">{{ sysInfo.sapi_name }}</span>
                        </div>
                        <div class="flex items-center justify-between p-2 bg-slate-50 rounded border border-slate-200/60 font-mono">
                            <span class="text-slate-500 font-sans">Host Operating System</span>
                            <span class="font-bold text-slate-900">{{ sysInfo.os }}</span>
                        </div>
                    </div>
                </div>

                <div class="bg-white border border-slate-200 rounded-lg p-5 shadow-2xs space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <div class="flex items-center gap-2">
                            <DocumentTextIcon class="w-5 h-5 text-blue-600" />
                            <h3 class="font-bold text-slate-900 text-sm">Configuration File System</h3>
                        </div>
                    </div>

                    <div class="space-y-2.5 text-xs">
                        <div class="p-2.5 bg-slate-50 rounded border border-slate-200/60 font-mono space-y-1">
                            <span class="text-[10.5px] text-slate-400 uppercase font-bold font-sans">Loaded php.ini</span>
                            <span class="font-bold text-slate-900 break-all block">{{ sysInfo.loaded_ini }}</span>
                        </div>
                        <div class="p-2.5 bg-slate-50 rounded border border-slate-200/60 font-mono space-y-1">
                            <span class="text-[10.5px] text-slate-400 uppercase font-bold font-sans">Scanned Additional INI Files</span>
                            <span class="font-bold text-slate-900">{{ sysInfo.scanned_inis }} configuration files loaded</span>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </AuthenticatedLayout>
</template>
