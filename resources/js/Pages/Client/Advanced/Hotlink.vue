<script setup>
import { ref, computed, watch } from 'vue'
import { Head, useForm, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import { 
    ShieldCheckIcon, 
    ShieldExclamationIcon,
    PhotoIcon,
    GlobeAltIcon,
    ClipboardDocumentIcon,
    CheckIcon,
    SparklesIcon,
    ArrowPathIcon,
    CheckCircleIcon,
    CommandLineIcon,
    CodeBracketIcon,
    InformationCircleIcon,
    ArrowTopRightOnSquareIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    hotlink: {
        type: Object,
        default: () => ({
            domain: 'somitysoft.com',
            is_enabled: true,
            allowed_extensions: 'jpg, jpeg, png, gif, webp, svg, mp4, mp3, pdf, zip, avif, webm',
            allowed_referrers: "somitysoft.com\n*.somitysoft.com\ngoogle.com\n*.google.com\nbing.com\nyahoo.com\nfacebook.com\npinterest.com",
            allow_direct_requests: true,
            redirect_url: null
        })
    },
    domains: {
        type: Array,
        default: () => ['somitysoft.com']
    },
    stats: {
        type: Object,
        default: () => ({
            is_enabled: true,
            extensions_count: 12,
            referrers_count: 8,
            allow_direct: true,
            primary_domain: 'somitysoft.com',
            server_engine: 'Nginx valid_referers & Apache mod_rewrite'
        })
    },
    snippets: {
        type: Object,
        default: () => ({
            nginx: '',
            apache: ''
        })
    }
})

// Active Tab
const activeTab = ref('config') // 'config' | 'inspector' | 'guide'

// Checkbox Grid Matrix Definition (5 Columns as in screenshot)
const extensionColumns = [
    [
        { key: '.*', label: '.*' },
        { key: 'bmp', label: 'bmp' },
        { key: 'wma', label: 'wma' },
        { key: 'mp3', label: 'mp3' },
        { key: 'html', label: 'html' },
        { key: 'webp', label: 'webp' },
    ],
    [
        { key: 'jpg', label: 'jpg' },
        { key: 'tiff', label: 'tiff' },
        { key: 'mov', label: 'mov' },
        { key: 'pdf', label: 'pdf' },
        { key: 'htm', label: 'htm' },
        { key: 'svg', label: 'svg' },
    ],
    [
        { key: 'jpeg', label: 'jpeg' },
        { key: 'avi', label: 'avi' },
        { key: 'zip', label: 'zip' },
        { key: 'swf', label: 'swf' },
        { key: 'php', label: 'php' },
        { key: 'avif', label: 'avif' },
    ],
    [
        { key: 'gif', label: 'gif' },
        { key: 'mpeg', label: 'mpeg' },
        { key: 'rar', label: 'rar' },
        { key: 'psd', label: 'psd' },
        { key: 'mp4', label: 'mp4' },
        { key: 'webm', label: 'webm' },
    ],
    [
        { key: 'png', label: 'png' },
        { key: 'mpg', label: 'mpg' },
        { key: 'exe', label: 'exe' },
        { key: 'txt', label: 'txt' },
        { key: 'wav', label: 'wav' },
        { key: 'ogg', label: 'ogg' },
    ]
]

const allAvailableExtensions = extensionColumns.flatMap(col => col.map(item => item.key)).filter(k => k !== '.*')

// Parse initial selected extensions
const initialExts = props.hotlink.allowed_extensions
    ? props.hotlink.allowed_extensions.split(',').map(s => s.trim().toLowerCase()).filter(Boolean)
    : ['jpg', 'jpeg', 'gif', 'png', 'bmp', 'webp', 'svg', 'mp4', 'mp3', 'pdf', 'zip']

const selectedExtensions = ref([...initialExts])
const isAllSelected = computed(() => {
    return allAvailableExtensions.every(ext => selectedExtensions.value.includes(ext))
})

