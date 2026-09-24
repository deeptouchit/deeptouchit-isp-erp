<script setup>
import { ref, computed } from 'vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import { 
    SparklesIcon, 
    PaintBrushIcon, 
    ArrowDownTrayIcon, 
    CheckCircleIcon, 
    GlobeAltIcon, 
    DevicePhoneMobileIcon,
    WindowIcon,
    CodeBracketIcon,
    DocumentDuplicateIcon,
    RocketLaunchIcon,
    BoltIcon,
    KeyIcon,
    ShieldCheckIcon,
    FolderArrowDownIcon
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
        default: 'somitysoft.com'
    },
    has_favicon: {
        type: Boolean,
        default: false
    },
    has_manifest: {
        type: Boolean,
        default: false
    }
})

const currentDomain = ref(props.selected_domain)

const handleDomainChange = () => {
    router.get(route('website.logo-maker'), { domain: currentDomain.value }, { preserveState: true, preserveScroll: true })
}

// State for Brand Generator
const brandState = ref({
    name: props.selected_domain ? props.selected_domain.split('.')[0].toUpperCase() : 'DEEPTOUCH',
    slogan: 'Cloud Hosting & Web Solutions',
    selectedIcon: 'rocket',
    iconShape: 'rounded', // 'rounded', 'circle', 'square', 'outline'
    layout: 'horizontal', // 'horizontal', 'stacked', 'badge'
    fontStyle: 'sans', // 'sans', 'mono', 'serif', 'display'
    colorPreset: 'cyan', // 'cyan', 'purple', 'emerald', 'amber', 'rose', 'slate'
    canvasBg: 'dark', // 'dark', 'light'
})

// Curated Vector Icons Library
const iconsList = [
    { id: 'rocket', name: 'Rocket Launch', path: 'M15.59 14.37a6 6 0 01-5.84 7.38v-4.8m5.84-2.58a14.98 14.98 0 006.16-12.12A14.98 14.98 0 009.631 8.41m5.96 5.96a14.926 14.926 0 01-5.841 2.58m-.119-8.54a6 6 0 00-7.381 5.84h4.8m2.58-5.84a14.927 14.927 0 00-2.58 5.84m2.699 2.7c-.103.021-.207.041-.311.06a15.09 15.09 0 01-2.448-2.448 14.9 14.9 0 01.06-.312m-2.24 2.39a4.493 4.493 0 00-1.757 4.306 4.493 4.493 0 004.306-1.758M16.5 9a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z' },
    { id: 'cloud', name: 'Cloud Server', path: 'M2.25 15a4.5 4.5 0 004.5 4.5H18a3.75 3.75 0 001.332-7.257 3 3 0 00-3.758-3.848 5.25 5.25 0 00-10.233 2.33A4.502 4.502 0 002.25 15z' },
    { id: 'bolt', name: 'Lightning Speed', path: 'M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z' },
    { id: 'shield', name: 'Cyber Shield', path: 'M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z' },
    { id: 'server', name: 'High Availability', path: 'M5.25 14.25h13.5m-13.5 0a3 3 0 01-3-3m3 3a3 3 0 00-3 3m3.75 0h7.5m9-3h-9m9 0a3 3 0 013 3m-3-3a3 3 0 003-3m-3.75 0H16.5m-9-6h13.5m-13.5 0a3 3 0 01-3-3m3 3a3 3 0 00-3 3m3.75 0h7.5m9-3h-9m9 0a3 3 0 013 3m-3-3a3 3 0 003-3m-3.75 0H16.5' },
    { id: 'cpu', name: 'Quantum Core', path: 'M8.25 3v1.5M4.5 8.25H3m18 0h-1.5M4.5 12H3m18 0h-1.5m-15 3.75H3m18 0h-1.5M8.25 19.5V21M12 3v1.5m0 15V21m3.75-18v1.5m0 15V21m-9-1.5h10.5a2.25 2.25 0 002.25-2.25V6.75a2.25 2.25 0 00-2.25-2.25H6.75A2.25 2.25 0 004.5 6.75v10.5a2.25 2.25 0 002.25 2.25zm.75-12h9v9h-9v-9z' },
    { id: 'sparkles', name: 'AI Sparkles', path: 'M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z' },
    { id: 'database', name: 'Relational DB', path: 'M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 5.625c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125' },
]

