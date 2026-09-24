<script setup>
import { ref, computed } from 'vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import { 
    RocketLaunchIcon, 
    SparklesIcon, 
    MagnifyingGlassIcon,
    ArrowTopRightOnSquareIcon,
    CheckCircleIcon,
    ShieldCheckIcon,
    CircleStackIcon,
    GlobeAltIcon,
    BoltIcon,
    PlusIcon,
    XMarkIcon,
    TrashIcon,
    ArrowPathIcon,
    CpuChipIcon,
    EllipsisHorizontalIcon,
    ExclamationTriangleIcon
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
    apps: {
        type: Array,
        default: () => []
    },
    installed_apps: {
        type: Array,
        default: () => []
    },
    categories: {
        type: Array,
        default: () => ['All Apps', 'Most Popular', 'CMS & Blogs', 'E-Commerce', 'Frameworks', 'Forums & Community']
    },
    stats: {
        type: Object,
        default: () => ({
            available_apps: 45,
            runtime_stack: 'PHP 8.1 - 8.4 / Node.js 20 LTS',
            db_compatibility: 'MariaDB 10.11 / MySQL 8.0',
            auto_ssl: 'Automated Let’s Encrypt Active'
        })
    }
})

// Search & Filter State
const searchQuery = ref('')
const selectedCategory = ref('All Apps')

const filteredApps = computed(() => {
    return props.apps.filter(app => {
        const matchesCategory = selectedCategory.value === 'All Apps' 
            || (selectedCategory.value === 'Most Popular' && app.is_popular)
            || app.category === selectedCategory.value

        const matchesSearch = app.name.toLowerCase().includes(searchQuery.value.toLowerCase())
            || app.tagline.toLowerCase().includes(searchQuery.value.toLowerCase())

        return matchesCategory && matchesSearch
    })
})

// Install Modal State
const showModal = ref(false)
const selectedApp = ref(null)

const form = useForm({
    subscription_id: props.subscription?.id || (props.subscriptions[0]?.id ?? ''),
    app_id: '',
    app_name: '',
    domain: props.websites[0]?.domain || props.subscription?.domain || '',
    install_path: '/',
    site_title: '',
    admin_username: 'admin',
    admin_password: '',
    admin_email: 'admin@' + (props.websites[0]?.domain || props.subscription?.domain || 'example.com'),
    auto_ssl: true,
})

const generateStrongPassword = () => {
    const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#$%^&*'
    let pass = ''
    for (let i = 0; i < 16; i++) {
        pass += chars.charAt(Math.floor(Math.random() * chars.length))
    }
    form.admin_password = pass
}

const openInstallModal = (app) => {
    selectedApp.value = app
    form.app_id = app.id
    form.app_name = app.name
    form.site_title = 'My ' + app.name + ' Site'
    if (!form.admin_password) {
        generateStrongPassword()
    }
    showModal.value = true
}

const submitInstall = () => {
    form.post(route('website.installer.install'), {
        preserveScroll: true,
        onSuccess: () => {
            showModal.value = false
        }
    })
}

// Uninstall Modal State
const showUninstallModal = ref(false)
const uninstallTarget = ref(null)

const uninstallForm = useForm({
    subscription_id: props.subscription?.id || (props.subscriptions[0]?.id ?? ''),
    app_type: '',
    domain: '',
    install_path: '/',
    db_name: '',
    delete_database: true,
    delete_files: true,
    raw_id: null,
})

const openUninstallModal = (installedApp) => {
    uninstallTarget.value = installedApp
    uninstallForm.subscription_id = props.subscription?.id || (props.subscriptions[0]?.id ?? '')
    uninstallForm.app_type = installedApp.type
    uninstallForm.domain = installedApp.domain
    uninstallForm.install_path = installedApp.install_path
    uninstallForm.db_name = installedApp.db_name
    uninstallForm.raw_id = installedApp.raw_id
    uninstallForm.delete_database = true
    uninstallForm.delete_files = true
    showUninstallModal.value = true
}

const submitUninstall = () => {
    uninstallForm.post(route('website.installer.uninstall'), {
        preserveScroll: true,
        onSuccess: () => {
            showUninstallModal.value = false
        }
    })
}
</script>

