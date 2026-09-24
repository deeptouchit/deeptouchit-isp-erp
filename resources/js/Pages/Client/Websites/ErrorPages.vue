<script setup>
import { ref, computed } from 'vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import { 
    DocumentTextIcon, 
    PencilSquareIcon, 
    EyeIcon, 
    ArrowPathIcon, 
    CheckCircleIcon, 
    XMarkIcon,
    ShieldExclamationIcon,
    ExclamationTriangleIcon,
    BoltIcon,
    GlobeAltIcon,
    CodeBracketIcon,
    SparklesIcon,
    PaintBrushIcon,
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
    selected_domain: {
        type: String,
        default: ''
    },
    error_codes: {
        type: Array,
        default: () => []
    }
})

const currentDomain = ref(props.selected_domain)

const handleDomainChange = () => {
    router.get(route('website.error-pages'), { domain: currentDomain.value }, { preserveState: true, preserveScroll: true })
}

// Editor Modal State
const showEditorModal = ref(false)
const activeModalTab = ref('visual') // 'visual', 'code', 'preview'
const activeErrorCode = ref(null)

const form = useForm({
    domain: props.selected_domain,
    code: 404,
    content: '',
})

// Visual Builder Reactive State
const visualState = ref({
    title: '',
    message: '',
    btnText: 'Return Home',
    btnUrl: '/',
    secondaryText: 'Contact Support',
    secondaryUrl: 'mailto:support@' + props.selected_domain,
    themeColor: 'blue', // 'blue', 'rose', 'emerald', 'purple', 'amber'
})

const openEditor = (ec, defaultTab = 'visual') => {
    activeErrorCode.value = ec
    form.domain = currentDomain.value
    form.code = ec.code
    form.content = ec.content
    activeModalTab.value = defaultTab

    // Initialize visual builder with defaults from code
    visualState.value.title = ec.title
    visualState.value.message = 'The requested URL could not be processed on ' + currentDomain.value + '. Please check the address or return home.'
    visualState.value.btnText = 'Return Home'
    visualState.value.btnUrl = '/'
    visualState.value.secondaryText = 'Contact Support'
    visualState.value.secondaryUrl = 'mailto:support@' + currentDomain.value
    visualState.value.themeColor = ec.color || 'blue'

    showEditorModal.value = true
}

// Generate HTML from Visual State
const applyVisualToCode = () => {
    const colorMap = {
        blue: { code: '#38bdf8', btn: '#0284c7', hover: '#0369a1' },
        rose: { code: '#fb7185', btn: '#e11d48', hover: '#be123c' },
        emerald: { code: '#34d399', btn: '#059669', hover: '#047857' },
        purple: { code: '#c084fc', btn: '#7c3aed', hover: '#6d28d9' },
        amber: { code: '#fbbf24', btn: '#d97706', hover: '#b45309' },
    }
    const c = colorMap[visualState.value.themeColor] || colorMap.blue

    form.content = `<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>${form.code} - ${visualState.value.title} | ${form.domain}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,600,800&display=swap" rel="stylesheet" />
    <style>
        body { font-family: 'Figtree', sans-serif; background: #0f172a; color: #f8fafc; margin: 0; display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 20px; box-sizing: border-box; }
        .card { background: #1e293b; border: 1px solid #334155; border-radius: 12px; padding: 48px; max-width: 560px; width: 100%; text-align: center; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5); }
        .code { font-size: 72px; font-weight: 900; line-height: 1; color: ${c.code}; font-family: monospace; letter-spacing: -2px; margin-bottom: 8px; }
        h1 { font-size: 22px; font-weight: 800; margin: 0 0 12px 0; color: #ffffff; }
        p { color: #94a3b8; font-size: 14px; line-height: 1.6; margin: 0 0 28px 0; }
        .actions { display: flex; justify-content: center; gap: 12px; flex-wrap: wrap; }
        .btn { background: ${c.btn}; color: white; text-decoration: none; padding: 10px 20px; border-radius: 6px; font-weight: 700; font-size: 13px; transition: 0.2s; display: inline-flex; align-items: center; gap: 6px; }
        .btn:hover { background: ${c.hover}; }
        .btn-outline { background: transparent; color: #cbd5e1; border: 1px solid #475569; }
        .btn-outline:hover { background: #334155; color: white; }
        .footer { margin-top: 32px; padding-top: 20px; border-top: 1px solid #334155; font-size: 12px; color: #64748b; }
    </style>
</head>
<body>
    <div class="card">
        <div class="code">${form.code}</div>
        <h1>${visualState.value.title}</h1>
        <p>${visualState.value.message}</p>
        <div class="actions">
            <a href="${visualState.value.btnUrl}" class="btn">${visualState.value.btnText}</a>
            <a href="${visualState.value.secondaryUrl}" class="btn btn-outline">${visualState.value.secondaryText}</a>
        </div>
        <div class="footer">
            Hosted with high-availability on <strong>${form.domain}</strong>
        </div>
    </div>
</body>
</html>`
}