// Color Presets Map
const colorThemes = {
    cyan: { name: 'Ocean Cyan', from: '#0284c7', to: '#06b6d4', hex: '#0284c7', glow: 'rgba(6, 182, 212, 0.4)' },
    purple: { name: 'Nebula Violet', from: '#6366f1', to: '#a855f7', hex: '#6366f1', glow: 'rgba(168, 85, 247, 0.4)' },
    emerald: { name: 'Fintech Green', from: '#059669', to: '#10b981', hex: '#059669', glow: 'rgba(16, 185, 129, 0.4)' },
    amber: { name: 'Solar Amber', from: '#ea580c', to: '#f59e0b', hex: '#ea580c', glow: 'rgba(245, 158, 11, 0.4)' },
    rose: { name: 'Cyber Crimson', from: '#e11d48', to: '#fb7185', hex: '#e11d48', glow: 'rgba(225, 29, 72, 0.4)' },
    slate: { name: 'Titanium Dark', from: '#1e293b', to: '#475569', hex: '#334155', glow: 'rgba(71, 85, 105, 0.4)' },
}

const currentTheme = computed(() => colorThemes[brandState.value.colorPreset] || colorThemes.cyan)
const currentIconObj = computed(() => iconsList.find(i => i.id === brandState.value.selectedIcon) || iconsList[0])