<template>
    <Head title="App Auto Installer - DeepTouch Host" />

    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto space-y-4">
            
            <!-- 1. Page Header with Breadcrumbs & Actions -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Website Tools', href: '#' },
                    { label: 'Auto Installer' }
                ]"
                :show-refresh="true"
                @refreshed="router.reload({ preserveScroll: true })"
            >
                <template #actions>
                    <Link
                        :href="route('website.wordpress')"
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition"
                    >
                        <SparklesIcon class="w-3.5 h-3.5 text-blue-600" />
                        <span>WordPress Toolkit</span>
                    </Link>
                </template>
            </PageHeader>

            <!-- 2. Active Installed Applications Section (If any installed) -->
            <div v-if="installed_apps && installed_apps.length > 0" class="bg-white rounded-lg border border-slate-200 shadow-2xs overflow-hidden">
                <div class="px-4 py-3 bg-slate-50/70 border-b border-slate-200 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <CheckCircleIcon class="w-4 h-4 text-emerald-600" />
                        <span class="text-xs font-bold text-slate-800">Installed Applications ({{ installed_apps.length }})</span>
                    </div>
                    <span class="text-[11px] text-slate-500 font-mono">1-Click Management & Uninstall</span>
                </div>

                <div class="divide-y divide-slate-100">
                    <div 
                        v-for="installed in installed_apps" 
                        :key="installed.id"
                        class="p-4 flex flex-col md:flex-row md:items-center justify-between gap-4 hover:bg-slate-50/50 transition"
                    >
                        <div class="flex items-center gap-3.5">
                            <!-- App Icon -->
                            <div class="w-10 h-10 rounded-lg bg-blue-50 border border-blue-100 flex items-center justify-center shrink-0">
                                <svg v-if="installed.type === 'wordpress'" class="w-6 h-6 text-blue-600" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12 2C6.477 2 2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878L5.275 8.136C6.18 5.753 8.847 4.09 12 4.09c1.905 0 3.633.626 5.027 1.688L12 2zm8.562 10c0-1.282-.46-2.172-.857-2.868-.526-.856-1.02-1.577-1.02-2.43 0-.952.723-1.84 1.74-1.84.045 0 .088.006.133.008C19.056 6.305 15.768 4.09 12 4.09c-3.153 0-5.82 1.663-6.725 4.046l4.982 13.66C10.79 21.93 11.385 22 12 22c5.523 0 10-4.477 10-10 0-.69-.07-1.364-.2-2.015-.75.053-1.238.015-1.238.015zm-7.616 9.843l-3.328-9.985h1.728l2.25 6.945 2.115-6.945h1.677l-3.376 9.948c-.347.025-.699.037-1.066.037z"/>
                                </svg>
                                <svg v-else-if="installed.type === 'laravel'" class="w-6 h-6 text-rose-600" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                                </svg>
                                <div v-else class="font-bold text-blue-600 text-base">
                                    {{ installed.app_name.charAt(0) }}
                                </div>
                            </div>

                            <div>
                                <div class="flex items-center gap-2">
                                    <h4 class="font-bold text-slate-900 text-sm">{{ installed.site_title }}</h4>
                                    <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-blue-50 text-blue-700 border border-blue-200">
                                        {{ installed.app_name }} v{{ installed.version }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-3 text-xs text-slate-500 font-mono mt-0.5">
                                    <a :href="installed.url" target="_blank" class="text-blue-600 hover:underline flex items-center gap-1 font-sans">
                                        {{ installed.url }}
                                        <ArrowTopRightOnSquareIcon class="w-3.5 h-3.5" />
                                    </a>
                                    <span>•</span>
                                    <span>DB: <strong class="text-slate-700">{{ installed.db_name }}</strong></span>
                                </div>
                            </div>
                        </div>

                        <!-- Actions (Open Site & 1-Click Uninstall) -->
                        <div class="flex items-center gap-2">
                            <a 
                                :href="installed.url" 
                                target="_blank"
                                class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-[3px] text-xs font-bold shadow-2xs flex items-center gap-1.5 transition"
                            >
                                <GlobeAltIcon class="w-3.5 h-3.5 text-blue-600" />
                                <span>Visit Site</span>
                            </a>

                            <button 
                                @click="openUninstallModal(installed)"
                                class="px-3 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-[3px] text-xs font-bold shadow-2xs flex items-center gap-1.5 transition cursor-pointer"
                            >
                                <TrashIcon class="w-3.5 h-3.5" />
                                <span>Uninstall</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. Top Analytics & Stack Compatibility Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                
                <!-- Card 1: Available Applications -->
                <div class="bg-white rounded-lg border border-slate-200 p-4 shadow-2xs space-y-2.5">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                            <RocketLaunchIcon class="w-4 h-4 text-blue-600" />
                            <span>1-Click App Library</span>
                        </span>
                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-blue-50 text-blue-700 border border-blue-200 uppercase font-mono">
                            45+ READY
                        </span>
                    </div>

                    <div>
                        <div class="flex items-baseline justify-between">
                            <span class="text-2xl font-black text-slate-900 font-mono">Instant Deploy</span>
                            <span class="text-xs text-slate-500 font-mono">Zero Config</span>
                        </div>
                        <div class="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden mt-2">
                            <div class="bg-blue-600 h-full rounded-full w-full"></div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between text-[11px] text-slate-500 pt-1 border-t border-slate-100">
                        <span>Database Provisioning:</span>
                        <strong class="text-slate-700">Auto MySQL & Schema</strong>
                    </div>
                </div>

                <!-- Card 2: Multi-PHP & Runtime Engine -->
                <div class="bg-white rounded-lg border border-slate-200 p-4 shadow-2xs space-y-2.5">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                            <CpuChipIcon class="w-4 h-4 text-emerald-600" />
                            <span>Runtime Compatibility</span>
                        </span>
                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-200 uppercase font-mono">
                            OPTIMIZED
                        </span>
                    </div>

                    <div>
                        <div class="flex items-baseline justify-between">
                            <span class="text-2xl font-black text-emerald-600 font-mono">PHP 8.1 - 8.4</span>
                            <span class="text-xs text-slate-500 font-mono">Node 20 LTS</span>
                        </div>
                        <div class="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden mt-2">
                            <div class="bg-emerald-500 h-full rounded-full w-full"></div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between text-[11px] text-slate-500 pt-1 border-t border-slate-100">
                        <span>Process Manager:</span>
                        <strong class="text-emerald-700 font-bold">FastCGI + PM2 Supervisor</strong>
                    </div>
                </div>

                <!-- Card 3: Automated SSL & Security -->
                <div class="bg-white rounded-lg border border-slate-200 p-4 shadow-2xs space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                            <ShieldCheckIcon class="w-4 h-4 text-purple-600" />
                            <span>Auto-SSL & WAF</span>
                        </span>
                        <span class="text-[10px] font-bold text-slate-500 uppercase font-mono">
                            AUTO-ENCRYPT
                        </span>
                    </div>

                    <div class="space-y-1 text-xs font-mono">
                        <div class="flex items-center justify-between p-1.5 bg-slate-50 rounded border border-slate-200/70">
                            <span class="text-slate-500 text-[11px]">SSL Issuer:</span>
                            <span class="font-bold text-purple-700">Let's Encrypt Wildcard</span>
                        </div>

                        <div class="flex items-center justify-between p-1.5 bg-slate-50 rounded border border-slate-200/70">
                            <span class="text-slate-500 text-[11px]">Database Sandbox:</span>
                            <span class="font-bold text-emerald-700">Isolated MySQL User</span>
                        </div>
                    </div>
                </div>

            </div>

            <!-- 4. Category Filter Tabs & Live Search Bar -->
            <div class="bg-white rounded-lg border border-slate-200 p-3 shadow-2xs flex flex-col md:flex-row md:items-center justify-between gap-3">
                <!-- Categories Pills -->
                <div class="flex items-center gap-1.5 overflow-x-auto pb-1 md:pb-0">
                    <button 
                        v-for="cat in categories" 
                        :key="cat"
                        @click="selectedCategory = cat"
                        class="px-3 py-1.5 rounded-[3px] text-xs font-bold transition whitespace-nowrap cursor-pointer"
                        :class="selectedCategory === cat ? 'bg-blue-600 text-white shadow-2xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 hover:text-slate-900'"
                    >
                        {{ cat }}
                    </button>
                </div>

                <!-- Live Search Bar -->
                <div class="relative w-full md:w-64">
                    <MagnifyingGlassIcon class="w-4 h-4 text-slate-400 absolute left-2.5 top-2.5 pointer-events-none" />
                    <input 
                        type="text" 
                        v-model="searchQuery" 
                        placeholder="Search applications..." 
                        class="w-full text-xs rounded-[3px] border-slate-300 pl-8 pr-3 py-1.5 bg-slate-50 text-slate-900 placeholder:text-slate-400 focus:bg-white focus:ring-blue-500 focus:border-blue-500"
                    />
                </div>
            </div>

            <!-- 5. Applications Grid (Hero Cards - Crisp hostinger/cpanel style) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                
                <div 
                    v-for="app in filteredApps" 
                    :key="app.id"
                    class="bg-white rounded-lg border border-slate-200 p-6 flex flex-col items-center text-center shadow-2xs hover:shadow-md hover:border-blue-300 transition-all duration-200 relative group"
                >
                    <!-- Most Popular Badge -->
                    <span 
                        v-if="app.is_popular"
                        class="absolute -top-2.5 left-1/2 -translate-x-1/2 px-2.5 py-0.5 rounded-[3px] text-[10px] font-bold uppercase tracking-wider bg-slate-800 text-white shadow-2xs font-mono"
                    >
                        Most Popular
                    </span>

                    <!-- App Icon / Logo -->
                    <div class="w-16 h-16 rounded-lg bg-slate-50 border border-slate-100 flex items-center justify-center mb-3 mt-1 shadow-2xs group-hover:scale-105 transition">
                        
                        <!-- WordPress SVG -->
                        <svg v-if="app.id === 'wordpress'" class="w-10 h-10 text-blue-600" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 2C6.477 2 2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878L5.275 8.136C6.18 5.753 8.847 4.09 12 4.09c1.905 0 3.633.626 5.027 1.688L12 2zm8.562 10c0-1.282-.46-2.172-.857-2.868-.526-.856-1.02-1.577-1.02-2.43 0-.952.723-1.84 1.74-1.84.045 0 .088.006.133.008C19.056 6.305 15.768 4.09 12 4.09c-3.153 0-5.82 1.663-6.725 4.046l4.982 13.66C10.79 21.93 11.385 22 12 22c5.523 0 10-4.477 10-10 0-.69-.07-1.364-.2-2.015-.75.053-1.238.015-1.238.015zm-7.616 9.843l-3.328-9.985h1.728l2.25 6.945 2.115-6.945h1.677l-3.376 9.948c-.347.025-.699.037-1.066.037z"/>
                        </svg>

                        <!-- WooCommerce SVG -->
                        <div v-else-if="app.id === 'woocommerce'" class="text-purple-600 font-black text-xl font-mono tracking-tighter">
                            woo
                        </div>

                        <!-- Laravel SVG -->
                        <svg v-else-if="app.id === 'laravel'" class="w-10 h-10 text-rose-600" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                        </svg>

                        <!-- Joomla SVG -->
                        <div v-else-if="app.id === 'joomla'" class="w-10 h-10 rounded-md bg-amber-500 text-white flex items-center justify-center font-black text-xl">
                            J
                        </div>

                        <!-- Next.js SVG -->
                        <div v-else-if="app.id === 'nextjs'" class="w-10 h-10 rounded-md bg-slate-900 text-white flex items-center justify-center font-black text-sm font-mono">
                            N
                        </div>

                        <!-- Other generic -->
                        <div v-else class="w-10 h-10 rounded-md bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-lg">
                            {{ app.name.charAt(0) }}
                        </div>

                    </div>

                    <!-- App Title & Tagline -->
                    <h3 class="font-black text-base text-slate-900 mb-1.5">{{ app.name }}</h3>
                    <p class="text-xs text-slate-500 leading-relaxed mb-4 flex-1">
                        {{ app.tagline }}
                    </p>

                    <!-- Tech specs pill -->
                    <div class="flex items-center gap-1.5 mb-4 text-[10.5px] font-mono text-slate-500">
                        <span class="px-1.5 py-0.5 rounded bg-slate-100 border border-slate-200">v{{ app.version }}</span>
                        <span class="px-1.5 py-0.5 rounded bg-slate-100 border border-slate-200">{{ app.php_recommended }}</span>
                    </div>

                    <!-- Select Button -->
                    <button 
                        @click="openInstallModal(app)"
                        class="w-full py-2 bg-white hover:bg-blue-600 text-blue-600 hover:text-white font-bold rounded-[3px] text-xs border border-blue-600 hover:border-blue-600 shadow-2xs transition-all duration-150 cursor-pointer"
                    >
                        Select
                    </button>
                </div>

                <!-- Generic 'Other Applications' Card -->
                <div class="bg-white rounded-lg border border-slate-200 p-6 flex flex-col items-center text-center shadow-2xs hover:shadow-md hover:border-blue-300 transition-all duration-200">
                    <div class="w-16 h-16 rounded-lg bg-slate-50 border border-slate-100 flex items-center justify-center mb-3 mt-1 shadow-2xs text-slate-400">
                        <EllipsisHorizontalIcon class="w-8 h-8 stroke-[2.5]" />
                    </div>

                    <h3 class="font-black text-base text-slate-900 mb-1.5">Other CMS / App</h3>
                    <p class="text-xs text-slate-500 leading-relaxed mb-4 flex-1">
                        Choose from 40+ custom scripts, Node.js runtimes, or Python frameworks.
                    </p>

                    <div class="flex items-center gap-1.5 mb-4 text-[10.5px] font-mono text-slate-500">
                        <span class="px-1.5 py-0.5 rounded bg-slate-100 border border-slate-200">Custom Git</span>
                        <span class="px-1.5 py-0.5 rounded bg-slate-100 border border-slate-200">Composer</span>
                    </div>

                    <button 
                        @click="openInstallModal(apps[0])"
                        class="w-full py-2 bg-white hover:bg-slate-900 text-slate-800 hover:text-white font-bold rounded-[3px] text-xs border border-slate-300 hover:border-slate-900 shadow-2xs transition-all duration-150 cursor-pointer"
                    >
                        Select
                    </button>
                </div>

            </div>

        </div>

        <!-- 1-Click Install Application Modal -->
        <div v-if="showModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-2xs flex items-center justify-center p-4 z-50">
            <div class="bg-white rounded-lg max-w-lg w-full p-5 shadow-xl space-y-4 border border-slate-200 max-h-[90vh] overflow-y-auto">
                
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-[4px] bg-blue-600 text-white flex items-center justify-center font-bold text-sm shadow-2xs">
                            {{ selectedApp?.name.charAt(0) }}
                        </div>
                        <div>
                            <h3 class="font-bold text-slate-900 text-sm">Install {{ selectedApp?.name }}</h3>
                            <span class="text-[11px] text-slate-500 font-mono">v{{ selectedApp?.version }} • {{ selectedApp?.category }}</span>
                        </div>
                    </div>
                    <button @click="showModal = false" class="text-slate-400 hover:text-slate-600 p-1 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitInstall" class="space-y-3.5 text-xs">
                    
                    <!-- Domain & Path -->
                    <div class="space-y-1.5">
                        <label class="block font-bold text-slate-700">Installation URL & Directory</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <select 
                                v-model="form.domain"
                                required
                                class="text-xs font-bold rounded-[3px] border-slate-300 bg-white text-slate-900 py-2 px-2.5 focus:ring-blue-500 focus:border-blue-500"
                            >
                                <option v-for="site in websites" :key="site.id" :value="site.domain">
                                    https://{{ site.domain }}
                                </option>
                            </select>

                            <div class="flex rounded-md shadow-2xs">
                                <span class="inline-flex items-center px-2.5 rounded-l-[3px] border border-r-0 border-slate-300 bg-slate-100 text-slate-500 font-mono text-xs">
                                    /
                                </span>
                                <input 
                                    type="text" 
                                    v-model="form.install_path"
                                    placeholder="e.g. store, blog"
                                    class="flex-1 text-xs font-mono rounded-r-[3px] border-slate-300 bg-white text-slate-900 py-2 px-2.5 focus:ring-blue-500 focus:border-blue-500"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Site Title -->
                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Site Title <span class="text-rose-500">*</span></label>
                        <input 
                            type="text" 
                            v-model="form.site_title"
                            required
                            placeholder="e.g. My Online Store"
                            class="w-full text-xs rounded-[3px] border-slate-300 bg-white text-slate-900 py-2 px-2.5 focus:ring-blue-500 focus:border-blue-500"
                        />
                    </div>

                    <!-- Admin Account -->
                    <div class="space-y-2.5 pt-1 border-t border-slate-100">
                        <span class="block font-bold uppercase tracking-wider text-[10.5px] text-slate-500">Administrator Credentials</span>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                            <div class="space-y-1">
                                <label class="block font-bold text-slate-700">Admin Username</label>
                                <input 
                                    type="text" 
                                    v-model="form.admin_username"
                                    required
                                    class="w-full text-xs font-mono font-bold rounded-[3px] border-slate-300 bg-white text-slate-900 py-2 px-2.5"
                                />
                            </div>

                            <div class="space-y-1">
                                <label class="block font-bold text-slate-700">Admin Email</label>
                                <input 
                                    type="email" 
                                    v-model="form.admin_email"
                                    required
                                    class="w-full text-xs font-mono rounded-[3px] border-slate-300 bg-white text-slate-900 py-2 px-2.5"
                                />
                            </div>
                        </div>

                        <div class="space-y-1">
                            <div class="flex items-center justify-between">
                                <label class="font-bold text-slate-700">Admin Password</label>
                                <button 
                                    type="button" 
                                    @click="generateStrongPassword" 
                                    class="text-[11px] font-bold text-blue-600 hover:underline cursor-pointer"
                                >
                                    Generate Strong
                                </button>
                            </div>
                            <input 
                                type="text" 
                                v-model="form.admin_password"
                                required
                                class="w-full text-xs font-mono font-bold rounded-[3px] border-slate-300 bg-slate-50 text-slate-900 py-2 px-2.5 focus:bg-white"
                            />
                        </div>
                    </div>

                    <!-- Automated DB & SSL Note -->
                    <div class="p-3 bg-blue-50/70 border border-blue-200 rounded space-y-1 text-[11px] text-blue-900">
                        <div class="flex items-center gap-1.5 font-bold">
                            <CircleStackIcon class="w-4 h-4 text-blue-600 shrink-0" />
                            <span>Automated MySQL Database & SSL Provisioning</span>
                        </div>
                        <p class="text-blue-700 text-[10.5px]">
                            A dedicated database and Let's Encrypt SSL certificate will be automatically configured for {{ selectedApp?.name }}.
                        </p>
                    </div>

                    <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                        <button 
                            type="button" 
                            @click="showModal = false" 
                            class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-[3px] text-xs font-bold cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button 
                            type="submit" 
                            :disabled="form.processing"
                            class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold shadow-2xs transition cursor-pointer disabled:opacity-50"
                        >
                            {{ form.processing ? 'Deploying Application...' : `Install ${selectedApp?.name}` }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 1-Click Uninstall Modal Dialog -->
        <div v-if="showUninstallModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-2xs flex items-center justify-center p-4 z-50">
            <div class="bg-white rounded-lg max-w-md w-full p-5 shadow-xl space-y-4 border border-slate-200">
                
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div class="flex items-center gap-2 text-rose-600">
                        <ExclamationTriangleIcon class="w-5 h-5" />
                        <h3 class="font-bold text-slate-900 text-sm">Uninstall {{ uninstallTarget?.site_title }}</h3>
                    </div>
                    <button @click="showUninstallModal = false" class="text-slate-400 hover:text-slate-600 p-1 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitUninstall" class="space-y-4 text-xs">
                    
                    <p class="text-slate-600 leading-relaxed">
                        Are you sure you want to uninstall this application from <strong class="text-slate-900">{{ uninstallTarget?.domain }}{{ uninstallTarget?.install_path }}</strong>?
                    </p>

                    <!-- Cleanup Checkboxes -->
                    <div class="space-y-2.5 p-3 bg-rose-50/50 border border-rose-200 rounded">
                        <label class="flex items-start gap-2 cursor-pointer">
                            <input 
                                type="checkbox" 
                                v-model="uninstallForm.delete_files"
                                class="rounded border-rose-300 text-rose-600 focus:ring-rose-500 mt-0.5"
                            />
                            <div class="text-slate-700">
                                <span class="font-bold block">Delete all application files & directories</span>
                                <span class="text-[11px] text-slate-500">Completely clears document root and restores clean default index.</span>
                            </div>
                        </label>

                        <label v-if="uninstallTarget?.db_name" class="flex items-start gap-2 cursor-pointer">
                            <input 
                                type="checkbox" 
                                v-model="uninstallForm.delete_database"
                                class="rounded border-rose-300 text-rose-600 focus:ring-rose-500 mt-0.5"
                            />
                            <div class="text-slate-700">
                                <span class="font-bold block">Drop MySQL Database (<code>{{ uninstallTarget?.db_name }}</code>)</span>
                                <span class="text-[11px] text-slate-500">Deletes database and all tables permanently.</span>
                            </div>
                        </label>
                    </div>

                    <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                        <button 
                            type="button" 
                            @click="showUninstallModal = false" 
                            class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-[3px] text-xs font-bold cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button 
                            type="submit" 
                            :disabled="uninstallForm.processing"
                            class="px-4 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-[3px] text-xs font-bold shadow-2xs transition cursor-pointer disabled:opacity-50 flex items-center gap-1.5"
                        >
                            <TrashIcon class="w-3.5 h-3.5" />
                            <span>{{ uninstallForm.processing ? 'Uninstalling...' : 'Confirm & Uninstall' }}</span>
                        </button>
                    </div>

                </form>
            </div>
        </div>

    </AuthenticatedLayout>
</template>