const applyPreset = (presetType) => {
    if (presetType === 'minimal') {
        visualState.value.title = activeErrorCode.value?.title || 'Page Not Found'
        visualState.value.message = 'The resource you requested is temporarily unavailable or does not exist.'
        visualState.value.themeColor = 'blue'
    } else if (presetType === 'security') {
        visualState.value.title = 'Access Restricted by Firewall'
        visualState.value.message = 'Your IP or request was blocked due to active security policies.'
        visualState.value.themeColor = 'rose'
    } else if (presetType === 'maintenance') {
        visualState.value.title = 'Scheduled Maintenance in Progress'
        visualState.value.message = 'We are performing planned upgrades. Service will resume shortly.'
        visualState.value.themeColor = 'amber'
    }
    applyVisualToCode()
}

const submitSave = () => {
    if (activeModalTab.value === 'visual') {
        applyVisualToCode()
    }
    form.post(route('website.error-pages.save'), {
        preserveScroll: true,
        onSuccess: () => {
            showEditorModal.value = false
        }
    })
}

const resetPage = (ec) => {
    if (confirm(`Reset HTTP ${ec.code} to standard server template?`)) {
        router.post(route('website.error-pages.reset'), {
            domain: currentDomain.value,
            code: ec.code
        }, { preserveScroll: true })
    }
}
</script>