// Generated SVG Strings
const generatedFaviconSvg = computed(() => {
    const t = currentTheme.value
    const icon = currentIconObj.value
    let shapeSvg = ''
    if (brandState.value.iconShape === 'circle') {
        shapeSvg = `<circle cx="32" cy="32" r="30" fill="url(#grad)" />`
    } else if (brandState.value.iconShape === 'square') {
        shapeSvg = `<rect x="2" y="2" width="60" height="60" rx="4" fill="url(#grad)" />`
    } else {
        shapeSvg = `<rect x="2" y="2" width="60" height="60" rx="14" fill="url(#grad)" />`
    }

    return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" width="64" height="64">
  <defs>
    <linearGradient id="grad" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="${t.from}" />
      <stop offset="100%" stop-color="${t.to}" />
    </linearGradient>
  </defs>
  ${shapeSvg}
  <g transform="translate(14, 14) scale(1.5)" fill="none" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
    <path d="${icon.path}" />
  </g>
</svg>`
})

// Deployment Form
const deployForm = useForm({
    domain: props.selected_domain,
    brand_name: brandState.value.name,
    svg_favicon: '',
    theme_color: '',
})

const handleDeploy = () => {
    deployForm.domain = currentDomain.value
    deployForm.brand_name = brandState.value.name
    deployForm.svg_favicon = generatedFaviconSvg.value
    deployForm.theme_color = currentTheme.value.hex

    deployForm.post(route('website.logo-maker.deploy'), {
        preserveScroll: true
    })
}

// Download Asset
const downloadSvg = () => {
    const blob = new Blob([generatedFaviconSvg.value], { type: 'image/svg+xml' })
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `${brandState.value.name.toLowerCase()}-favicon.svg`
    a.click()
    URL.revokeObjectURL(url)
}

// Copy Code Snippet
const copiedSnippet = ref(false)
const htmlSnippet = computed(() => {
    return `<!-- DeepTouch Favicon & Web App Manifest Suite -->
<link rel="icon" type="image/svg+xml" href="/favicon.svg">
<link rel="alternate icon" href="/favicon.ico">
<link rel="apple-touch-icon" href="/favicon.svg">
<link rel="manifest" href="/site.webmanifest">
<meta name="theme-color" content="${currentTheme.value.hex}">`
})

const copySnippet = () => {
    navigator.clipboard.writeText(htmlSnippet.value)
    copiedSnippet.value = true
    setTimeout(() => {
        copiedSnippet.value = false
    }, 2000)
}
</script>

<template>
    <Head title="Brand & Favicon Generator Studio - DeepTouch Host" />

    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto space-y-4">
            
            <!-- 1. Page Header with Breadcrumbs & Domain Selector -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Website Tools', href: '#' },
                    { label: 'Brand & Favicon Studio' }
                ]"
                :show-refresh="true"
                @refreshed="router.reload({ preserveScroll: true })"
            >
                <template #actions>
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-slate-500 font-bold hidden sm:inline">Target Website:</span>
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

            <!-- 2. Top 3 Studio Metric Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                
                <!-- Card 1: Vector SVG & High-Res Engine -->
                <div class="bg-white rounded-lg border border-slate-200 p-4 shadow-2xs space-y-2.5">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                            <SparklesIcon class="w-4 h-4 text-purple-600" />
                            <span>Vector SVG Engine</span>
                        </span>
                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-purple-50 text-purple-700 border border-purple-200 uppercase font-mono">
                            LOSSLESS
                        </span>
                    </div>

                    <div>
                        <div class="flex items-baseline justify-between">
                            <span class="text-2xl font-black text-slate-900 font-mono">Infinite Scale</span>
                            <span class="text-xs text-slate-500 font-mono">Retina Crisp</span>
                        </div>
                        <div class="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden mt-2">
                            <div class="bg-purple-600 h-full rounded-full w-full"></div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between text-[11px] text-slate-500 pt-1 border-t border-slate-100">
                        <span>Output Formats:</span>
                        <strong class="text-purple-700 font-bold">SVG, ICO, PNG & Manifest</strong>
                    </div>
                </div>

                <!-- Card 2: PWA & Mobile Web Ready -->
                <div class="bg-white rounded-lg border border-slate-200 p-4 shadow-2xs space-y-2.5">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                            <DevicePhoneMobileIcon class="w-4 h-4 text-blue-600" />
                            <span>PWA & iOS Suite</span>
                        </span>
                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-blue-50 text-blue-700 border border-blue-200 uppercase font-mono">
                            AUTO-SYNC
                        </span>
                    </div>

                    <div>
                        <div class="flex items-baseline justify-between">
                            <span class="text-2xl font-black text-blue-600 font-mono">site.webmanifest</span>
                            <span class="text-xs text-slate-500 font-mono">iOS & Android</span>
                        </div>
                        <div class="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden mt-2">
                            <div class="bg-blue-500 h-full rounded-full w-full"></div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between text-[11px] text-slate-500 pt-1 border-t border-slate-100">
                        <span>Status on {{ currentDomain }}:</span>
                        <span 
                            class="text-[11px] font-bold font-mono"
                            :class="has_favicon ? 'text-emerald-600' : 'text-amber-600'"
                        >
                            {{ has_favicon ? 'Active on Server' : 'Ready to Deploy' }}
                        </span>
                    </div>
                </div>

                <!-- Card 3: 1-Click Server Auto-Deploy -->
                <div class="bg-white rounded-lg border border-slate-200 p-4 shadow-2xs space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                            <RocketLaunchIcon class="w-4 h-4 text-emerald-600" />
                            <span>Instant Root Deployment</span>
                        </span>
                        <span class="text-[10px] font-bold text-slate-500 uppercase font-mono">
                            1-CLICK
                        </span>
                    </div>

                    <div class="space-y-1 text-xs font-mono">
                        <div class="flex items-center justify-between p-1.5 bg-slate-50 rounded border border-slate-200/70">
                            <span class="text-slate-500 text-[11px]">Deploy Path:</span>
                            <span class="font-bold text-slate-800">public_html/favicon.svg</span>
                        </div>

                        <div class="flex items-center justify-between p-1.5 bg-slate-50 rounded border border-slate-200/70">
                            <span class="text-slate-500 text-[11px]">Upload Time:</span>
                            <span class="font-bold text-emerald-700">0.02s Instant Hook</span>
                        </div>
                    </div>
                </div>

            </div>

            <!-- 3. Studio 2-Column Workstation Layout -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">
                
                <!-- Left Column: Design Controls (5 Cols) -->
                <div class="lg:col-span-5 bg-white rounded-lg border border-slate-200 p-5 shadow-2xs space-y-4">
                    
                    <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <PaintBrushIcon class="w-4 h-4 text-blue-600" />
                            <h3 class="font-bold text-slate-900 text-sm">Brand & Icon Customizer</h3>
                        </div>
                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-slate-100 text-slate-600 font-mono uppercase">
                            Vector Controls
                        </span>
                    </div>

                    <!-- Brand Name & Slogan -->
                    <div class="space-y-3">
                        <div class="space-y-1">
                            <label class="block text-xs font-bold text-slate-700">Brand / Project Name</label>
                            <input 
                                type="text" 
                                v-model="brandState.name"
                                placeholder="e.g. SOMITYSOFT"
                                class="w-full text-xs font-bold uppercase tracking-wider rounded-[3px] border-slate-300 bg-white text-slate-900 py-2 px-2.5 focus:ring-blue-500 focus:border-blue-500"
                            />
                        </div>

                        <div class="space-y-1">
                            <label class="block text-xs font-bold text-slate-700">Slogan / Tagline</label>
                            <input 
                                type="text" 
                                v-model="brandState.slogan"
                                placeholder="e.g. Cloud Hosting Solutions"
                                class="w-full text-xs rounded-[3px] border-slate-300 bg-white text-slate-900 py-2 px-2.5 focus:ring-blue-500 focus:border-blue-500"
                            />
                        </div>
                    </div>

                    <!-- Vector Symbol Selection (Grid) -->
                    <div class="space-y-1.5 pt-2 border-t border-slate-100">
                        <label class="block text-xs font-bold text-slate-700">Select Vector Symbol</label>
                        <div class="grid grid-cols-4 gap-2">
                            <button 
                                v-for="ico in iconsList" 
                                :key="ico.id"
                                type="button"
                                @click="brandState.selectedIcon = ico.id"
                                class="p-2.5 rounded-[4px] border flex flex-col items-center justify-center gap-1 transition cursor-pointer"
                                :class="brandState.selectedIcon === ico.id ? 'border-blue-500 bg-blue-50/50 text-blue-600 shadow-2xs ring-1 ring-blue-500' : 'border-slate-200 hover:border-slate-300 text-slate-600 hover:bg-slate-50'"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="ico.path" />
                                </svg>
                                <span class="text-[9.5px] font-bold truncate max-w-full">{{ ico.name.split(' ')[0] }}</span>
                            </button>
                        </div>
                    </div>

                    <!-- Color Gradient Presets -->
                    <div class="space-y-1.5 pt-2 border-t border-slate-100">
                        <label class="block text-xs font-bold text-slate-700">Color & Gradient Preset</label>
                        <div class="grid grid-cols-3 gap-2">
                            <button 
                                v-for="(thm, k) in colorThemes" 
                                :key="k"
                                type="button"
                                @click="brandState.colorPreset = k"
                                class="p-2 rounded-[3px] border flex items-center gap-2 transition cursor-pointer"
                                :class="brandState.colorPreset === k ? 'border-slate-900 bg-slate-50 ring-1 ring-slate-900' : 'border-slate-200 hover:border-slate-300'"
                            >
                                <div class="w-4 h-4 rounded-full shadow-xs shrink-0" :style="{ background: `linear-gradient(135deg, ${thm.from}, ${thm.to})` }"></div>
                                <span class="text-[10.5px] font-bold text-slate-800 truncate">{{ thm.name.split(' ')[0] }}</span>
                            </button>
                        </div>
                    </div>

                    <!-- Badge Shape & Layout Controls -->
                    <div class="grid grid-cols-2 gap-3 pt-2 border-t border-slate-100">
                        <div class="space-y-1">
                            <label class="block text-xs font-bold text-slate-700">Icon Badge Shape</label>
                            <select 
                                v-model="brandState.iconShape"
                                class="w-full text-xs font-bold rounded-[3px] border-slate-300 bg-white text-slate-900 py-1.5 px-2 focus:ring-blue-500 focus:border-blue-500"
                            >
                                <option value="rounded">Squircle (Rounded)</option>
                                <option value="circle">Circle</option>
                                <option value="square">Crisp Box</option>
                            </select>
                        </div>

                        <div class="space-y-1">
                            <label class="block text-xs font-bold text-slate-700">Layout Structure</label>
                            <select 
                                v-model="brandState.layout"
                                class="w-full text-xs font-bold rounded-[3px] border-slate-300 bg-white text-slate-900 py-1.5 px-2 focus:ring-blue-500 focus:border-blue-500"
                            >
                                <option value="horizontal">Horizontal Logo</option>
                                <option value="stacked">Stacked Centered</option>
                                <option value="badge">Icon Badge Only</option>
                            </select>
                        </div>
                    </div>

                </div>

                <!-- Right Column: Live Mockup Workstation & Deployment (7 Cols) -->
                <div class="lg:col-span-7 space-y-4">
                    
                    <!-- Main Logo Showcase Canvas -->
                    <div class="bg-white rounded-lg border border-slate-200 shadow-2xs overflow-hidden">
                        
                        <!-- Canvas Top Toolbar -->
                        <div class="px-4 py-2.5 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <WindowIcon class="w-4 h-4 text-slate-600" />
                                <span class="text-xs font-bold text-slate-800">Live Vector Brand Canvas</span>
                            </div>

                            <div class="flex items-center gap-1.5">
                                <button 
                                    type="button" 
                                    @click="brandState.canvasBg = 'dark'"
                                    class="px-2 py-1 rounded-[3px] text-[11px] font-bold transition cursor-pointer"
                                    :class="brandState.canvasBg === 'dark' ? 'bg-slate-900 text-white shadow-2xs' : 'bg-white text-slate-600 border border-slate-200'"
                                >
                                    Dark Canvas
                                </button>
                                <button 
                                    type="button" 
                                    @click="brandState.canvasBg = 'light'"
                                    class="px-2 py-1 rounded-[3px] text-[11px] font-bold transition cursor-pointer"
                                    :class="brandState.canvasBg === 'light' ? 'bg-slate-900 text-white shadow-2xs' : 'bg-white text-slate-600 border border-slate-200'"
                                >
                                    Light Canvas
                                </button>
                            </div>
                        </div>

                        <!-- Canvas Interactive Area -->
                        <div 
                            class="p-10 flex items-center justify-center min-h-[220px] transition-colors duration-200"
                            :class="brandState.canvasBg === 'dark' ? 'bg-slate-950 text-white' : 'bg-slate-50 text-slate-900'"
                        >
                            <div 
                                class="flex items-center gap-4 transition-all duration-200"
                                :class="{
                                    'flex-row': brandState.layout === 'horizontal',
                                    'flex-col text-center': brandState.layout === 'stacked',
                                    'justify-center': brandState.layout === 'badge',
                                }"
                            >
                                <!-- Icon Badge -->
                                <div 
                                    class="w-16 h-16 flex items-center justify-center text-white shadow-lg shrink-0 transition-all duration-300"
                                    :class="{
                                        'rounded-xl': brandState.iconShape === 'rounded',
                                        'rounded-full': brandState.iconShape === 'circle',
                                        'rounded-[4px]': brandState.iconShape === 'square',
                                    }"
                                    :style="{ 
                                        background: `linear-gradient(135deg, ${currentTheme.from}, ${currentTheme.to})`,
                                        boxShadow: `0 10px 25px -5px ${currentTheme.glow}`
                                    }"
                                >
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="currentIconObj.path" />
                                    </svg>
                                </div>

                                <!-- Text Typography -->
                                <div v-if="brandState.layout !== 'badge'">
                                    <h1 class="text-2xl font-black tracking-wider uppercase font-sans leading-none">
                                        {{ brandState.name }}
                                    </h1>
                                    <p 
                                        class="text-xs font-semibold tracking-wide mt-1.5"
                                        :class="brandState.canvasBg === 'dark' ? 'text-slate-400' : 'text-slate-500'"
                                    >
                                        {{ brandState.slogan }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Canvas Bottom Quick Actions -->
                        <div class="p-3 bg-slate-50/80 border-t border-slate-200 flex items-center justify-between gap-3">
                            <button 
                                type="button" 
                                @click="downloadSvg"
                                class="px-3 py-1.5 bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 rounded-[3px] text-xs font-bold shadow-2xs flex items-center gap-1.5 transition cursor-pointer"
                            >
                                <ArrowDownTrayIcon class="w-3.5 h-3.5" />
                                <span>Download SVG</span>
                            </button>

                            <button 
                                type="button" 
                                @click="handleDeploy"
                                :disabled="deployForm.processing"
                                class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold shadow-2xs flex items-center gap-1.5 transition cursor-pointer disabled:opacity-50"
                            >
                                <RocketLaunchIcon class="w-3.5 h-3.5" />
                                <span>{{ deployForm.processing ? 'Deploying...' : `Deploy to ${currentDomain}` }}</span>
                            </button>
                        </div>

                    </div>

                    <!-- Multi-Device Mockups Grid (Browser Tab & Mobile) -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        
                        <!-- Mockup 1: Real Browser Tab Preview -->
                        <div class="bg-white rounded-lg border border-slate-200 p-3.5 shadow-2xs space-y-2">
                            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">Browser Tab Favicon Preview</span>
                            <div class="bg-slate-200 rounded-t p-2 flex items-center">
                                <div class="bg-white rounded-t px-3 py-1.5 flex items-center gap-2 text-xs font-bold text-slate-800 shadow-xs max-w-[200px] truncate">
                                    <!-- Favicon mini badge -->
                                    <div 
                                        class="w-4 h-4 rounded-[3px] flex items-center justify-center text-white shrink-0"
                                        :style="{ background: `linear-gradient(135deg, ${currentTheme.from}, ${currentTheme.to})` }"
                                    >
                                        <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" :d="currentIconObj.path" />
                                        </svg>
                                    </div>
                                    <span class="truncate">{{ brandState.name }} | {{ currentDomain }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Mockup 2: Mobile App Home Screen Icon -->
                        <div class="bg-white rounded-lg border border-slate-200 p-3.5 shadow-2xs space-y-2">
                            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">iOS & Android App Icon</span>
                            <div class="flex items-center gap-3 p-2 bg-slate-900 rounded text-white">
                                <div 
                                    class="w-10 h-10 rounded-xl flex items-center justify-center text-white shadow-md shrink-0"
                                    :style="{ background: `linear-gradient(135deg, ${currentTheme.from}, ${currentTheme.to})` }"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="currentIconObj.path" />
                                    </svg>
                                </div>
                                <div class="text-xs font-mono">
                                    <span class="font-bold block">{{ brandState.name }}</span>
                                    <span class="text-[10px] text-slate-400">PWA Manifest Ready</span>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- HTML Integration Snippet Box -->
                    <div class="bg-slate-900 text-slate-100 rounded-lg p-4 shadow-2xs space-y-2 border border-slate-800 font-mono text-xs">
                        <div class="flex items-center justify-between border-b border-slate-800 pb-2">
                            <span class="text-slate-400 font-bold text-[11px] uppercase tracking-wider">HTML &lt;head&gt; Integration Snippet</span>
                            <button 
                                @click="copySnippet"
                                class="text-[11px] text-blue-400 hover:text-white transition cursor-pointer flex items-center gap-1 font-bold"
                            >
                                <DocumentDuplicateIcon class="w-3.5 h-3.5" />
                                <span>{{ copiedSnippet ? 'Copied to Clipboard!' : 'Copy Code' }}</span>
                            </button>
                        </div>
                        <pre class="text-emerald-400 text-[11px] leading-relaxed overflow-x-auto py-1 select-all">{{ htmlSnippet }}</pre>
                    </div>

                </div>

            </div>

        </div>
    </AuthenticatedLayout>
</template>