// Toggle extension
const toggleExtension = (extKey) => {
    if (extKey === '.*') {
        if (isAllSelected.value) {
            selectedExtensions.value = []
        } else {
            selectedExtensions.value = [...allAvailableExtensions]
        }
        return
    }

    const idx = selectedExtensions.value.indexOf(extKey)
    if (idx > -1) {
        selectedExtensions.value.splice(idx, 1)
    } else {
        selectedExtensions.value.push(extKey)
    }
}

// Redirect URL decomposition (protocol + host/path)
let initialProto = 'http://'
let initialHost = ''
if (props.hotlink.redirect_url) {
    if (props.hotlink.redirect_url.startsWith('https://')) {
        initialProto = 'https://'
        initialHost = props.hotlink.redirect_url.replace('https://', '')
    } else if (props.hotlink.redirect_url.startsWith('http://')) {
        initialProto = 'http://'
        initialHost = props.hotlink.redirect_url.replace('http://', '')
    } else {
        initialHost = props.hotlink.redirect_url
    }
}

const redirectProtocol = ref(initialProto)
const redirectHost = ref(initialHost)

// Form
const form = useForm({
    domain: props.hotlink.domain,
    is_enabled: props.hotlink.is_enabled,
    allowed_extensions: props.hotlink.allowed_extensions,
    allowed_referrers: props.hotlink.allowed_referrers,
    allow_direct_requests: props.hotlink.allow_direct_requests,
    redirect_url: props.hotlink.redirect_url || '',
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

// Preset Handlers
const selectImagesOnly = () => {
    selectedExtensions.value = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg', 'avif', 'tiff', 'psd']
}

const selectMediaAll = () => {
    selectedExtensions.value = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg', 'mp4', 'avi', 'mov', 'mpeg', 'mpg', 'webm', 'mp3', 'wma', 'wav', 'ogg']
}

const addReferrerPreset = (domainStr) => {
    const current = form.allowed_referrers ? form.allowed_referrers.split('\n').map(s => s.trim()) : []
    if (!current.includes(domainStr)) {
        current.push(domainStr)
        form.allowed_referrers = current.join('\n')
    }
}

const submitForm = () => {
    form.allowed_extensions = selectedExtensions.value.join(', ')
    form.redirect_url = redirectHost.value.trim() ? `${redirectProtocol.value}${redirectHost.value.trim()}` : null

    form.post(route('advanced.hotlink.update'), {
        preserveScroll: true,
    })
}

const toggleHotlinkProtection = () => {
    router.post(route('advanced.hotlink.toggle'), {}, {
        preserveScroll: true,
        onSuccess: () => {
            form.is_enabled = !form.is_enabled
        }
    })
}
</script>

<template>
    <Head title="Hotlink & Bandwidth Theft Protection - DeepTouch Host" />

    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto space-y-4">
            
            <!-- 1. Page Header with Breadcrumbs & Actions -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Advanced Tools', href: '#' },
                    { label: 'Hotlink & Bandwidth Theft Protection' }
                ]"
                :show-refresh="true"
                @refreshed="router.reload({ preserveScroll: true })"
            >
                <template #actions>
                    <button 
                        @click="toggleHotlinkProtection"
                        class="px-3 py-1.5 rounded-[3px] text-xs font-bold flex items-center gap-1.5 transition cursor-pointer shadow-2xs border"
                        :class="form.is_enabled ? 'bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border-emerald-300' : 'bg-rose-50 hover:bg-rose-100 text-rose-800 border-rose-300'"
                    >
                        <ShieldCheckIcon v-if="form.is_enabled" class="w-3.5 h-3.5 text-emerald-600" />
                        <ShieldExclamationIcon v-else class="w-3.5 h-3.5 text-rose-600" />
                        <span>{{ form.is_enabled ? 'Protection Active' : 'Protection Disabled' }}</span>
                    </button>

                    <button 
                        @click="submitForm"
                        :disabled="form.processing"
                        class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold flex items-center gap-1.5 shadow-2xs transition cursor-pointer disabled:opacity-50"
                    >
                        <CheckIcon class="w-3.5 h-3.5 stroke-[2.5]" />
                        <span>{{ form.processing ? 'Saving...' : 'Save Configuration' }}</span>
                    </button>
                </template>
            </PageHeader>

            <!-- 2. Top 3 KPI / Bandwidth Defense Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                
                <!-- Card 1: Shield Status -->
                <div class="bg-white border border-slate-200 rounded-lg p-4 shadow-2xs space-y-2.5">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                            <ShieldCheckIcon class="w-4 h-4 text-rose-600" />
                            <span>Hotlink Defense Shield</span>
                        </span>
                        <span 
                            class="text-[10px] font-bold px-1.5 py-0.5 rounded font-mono uppercase"
                            :class="stats.is_enabled ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200'"
                        >
                            {{ stats.is_enabled ? 'ACTIVE' : 'DISABLED' }}
                        </span>
                    </div>

                    <div>
                        <div class="flex items-baseline gap-2">
                            <span class="text-2xl font-black text-slate-900 font-mono">{{ stats.is_enabled ? 'ENABLED' : 'MUTED' }}</span>
                        </div>
                        <p class="text-[11px] text-slate-500 mt-1">
                            Third-party sites embedding your media are blocked with <strong>403 Forbidden</strong> or custom redirect.
                        </p>
                    </div>

                    <div class="flex items-center justify-between text-[11px] text-slate-500 pt-1 border-t border-slate-100 font-mono">
                        <span>Scope Domain:</span>
                        <strong class="text-slate-800">{{ stats.primary_domain }}</strong>
                    </div>
                </div>

                <!-- Card 2: Guarded File Formats -->
                <div class="bg-white border border-slate-200 rounded-lg p-4 shadow-2xs space-y-2.5">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                            <PhotoIcon class="w-4 h-4 text-indigo-600" />
                            <span>Guarded Asset Types</span>
                        </span>
                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-indigo-50 text-indigo-700 border border-indigo-200 font-mono">
                            {{ selectedExtensions.length }} EXTENSIONS
                        </span>
                    </div>

                    <div>
                        <div class="flex items-baseline gap-2">
                            <span class="text-2xl font-black text-slate-900 font-mono">{{ selectedExtensions.length }}</span>
                            <span class="text-xs text-slate-400 font-bold font-mono">Protected Formats Selected</span>
                        </div>
                        <p class="text-[11px] text-slate-500 mt-1 font-mono truncate" :title="selectedExtensions.join(', ')">
                            {{ selectedExtensions.join(', ') || 'None selected' }}
                        </p>
                    </div>

                    <div class="flex items-center justify-between text-[11px] text-slate-500 pt-1 border-t border-slate-100 font-mono">
                        <span>Direct URL Access:</span>
                        <strong :class="form.allow_direct_requests ? 'text-emerald-700' : 'text-rose-700'">
                            {{ form.allow_direct_requests ? 'ALLOWED' : 'BLOCKED' }}
                        </strong>
                    </div>
                </div>

                <!-- Card 3: Whitelisted Referer Networks -->
                <div class="bg-gradient-to-br from-indigo-50/70 to-blue-50/50 border border-indigo-100 rounded-lg p-4 shadow-2xs flex flex-col justify-between space-y-2.5">
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-indigo-900 flex items-center gap-1.5">
                                <GlobeAltIcon class="w-4 h-4 text-indigo-600" />
                                <span>Allowed Referer Domains</span>
                            </span>
                            <span class="text-[10px] font-bold px-1.5 py-0.5 bg-indigo-100 text-indigo-800 rounded font-mono">
                                WHITELIST
                            </span>
                        </div>

                        <div class="flex items-baseline gap-2 mt-1">
                            <span class="text-2xl font-black text-slate-900 font-mono">{{ stats.referrers_count }}</span>
                            <span class="text-xs text-slate-600 font-bold">Trusted Search & Social Networks</span>
                        </div>
                    </div>

                    <div class="pt-2 border-t border-indigo-100 flex items-center justify-between text-[11px] text-indigo-900 font-mono">
                        <span>Engine:</span>
                        <span class="font-bold text-slate-800">Nginx valid_referers</span>
                    </div>
                </div>

            </div>

            <!-- 3. Navigation Tabs -->
            <div class="border-b border-slate-200 flex items-center gap-4 text-xs font-bold">
                <button 
                    @click="activeTab = 'config'"
                    class="pb-2.5 border-b-2 transition cursor-pointer flex items-center gap-1.5"
                    :class="activeTab === 'config' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-800'"
                >
                    <ShieldCheckIcon class="w-4 h-4" />
                    <span>Protection Configuration</span>
                </button>

                <button 
                    @click="activeTab = 'inspector'"
                    class="pb-2.5 border-b-2 transition cursor-pointer flex items-center gap-1.5"
                    :class="activeTab === 'inspector' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-800'"
                >
                    <CodeBracketIcon class="w-4 h-4" />
                    <span>Nginx & Apache Rule Inspector</span>
                </button>

                <button 
                    @click="activeTab = 'guide'"
                    class="pb-2.5 border-b-2 transition cursor-pointer flex items-center gap-1.5"
                    :class="activeTab === 'guide' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-800'"
                >
                    <InformationCircleIcon class="w-4 h-4" />
                    <span>Testing & Bandwidth Guide</span>
                </button>
            </div>

            <!-- 4. TAB 1: Main Configuration Form (Matches User Design Exactly) -->
            <form v-if="activeTab === 'config'" @submit.prevent="submitForm" class="bg-white border border-slate-200 rounded-lg p-6 shadow-2xs space-y-6">
                
                <!-- SECTION 1: Block direct access to these extensions (Checkbox Grid) -->
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <h4 class="text-sm font-bold text-slate-900">
                            Block direct access to these extensions
                        </h4>
                        <div class="flex items-center gap-2">
                            <button 
                                type="button" 
                                @click="selectImagesOnly"
                                class="px-2 py-0.5 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-[11px] cursor-pointer"
                            >
                                Images Only
                            </button>
                            <button 
                                type="button" 
                                @click="selectMediaAll"
                                class="px-2 py-0.5 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-[11px] cursor-pointer"
                            >
                                Images & Videos
                            </button>
                        </div>
                    </div>

                    <!-- 5 Column Grid matching user screenshot -->
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-y-3 gap-x-4 pt-1">
                        <div 
                            v-for="(col, colIdx) in extensionColumns" 
                            :key="colIdx" 
                            class="space-y-3"
                        >
                            <label 
                                v-for="item in col" 
                                :key="item.key"
                                class="flex items-center gap-2.5 cursor-pointer select-none group"
                            >
                                <div 
                                    @click.prevent="toggleExtension(item.key)"
                                    class="w-5 h-5 rounded-[4px] border flex items-center justify-center transition-all"
                                    :class="(item.key === '.*' ? isAllSelected : selectedExtensions.includes(item.key)) 
                                        ? 'bg-blue-600 border-blue-600 text-white shadow-2xs' 
                                        : 'border-slate-400 bg-white group-hover:border-blue-500'"
                                >
                                    <CheckIcon 
                                        v-if="item.key === '.*' ? isAllSelected : selectedExtensions.includes(item.key)" 
                                        class="w-3.5 h-3.5 stroke-[3]" 
                                    />
                                </div>
                                <span class="text-xs font-mono font-medium text-slate-800">{{ item.label }}</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- SECTION 2: Direct requests -->
                <div class="space-y-2 pt-4 border-t border-slate-100">
                    <h4 class="text-sm font-bold text-slate-900">
                        Direct requests
                    </h4>
                    
                    <label class="flex items-center gap-2.5 cursor-pointer select-none group pt-1">
                        <div 
                            @click.prevent="form.allow_direct_requests = !form.allow_direct_requests"
                            class="w-5 h-5 rounded-[4px] border flex items-center justify-center transition-all"
                            :class="form.allow_direct_requests 
                                ? 'bg-blue-600 border-blue-600 text-white shadow-2xs' 
                                : 'border-slate-400 bg-white group-hover:border-blue-500'"
                        >
                            <CheckIcon v-if="form.allow_direct_requests" class="w-3.5 h-3.5 stroke-[3]" />
                        </div>
                        <span class="text-xs text-slate-700">
                            Allow direct requests (for example entering the URL to an image in your browser)
                        </span>
                    </label>
                </div>

                <!-- SECTION 3: Redirect blocked requests to this URL -->
                <div class="space-y-2 pt-4 border-t border-slate-100">
                    <h4 class="text-sm font-bold text-slate-900">
                        Redirect blocked requests to this URL
                    </h4>
                    
                    <div class="flex items-center gap-2 max-w-2xl pt-1">
                        <!-- Protocol Select -->
                        <div class="relative w-28 shrink-0">
                            <select 
                                v-model="redirectProtocol"
                                class="w-full appearance-none bg-white border border-slate-300 rounded-lg px-3 py-2 text-xs font-mono font-bold text-slate-800 focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer shadow-2xs"
                            >
                                <option value="http://">http://</option>
                                <option value="https://">https://</option>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-slate-500">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </div>

                        <!-- Host Input -->
                        <input 
                            v-model="redirectHost"
                            type="text" 
                            placeholder="domain.com/no-hotlink.png (or leave blank for 403 Forbidden)"
                            class="flex-1 bg-white border border-slate-300 rounded-lg px-3.5 py-2 text-xs font-mono text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-blue-500 shadow-2xs"
                        />
                    </div>
                </div>

                <!-- SECTION 4: Allowed Referrer URLs -->
                <div class="space-y-2 pt-4 border-t border-slate-100">
                    <div class="flex items-center justify-between">
                        <h4 class="text-sm font-bold text-slate-900">
                            Permitted Referer Domains (URLs allowed to embed your assets)
                        </h4>
                        <div class="flex items-center gap-1.5">
                            <span class="text-[10.5px] text-slate-400 font-bold">Add:</span>
                            <button 
                                type="button" 
                                @click="addReferrerPreset('pinterest.com\n*.pinterest.com')"
                                class="px-1.5 py-0.5 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-[10.5px] cursor-pointer"
                            >
                                + Pinterest
                            </button>
                            <button 
                                type="button" 
                                @click="addReferrerPreset('twitter.com\nx.com\nt.co')"
                                class="px-1.5 py-0.5 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-[10.5px] cursor-pointer"
                            >
                                + Twitter / X
                            </button>
                            <button 
                                type="button" 
                                @click="addReferrerPreset('facebook.com\n*.facebook.com')"
                                class="px-1.5 py-0.5 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-[10.5px] cursor-pointer"
                            >
                                + Facebook
                            </button>
                        </div>
                    </div>

                    <textarea 
                        v-model="form.allowed_referrers"
                        rows="4"
                        placeholder="somitysoft.com&#10;*.somitysoft.com&#10;google.com&#10;*.google.com&#10;bing.com&#10;yahoo.com"
                        class="w-full bg-slate-50 border border-slate-300 rounded-lg p-3 text-xs font-mono font-medium text-slate-900 focus:ring-1 focus:ring-blue-600 outline-none leading-relaxed"
                    ></textarea>
                </div>

                <!-- Footer Save -->
                <div class="flex items-center justify-between pt-4 border-t border-slate-100">
                    <span class="text-xs text-slate-400">
                        {{ selectedExtensions.length }} extensions guarded • Applied instantly to Nginx / Apache
                    </span>

                    <button 
                        type="submit" 
                        :disabled="form.processing"
                        class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold shadow-2xs transition cursor-pointer disabled:opacity-50 flex items-center gap-1.5"
                    >
                        <CheckIcon class="w-3.5 h-3.5 stroke-[2.5]" />
                        <span>{{ form.processing ? 'Saving...' : 'Apply Hotlink Protection' }}</span>
                    </button>
                </div>

            </form>

            <!-- 5. TAB 2: Nginx & Apache Rule Inspector -->
            <div v-else-if="activeTab === 'inspector'" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                
                <!-- Nginx Snippet -->
                <div class="bg-slate-900 rounded-lg p-4 text-slate-100 space-y-2.5 shadow-xl border border-slate-800">
                    <div class="flex items-center justify-between border-b border-slate-800 pb-2">
                        <span class="text-xs font-mono font-bold text-emerald-400 flex items-center gap-1.5">
                            <CommandLineIcon class="w-4 h-4" />
                            <span>Generated Nginx Block</span>
                        </span>
                        <button 
                            @click="copyToClipboard(snippets.nginx, 'nginx_code')"
                            class="text-slate-400 hover:text-white text-xs flex items-center gap-1 cursor-pointer"
                        >
                            <CheckIcon v-if="copiedField === 'nginx_code'" class="w-3.5 h-3.5 text-emerald-400" />
                            <ClipboardDocumentIcon v-else class="w-3.5 h-3.5" />
                            <span>{{ copiedField === 'nginx_code' ? 'Copied' : 'Copy' }}</span>
                        </button>
                    </div>

                    <pre class="font-mono text-xs text-slate-300 bg-slate-950 p-3 rounded overflow-x-auto leading-relaxed whitespace-pre-wrap">{{ snippets.nginx }}</pre>
                </div>

                <!-- Apache Snippet -->
                <div class="bg-slate-900 rounded-lg p-4 text-slate-100 space-y-2.5 shadow-xl border border-slate-800">
                    <div class="flex items-center justify-between border-b border-slate-800 pb-2">
                        <span class="text-xs font-mono font-bold text-indigo-400 flex items-center gap-1.5">
                            <CodeBracketIcon class="w-4 h-4" />
                            <span>Generated Apache .htaccess Block</span>
                        </span>
                        <button 
                            @click="copyToClipboard(snippets.apache, 'apache_code')"
                            class="text-slate-400 hover:text-white text-xs flex items-center gap-1 cursor-pointer"
                        >
                            <CheckIcon v-if="copiedField === 'apache_code'" class="w-3.5 h-3.5 text-emerald-400" />
                            <ClipboardDocumentIcon v-else class="w-3.5 h-3.5" />
                            <span>{{ copiedField === 'apache_code' ? 'Copied' : 'Copy' }}</span>
                        </button>
                    </div>

                    <pre class="font-mono text-xs text-slate-300 bg-slate-950 p-3 rounded overflow-x-auto leading-relaxed whitespace-pre-wrap">{{ snippets.apache }}</pre>
                </div>

            </div>

            <!-- 6. TAB 3: Bandwidth Protection Guide & Testing -->
            <div v-else-if="activeTab === 'guide'" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                
                <!-- How It Works -->
                <div class="bg-white border border-slate-200 rounded-lg p-5 shadow-2xs space-y-3">
                    <h3 class="font-bold text-slate-900 text-sm flex items-center gap-2">
                        <SparklesIcon class="w-4 h-4 text-indigo-600" />
                        <span>How Hotlink Protection Works</span>
                    </h3>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        When another website links directly to your image (`<img src="https://{{ stats.primary_domain }}/logo.png">`), the visitor's browser sends an HTTP <code>Referer</code> header indicating the external domain.
                    </p>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        If the referer is not in your allowed whitelist, the web server immediately drops the request with <strong>HTTP 403 Forbidden</strong> or redirects to your custom warning image.
                    </p>
                </div>

                <!-- Testing with cURL -->
                <div class="bg-white border border-slate-200 rounded-lg p-5 shadow-2xs space-y-3">
                    <h3 class="font-bold text-slate-900 text-sm flex items-center gap-2">
                        <CommandLineIcon class="w-4 h-4 text-emerald-600" />
                        <span>How to Test Hotlink Protection</span>
                    </h3>
                    <div class="space-y-2 text-xs">
                        <p class="text-slate-500">Run this cURL test in your terminal simulating an unauthorized hotlinker:</p>
                        <div class="bg-slate-900 text-slate-100 p-2.5 rounded font-mono text-[11px] leading-relaxed break-all">
                            curl -e "https://unauthorized-domain.com" -I https://{{ stats.primary_domain }}/image.jpg
                        </div>
                        <p class="text-emerald-700 font-mono font-bold text-[11px]">
                            Expected Result: HTTP/1.1 403 Forbidden
                        </p>
                    </div>
                </div>

            </div>

        </div>

    </AuthenticatedLayout>
</template>