<template>
    <Head title="Custom Error Pages (404, 403, 500) - DeepTouch Host" />

    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto space-y-4">
            
            <!-- 1. Page Header with Breadcrumbs & Domain Selector -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Website Tools', href: '#' },
                    { label: 'Custom Error Pages' }
                ]"
                :show-refresh="true"
                @refreshed="router.reload({ preserveScroll: true })"
            >
                <template #actions>
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-slate-500 font-bold hidden sm:inline">Active Domain:</span>
                        <select 
                            v-model="currentDomain" 
                            @change="handleDomainChange"
                            class="text-xs font-bold rounded-[3px] border-slate-300 bg-white text-slate-900 py-1.5 px-2.5 focus:ring-blue-500 focus:border-blue-500 shadow-2xs"
                        >
                            <option v-for="site in websites" :key="site.id" :value="site.domain">
                                {{ site.domain }}
                            </option>
                        </select>
                    </div>
                </template>
            </PageHeader>

            <!-- 2. Top Analytics & Brand KPI Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                
                <!-- Card 1: Managed HTTP Status Codes -->
                <div class="bg-white rounded-lg border border-slate-200 p-4 shadow-2xs space-y-2.5">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                            <DocumentTextIcon class="w-4 h-4 text-blue-600" />
                            <span>HTTP Status Handlers</span>
                        </span>
                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-blue-50 text-blue-700 border border-blue-200 uppercase font-mono">
                            6 ACTIVE
                        </span>
                    </div>

                    <div>
                        <div class="flex items-baseline justify-between">
                            <span class="text-2xl font-black text-slate-900 font-mono">400 - 503</span>
                            <span class="text-xs text-slate-500 font-mono">Nginx / Apache</span>
                        </div>
                        <div class="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden mt-2">
                            <div class="bg-blue-600 h-full rounded-full w-full"></div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between text-[11px] text-slate-500 pt-1 border-t border-slate-100">
                        <span>Interception Mode:</span>
                        <strong class="text-slate-700">Direct Fast Static Buffer</strong>
                    </div>
                </div>

                <!-- Card 2: Brand Recovery & Bounce Prevention -->
                <div class="bg-white rounded-lg border border-slate-200 p-4 shadow-2xs space-y-2.5">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                            <PaintBrushIcon class="w-4 h-4 text-purple-600" />
                            <span>Brand & SEO Recovery</span>
                        </span>
                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-purple-50 text-purple-700 border border-purple-200 uppercase font-mono">
                            CUSTOM
                        </span>
                    </div>

                    <div>
                        <div class="flex items-baseline justify-between">
                            <span class="text-2xl font-black text-purple-600 font-mono">Zero Bounce</span>
                            <span class="text-xs text-slate-500 font-mono">Retain Traffic</span>
                        </div>
                        <div class="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden mt-2">
                            <div class="bg-purple-500 h-full rounded-full w-full"></div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between text-[11px] text-slate-500 pt-1 border-t border-slate-100">
                        <span>Navigation Links:</span>
                        <strong class="text-purple-700 font-bold">Auto Home & Support Route</strong>
                    </div>
                </div>

                <!-- Card 3: Nginx ErrorDocument Acceleration -->
                <div class="bg-white rounded-lg border border-slate-200 p-4 shadow-2xs space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                            <BoltIcon class="w-4 h-4 text-emerald-600" />
                            <span>Fast Delivery Engine</span>
                        </span>
                        <span class="text-[10px] font-bold text-slate-500 uppercase font-mono">
                            MICROSECOND
                        </span>
                    </div>

                    <div class="space-y-1 text-xs font-mono">
                        <div class="flex items-center justify-between p-1.5 bg-slate-50 rounded border border-slate-200/70">
                            <span class="text-slate-500 text-[11px]">Storage Path:</span>
                            <span class="font-bold text-slate-800">public_html/{code}.html</span>
                        </div>

                        <div class="flex items-center justify-between p-1.5 bg-slate-50 rounded border border-slate-200/70">
                            <span class="text-slate-500 text-[11px]">Memory Overhead:</span>
                            <span class="font-bold text-emerald-700">0 MB (Zero PHP Spawn)</span>
                        </div>
                    </div>
                </div>

            </div>

            <!-- 3. Error Pages Grid (6 High Impact Cards) -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                
                <div 
                    v-for="ec in error_codes" 
                    :key="ec.code"
                    class="bg-white rounded-lg border border-slate-200 p-5 shadow-2xs flex flex-col justify-between hover:shadow-md hover:border-blue-300 transition-all duration-200 relative group"
                >
                    <!-- Most Common Badge -->
                    <span 
                        v-if="ec.is_common"
                        class="absolute -top-2.5 right-4 px-2 py-0.5 rounded-[3px] text-[9.5px] font-bold uppercase tracking-wider bg-slate-800 text-white shadow-2xs font-mono"
                    >
                        Most Common
                    </span>

                    <div>
                        <!-- Header & Status -->
                        <div class="flex items-start justify-between mb-3">
                            <div class="flex items-center gap-2.5">
                                <div 
                                    class="w-11 h-11 rounded-lg border flex items-center justify-center font-mono font-black text-base shadow-2xs"
                                    :class="{
                                        'bg-blue-50 border-blue-200 text-blue-700': ec.color === 'blue',
                                        'bg-rose-50 border-rose-200 text-rose-700': ec.color === 'rose',
                                        'bg-purple-50 border-purple-200 text-purple-700': ec.color === 'purple',
                                        'bg-amber-50 border-amber-200 text-amber-700': ec.color === 'amber',
                                    }"
                                >
                                    {{ ec.code }}
                                </div>
                                <div>
                                    <h3 class="font-bold text-slate-900 text-sm">{{ ec.title }}</h3>
                                    <span 
                                        class="text-[10px] font-bold px-1.5 py-0.2 rounded font-mono inline-block mt-0.5"
                                        :class="ec.is_customized ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600'"
                                    >
                                        {{ ec.is_customized ? 'Custom Template Active' : 'System Default' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Description -->
                        <p class="text-xs text-slate-500 leading-relaxed line-clamp-2 mb-4">
                            {{ ec.description }}
                        </p>
                    </div>

                    <!-- Bottom Actions -->
                    <div class="pt-3 border-t border-slate-100 flex items-center justify-between gap-2">
                        <div class="flex items-center gap-1.5">
                            <button 
                                @click="openEditor(ec, 'visual')"
                                class="px-2.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold flex items-center gap-1 shadow-2xs transition cursor-pointer"
                            >
                                <PencilSquareIcon class="w-3.5 h-3.5" />
                                <span>Customize</span>
                            </button>

                            <button 
                                @click="openEditor(ec, 'preview')"
                                class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-[3px] text-xs font-bold flex items-center gap-1 shadow-2xs transition cursor-pointer"
                            >
                                <EyeIcon class="w-3.5 h-3.5 text-slate-500" />
                                <span>Preview</span>
                            </button>
                        </div>

                        <button 
                            v-if="ec.is_customized"
                            @click="resetPage(ec)"
                            class="p-1.5 text-slate-400 hover:text-rose-600 rounded transition cursor-pointer"
                            title="Reset to default"
                        >
                            <ArrowPathIcon class="w-4 h-4" />
                        </button>
                    </div>

                </div>

            </div>

        </div>

        <!-- 4. Interactive Live Visual & Code Editor Modal -->
        <div v-if="showEditorModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-2xs flex items-center justify-center p-4 z-50">
            <div class="bg-white rounded-lg max-w-4xl w-full p-5 shadow-2xl space-y-4 border border-slate-200 max-h-[92vh] flex flex-col">
                
                <!-- Modal Header -->
                <div class="flex items-center justify-between border-b border-slate-100 pb-3 shrink-0">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-[4px] bg-blue-600 text-white flex items-center justify-center font-black font-mono text-xs shadow-2xs">
                            {{ form.code }}
                        </div>
                        <div>
                            <h3 class="font-bold text-slate-900 text-sm">Customize HTTP {{ form.code }} - {{ activeErrorCode?.title }}</h3>
                            <span class="text-[11px] text-slate-500 font-mono">Domain: https://{{ currentDomain }}</span>
                        </div>
                    </div>

                    <!-- Editor Mode Tabs -->
                    <div class="flex items-center gap-1 bg-slate-100 p-0.5 rounded-[4px]">
                        <button 
                            @click="activeModalTab = 'visual'"
                            class="px-2.5 py-1 rounded-[3px] text-xs font-bold transition cursor-pointer"
                            :class="activeModalTab === 'visual' ? 'bg-white text-blue-600 shadow-2xs' : 'text-slate-600 hover:text-slate-900'"
                        >
                            Visual Builder
                        </button>
                        <button 
                            @click="applyVisualToCode(); activeModalTab = 'code'"
                            class="px-2.5 py-1 rounded-[3px] text-xs font-bold transition cursor-pointer"
                            :class="activeModalTab === 'code' ? 'bg-white text-blue-600 shadow-2xs' : 'text-slate-600 hover:text-slate-900'"
                        >
                            HTML Source
                        </button>
                        <button 
                            @click="applyVisualToCode(); activeModalTab = 'preview'"
                            class="px-2.5 py-1 rounded-[3px] text-xs font-bold transition cursor-pointer"
                            :class="activeModalTab === 'preview' ? 'bg-white text-blue-600 shadow-2xs' : 'text-slate-600 hover:text-slate-900'"
                        >
                            Live Preview
                        </button>
                    </div>

                    <button @click="showEditorModal = false" class="text-slate-400 hover:text-slate-600 p-1 cursor-pointer">
                        <XMarkIcon class="w-5 h-5" />
                    </button>
                </div>

                <!-- Modal Body Area -->
                <div class="flex-1 overflow-y-auto space-y-4 pr-1">
                    
                    <!-- TAB 1: Visual Builder -->
                    <div v-if="activeModalTab === 'visual'" class="space-y-4 text-xs">
                        
                        <!-- Quick Presets -->
                        <div class="p-3 bg-slate-50 border border-slate-200 rounded space-y-2">
                            <span class="font-bold text-slate-700 block text-[11px] uppercase tracking-wider">Quick Template Presets</span>
                            <div class="flex flex-wrap gap-2">
                                <button 
                                    type="button" 
                                    @click="applyPreset('minimal')" 
                                    class="px-2.5 py-1 bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 rounded-[3px] font-bold cursor-pointer transition shadow-2xs"
                                >
                                    Modern Minimalist
                                </button>
                                <button 
                                    type="button" 
                                    @click="applyPreset('security')" 
                                    class="px-2.5 py-1 bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 rounded-[3px] font-bold cursor-pointer transition shadow-2xs"
                                >
                                    Security & Firewall Block
                                </button>
                                <button 
                                    type="button" 
                                    @click="applyPreset('maintenance')" 
                                    class="px-2.5 py-1 bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 rounded-[3px] font-bold cursor-pointer transition shadow-2xs"
                                >
                                    Scheduled Maintenance
                                </button>
                            </div>
                        </div>

                        <!-- Content Inputs -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="space-y-1">
                                <label class="block font-bold text-slate-700">Heading Title</label>
                                <input 
                                    type="text" 
                                    v-model="visualState.title"
                                    class="w-full text-xs rounded-[3px] border-slate-300 bg-white text-slate-900 py-2 px-2.5"
                                />
                            </div>

                            <div class="space-y-1">
                                <label class="block font-bold text-slate-700">Accent Theme Color</label>
                                <select 
                                    v-model="visualState.themeColor"
                                    class="w-full text-xs font-bold rounded-[3px] border-slate-300 bg-white text-slate-900 py-2 px-2.5"
                                >
                                    <option value="blue">Deep Sky Blue</option>
                                    <option value="rose">Crimson Rose</option>
                                    <option value="emerald">Emerald Green</option>
                                    <option value="purple">Royal Purple</option>
                                    <option value="amber">Amber Warning</option>
                                </select>
                            </div>
                        </div>

                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Custom Message & Instructions</label>
                            <textarea 
                                v-model="visualState.message"
                                rows="3"
                                class="w-full text-xs rounded-[3px] border-slate-300 bg-white text-slate-900 py-2 px-2.5"
                            ></textarea>
                        </div>

                        <!-- Buttons Config -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2 border-t border-slate-100">
                            <div class="space-y-2">
                                <span class="font-bold text-slate-700 block">Primary Action Button</span>
                                <input 
                                    type="text" 
                                    v-model="visualState.btnText" 
                                    placeholder="Button label (e.g. Return Home)"
                                    class="w-full text-xs rounded-[3px] border-slate-300 bg-white text-slate-900 py-1.5 px-2.5 mb-1.5"
                                />
                                <input 
                                    type="text" 
                                    v-model="visualState.btnUrl" 
                                    placeholder="Destination URL (e.g. /)"
                                    class="w-full text-xs font-mono rounded-[3px] border-slate-300 bg-white text-slate-900 py-1.5 px-2.5"
                                />
                            </div>

                            <div class="space-y-2">
                                <span class="font-bold text-slate-700 block">Secondary Link</span>
                                <input 
                                    type="text" 
                                    v-model="visualState.secondaryText" 
                                    placeholder="Button label (e.g. Contact Support)"
                                    class="w-full text-xs rounded-[3px] border-slate-300 bg-white text-slate-900 py-1.5 px-2.5 mb-1.5"
                                />
                                <input 
                                    type="text" 
                                    v-model="visualState.secondaryUrl" 
                                    placeholder="Link URL (e.g. mailto:support@...)"
                                    class="w-full text-xs font-mono rounded-[3px] border-slate-300 bg-white text-slate-900 py-1.5 px-2.5"
                                />
                            </div>
                        </div>

                    </div>

                    <!-- TAB 2: HTML Source Code -->
                    <div v-else-if="activeModalTab === 'code'" class="space-y-2">
                        <div class="flex items-center justify-between text-xs text-slate-500">
                            <span class="font-mono">Full HTML5 Document Template</span>
                            <span>Target: <code>public_html/{{ form.code }}.html</code></span>
                        </div>
                        <textarea 
                            v-model="form.content"
                            rows="16"
                            class="w-full text-xs font-mono bg-slate-950 text-emerald-400 p-3.5 rounded border border-slate-800 leading-relaxed focus:ring-blue-500 focus:border-blue-500"
                        ></textarea>
                    </div>

                    <!-- TAB 3: Live Preview Sandbox -->
                    <div v-else-if="activeModalTab === 'preview'" class="border border-slate-300 rounded overflow-hidden shadow-inner bg-slate-900 h-96">
                        <iframe 
                            :srcdoc="form.content"
                            class="w-full h-full border-0"
                            sandbox="allow-scripts"
                        ></iframe>
                    </div>

                </div>

                <!-- Modal Footer -->
                <div class="flex items-center justify-between pt-3 border-t border-slate-100 shrink-0">
                    <button 
                        type="button" 
                        @click="showEditorModal = false" 
                        class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-[3px] text-xs font-bold cursor-pointer"
                    >
                        Cancel
                    </button>

                    <button 
                        type="button" 
                        @click="submitSave"
                        :disabled="form.processing"
                        class="px-5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold shadow-2xs transition cursor-pointer disabled:opacity-50 flex items-center gap-1.5"
                    >
                        <CheckCircleIcon class="w-4 h-4" />
                        <span>{{ form.processing ? 'Saving...' : `Save & Deploy ${form.code}.html` }}</span>
                    </button>
                </div>

            </div>
        </div>

    </AuthenticatedLayout>
</template>
