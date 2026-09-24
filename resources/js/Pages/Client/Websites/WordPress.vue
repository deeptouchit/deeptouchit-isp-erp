<script setup>
import { ref, computed } from 'vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import { 
    PlusIcon, 
    SparklesIcon, 
    ArrowTopRightOnSquareIcon,
    TrashIcon,
    ArrowPathIcon,
    WrenchScrewdriverIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon,
    ShieldCheckIcon,
    KeyIcon,
    CpuChipIcon,
    CircleStackIcon,
    GlobeAltIcon,
    XMarkIcon,
    BoltIcon,
    RocketLaunchIcon,
    AdjustmentsHorizontalIcon,
    FolderIcon
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
    installations: {
        type: Array,
        default: () => []
    },
    availableVersions: {
        type: Array,
        default: () => [
            { version: '6.7.1', label: 'WordPress 6.7.1 (Latest Stable - Recommended)', is_latest: true },
            { version: '6.6.2', label: 'WordPress 6.6.2 (Stable)', is_latest: false },
            { version: '6.5.5', label: 'WordPress 6.5.5 (LTS)', is_latest: false }
        ]
    },
    stats: {
        type: Object,
        default: () => ({
            total_installations: 0,
            active_instances: 0,
            latest_version: 'WordPress 6.7.1',
            auto_update_active: 0,
            object_cache: 'Redis Active'
        })
    }
})

// 1. Install Modal State
const showInstallModal = ref(false)
const showAdvancedDb = ref(false)
const installForm = useForm({
    subscription_id: props.subscription?.id || (props.subscriptions[0]?.id ?? ''),
    domain: props.websites[0]?.domain || props.subscription?.domain || '',
    install_path: '/',
    site_title: 'My WordPress Site',
    admin_username: 'admin',
    admin_password: '',
    admin_email: 'admin@' + (props.websites[0]?.domain || props.subscription?.domain || 'example.com'),
    version: props.availableVersions[0]?.version || '6.7.1',
    auto_update_core: true,
    auto_update_plugins: true,
    auto_ssl: true,
    db_name: '',
    db_user: '',
    db_prefix: 'wp_',
})

const generateStrongPassword = () => {
    const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#$%^&*'
    let pass = ''
    for (let i = 0; i < 16; i++) {
        pass += chars.charAt(Math.floor(Math.random() * chars.length))
    }
    installForm.admin_password = pass
}

// Auto generate password on initial modal open if empty
const openInstallModal = () => {
    if (!installForm.admin_password) {
        generateStrongPassword()
    }
    showInstallModal.value = true
}

const submitInstall = () => {
    installForm.post(route('website.wordpress.install'), {
        preserveScroll: true,
        onSuccess: () => {
            showInstallModal.value = false
        }
    })
}

// 2. Toggle Maintenance Mode
const maintenanceForm = useForm({})
const toggleMaintenance = (wp) => {
    maintenanceForm.post(route('website.wordpress.maintenance', wp.id), {
        preserveScroll: true
    })
}

// 3. Toggle Auto-Updates
const autoUpdateForm = useForm({})
const toggleAutoUpdate = (wp) => {
    autoUpdateForm.post(route('website.wordpress.auto-update', wp.id), {
        preserveScroll: true
    })
}

// 4. Delete Modal State
const showDeleteModal = ref(false)
const wpToDelete = ref(null)
const deleteForm = useForm({})

const confirmDelete = (wp) => {
    wpToDelete.value = wp
    showDeleteModal.value = true
}

const submitDelete = () => {
    if (!wpToDelete.value) return
    deleteForm.delete(route('website.wordpress.destroy', wpToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false
            wpToDelete.value = null
        }
    })
}
</script>

<template>
    <Head title="1-Click WordPress Installer & Toolkit - DeepTouch Host" />

    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto space-y-4">
            
            <!-- 1. Page Header with Breadcrumbs & Actions -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Website Tools', href: '#' },
                    { label: 'WordPress Manager' }
                ]"
                :show-refresh="true"
                @refreshed="router.reload({ preserveScroll: true })"
            >
                <template #actions>
                    <button 
                        @click="openInstallModal"
                        class="px-2.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold flex items-center gap-1.5 shadow-2xs transition cursor-pointer"
                    >
                        <PlusIcon class="w-3.5 h-3.5 stroke-[2.5]" />
                        <span>Install WordPress</span>
                    </button>
                </template>
            </PageHeader>

            <!-- 2. Top Analytics & Status Grid (3 Cards) -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                
                <!-- Card 1: Active Installations -->
                <div class="bg-white rounded-lg border border-slate-200 p-4 shadow-2xs space-y-2.5">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                            <SparklesIcon class="w-4 h-4 text-blue-600" />
                            <span>WordPress Instances</span>
                        </span>
                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-blue-50 text-blue-700 border border-blue-200 uppercase font-mono">
                            {{ stats.latest_version }}
                        </span>
                    </div>

                    <div>
                        <div class="flex items-baseline justify-between">
                            <span class="text-2xl font-black text-slate-900 font-mono">{{ stats.active_instances }} Active</span>
                            <span class="text-xs text-slate-500 font-mono">{{ stats.total_installations }} Total Instances</span>
                        </div>
                        <div class="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden mt-2">
                            <div class="bg-blue-600 h-full rounded-full w-full"></div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between text-[11px] text-slate-500 pt-1 border-t border-slate-100">
                        <span>Stack Optimization:</span>
                        <strong class="text-slate-700">PHP 8.2 FPM + OPcache</strong>
                    </div>
                </div>

                <!-- Card 2: Auto-Update Engine -->
                <div class="bg-white rounded-lg border border-slate-200 p-4 shadow-2xs space-y-2.5">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                            <ShieldCheckIcon class="w-4 h-4 text-emerald-600" />
                            <span>Security & Auto-Updates</span>
                        </span>
                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-200 uppercase font-mono">
                            PROTECTED
                        </span>
                    </div>

                    <div>
                        <div class="flex items-baseline justify-between">
                            <span class="text-2xl font-black text-emerald-600 font-mono">Auto-Patched</span>
                            <span class="text-xs text-slate-500 font-mono">{{ stats.auto_update_active }} Enabled</span>
                        </div>
                        <div class="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden mt-2">
                            <div class="bg-emerald-500 h-full rounded-full w-full"></div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between text-[11px] text-slate-500 pt-1 border-t border-slate-100">
                        <span>Core Vulnerability Shield:</span>
                        <strong class="text-emerald-700 font-bold">Zero-day Auto Isolation</strong>
                    </div>
                </div>

                <!-- Card 3: Cache & Database Acceleration -->
                <div class="bg-white rounded-lg border border-slate-200 p-4 shadow-2xs space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                            <BoltIcon class="w-4 h-4 text-purple-600" />
                            <span>Speed & Object Cache</span>
                        </span>
                        <span class="text-[10px] font-bold text-slate-500 uppercase font-mono">
                            REDIS READY
                        </span>
                    </div>

                    <div class="space-y-1 text-xs font-mono">
                        <div class="flex items-center justify-between p-1.5 bg-slate-50 rounded border border-slate-200/70">
                            <span class="text-slate-500 text-[11px]">Object Caching:</span>
                            <span class="font-bold text-purple-700">Redis In-Memory</span>
                        </div>

                        <div class="flex items-center justify-between p-1.5 bg-slate-50 rounded border border-slate-200/70">
                            <span class="text-slate-500 text-[11px]">Static Microcache:</span>
                            <span class="font-bold text-emerald-700">Nginx FastCGI Edge</span>
                        </div>
                    </div>
                </div>

            </div>

            <!-- 3. Empty State or Installations Grid -->
            <div v-if="installations.length === 0" class="bg-white rounded-lg border border-slate-200 p-10 text-center shadow-2xs">
                <div class="w-12 h-12 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center mx-auto mb-3">
                    <SparklesIcon class="w-6 h-6" />
                </div>
                <h3 class="text-sm font-bold text-slate-900 mb-1">No WordPress Websites Installed</h3>
                <p class="text-xs text-slate-500 max-w-sm mx-auto mb-4">
                    Deploy a brand new, lightning-fast WordPress website in 30 seconds with automated MySQL database provisioning and SSL security.
                </p>
                <button 
                    @click="openInstallModal"
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold shadow-2xs inline-flex items-center gap-1.5 cursor-pointer"
                >
                    <PlusIcon class="w-3.5 h-3.5 stroke-[2.5]" />
                    <span>Install Your First WordPress Site</span>
                </button>
            </div>

            <!-- Installations Grid -->
            <div v-else class="space-y-4">
                <div 
                    v-for="wp in installations" 
                    :key="wp.id"
                    class="bg-white rounded-lg border border-slate-200 shadow-2xs overflow-hidden transition hover:border-slate-300"
                >
                    <!-- Card Top Header -->
                    <div class="px-5 py-3.5 bg-slate-50/70 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2.5">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-[4px] bg-blue-600 text-white flex items-center justify-center font-bold text-base shadow-2xs">
                                W
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h3 class="font-bold text-sm text-slate-900">{{ wp.site_title }}</h3>
                                    <span 
                                        class="px-2 py-0.5 rounded-[3px] text-[10.5px] font-bold uppercase border font-mono"
                                        :class="wp.maintenance_mode ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-emerald-50 text-emerald-700 border-emerald-200'"
                                    >
                                        {{ wp.maintenance_mode ? 'MAINTENANCE MODE' : 'LIVE ONLINE' }}
                                    </span>
                                </div>
                                <a 
                                    :href="wp.full_url" 
                                    target="_blank" 
                                    class="text-xs text-blue-600 hover:underline flex items-center gap-1 font-mono font-medium"
                                >
                                    <span>{{ wp.full_url }}</span>
                                    <ArrowTopRightOnSquareIcon class="w-3 h-3 text-slate-400" />
                                </a>
                            </div>
                        </div>

                        <!-- 1-Click Action Buttons -->
                        <div class="flex items-center gap-2 self-end sm:self-center">
                            <!-- Log in to WP-Admin -->
                            <a 
                                :href="wp.admin_url" 
                                target="_blank"
                                class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-[3px] text-xs flex items-center gap-1.5 shadow-2xs transition"
                            >
                                <RocketLaunchIcon class="w-3.5 h-3.5" />
                                <span>Log in to WP-Admin</span>
                            </a>

                            <!-- Maintenance Toggle -->
                            <button 
                                @click="toggleMaintenance(wp)"
                                class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer"
                                :title="wp.maintenance_mode ? 'Disable Maintenance Mode' : 'Enable Maintenance Mode'"
                            >
                                <WrenchScrewdriverIcon class="w-3.5 h-3.5 text-slate-500" />
                                <span class="hidden sm:inline">{{ wp.maintenance_mode ? 'Exit Maintenance' : 'Maintenance' }}</span>
                            </button>

                            <!-- Delete -->
                            <button 
                                @click="confirmDelete(wp)"
                                class="p-1.5 rounded hover:bg-rose-50 text-rose-500 hover:text-rose-700 transition cursor-pointer"
                                title="Uninstall WordPress"
                            >
                                <TrashIcon class="w-4 h-4" />
                            </button>
                        </div>
                    </div>

                    <!-- Card Body Details Grid -->
                    <div class="p-5 grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs font-mono">
                        <div class="p-2.5 bg-slate-50 rounded border border-slate-200/70 space-y-0.5">
                            <span class="text-slate-400 text-[10px] font-sans font-bold uppercase block">WordPress Core</span>
                            <span class="font-bold text-slate-900">v{{ wp.version }}</span>
                        </div>

                        <div class="p-2.5 bg-slate-50 rounded border border-slate-200/70 space-y-0.5">
                            <span class="text-slate-400 text-[10px] font-sans font-bold uppercase block">Database Name</span>
                            <span class="font-bold text-slate-900 truncate block">{{ wp.db_name }}</span>
                        </div>

                        <div class="p-2.5 bg-slate-50 rounded border border-slate-200/70 space-y-0.5">
                            <span class="text-slate-400 text-[10px] font-sans font-bold uppercase block">Admin Username</span>
                            <span class="font-bold text-slate-900 truncate block">{{ wp.admin_username }}</span>
                        </div>

                        <div class="p-2.5 bg-slate-50 rounded border border-slate-200/70 space-y-0.5">
                            <span class="text-slate-400 text-[10px] font-sans font-bold uppercase block">PHP Engine & SSL</span>
                            <span class="font-bold text-emerald-700">PHP {{ wp.php_version }} + SSL</span>
                        </div>
                    </div>

                    <!-- Card Footer Quick Switches -->
                    <div class="px-5 py-2.5 bg-slate-50/40 border-t border-slate-100 flex items-center justify-between text-xs font-sans">
                        <div class="flex items-center gap-4">
                            <label class="inline-flex items-center gap-1.5 cursor-pointer font-medium text-slate-700">
                                <input 
                                    type="checkbox" 
                                    :checked="wp.auto_update_core" 
                                    @change="toggleAutoUpdate(wp)"
                                    class="w-3.5 h-3.5 rounded text-blue-600 focus:ring-blue-500 border-slate-300"
                                />
                                <span>Core Auto-Updates</span>
                            </label>

                            <span class="text-slate-300">|</span>

                            <span class="text-slate-500 text-[11px]">Installed on: <strong class="text-slate-700">{{ wp.created_at }}</strong></span>
                        </div>

                        <a 
                            :href="wp.full_url" 
                            target="_blank" 
                            class="text-blue-600 hover:underline font-bold text-xs flex items-center gap-1"
                        >
                            <span>Visit Website</span>
                            <ArrowTopRightOnSquareIcon class="w-3.5 h-3.5" />
                        </a>
                    </div>
                </div>
            </div>

        </div>

        <!-- 1. Install WordPress Modal -->
        <div v-if="showInstallModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-2xs flex items-center justify-center p-4 z-50">
            <div class="bg-white rounded-lg max-w-xl w-full p-5 shadow-xl space-y-4 border border-slate-200 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-blue-600 text-white flex items-center justify-center font-bold text-xs">
                            W
                        </div>
                        <h3 class="font-bold text-slate-900 text-sm">Install WordPress Website</h3>
                    </div>
                    <button @click="showInstallModal = false" class="text-slate-400 hover:text-slate-600 p-1 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitInstall" class="space-y-3.5 text-xs">
                    
                    <!-- Section 1: Domain & Path -->
                    <div class="space-y-2">
                        <label class="block font-bold text-slate-700">Choose Installation Domain & Path</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <select 
                                v-model="installForm.domain"
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
                                    v-model="installForm.install_path"
                                    placeholder="Leave empty for root"
                                    class="flex-1 text-xs font-mono rounded-r-[3px] border-slate-300 bg-white text-slate-900 py-2 px-2.5 focus:ring-blue-500 focus:border-blue-500"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Section 2: Site Settings -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 pt-1 border-t border-slate-100">
                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Site Title <span class="text-rose-500">*</span></label>
                            <input 
                                type="text" 
                                v-model="installForm.site_title"
                                required
                                placeholder="e.g. SomitySoft Official"
                                class="w-full text-xs rounded-[3px] border-slate-300 bg-white text-slate-900 py-2 px-2.5 focus:ring-blue-500 focus:border-blue-500"
                            />
                        </div>

                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">WordPress Version</label>
                            <select 
                                v-model="installForm.version"
                                class="w-full text-xs font-bold rounded-[3px] border-slate-300 bg-white text-slate-900 py-2 px-2.5"
                            >
                                <option v-for="v in availableVersions" :key="v.version" :value="v.version">
                                    {{ v.label }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <!-- Section 3: Admin Account Details -->
                    <div class="space-y-2.5 pt-1 border-t border-slate-100">
                        <span class="block font-bold uppercase tracking-wider text-[10.5px] text-slate-500">Admin Login Credentials</span>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                            <div class="space-y-1">
                                <label class="block font-bold text-slate-700">Admin Username <span class="text-rose-500">*</span></label>
                                <input 
                                    type="text" 
                                    v-model="installForm.admin_username"
                                    required
                                    placeholder="admin"
                                    class="w-full text-xs font-mono font-bold rounded-[3px] border-slate-300 bg-white text-slate-900 py-2 px-2.5 focus:ring-blue-500 focus:border-blue-500"
                                />
                            </div>

                            <div class="space-y-1">
                                <label class="block font-bold text-slate-700">Admin Email <span class="text-rose-500">*</span></label>
                                <input 
                                    type="email" 
                                    v-model="installForm.admin_email"
                                    required
                                    placeholder="admin@somitysoft.com"
                                    class="w-full text-xs font-mono rounded-[3px] border-slate-300 bg-white text-slate-900 py-2 px-2.5 focus:ring-blue-500 focus:border-blue-500"
                                />
                            </div>
                        </div>

                        <div class="space-y-1">
                            <div class="flex items-center justify-between">
                                <label class="font-bold text-slate-700">Admin Password <span class="text-rose-500">*</span></label>
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
                                v-model="installForm.admin_password"
                                required
                                class="w-full text-xs font-mono font-bold rounded-[3px] border-slate-300 bg-slate-50 text-slate-900 py-2 px-2.5 focus:bg-white focus:ring-blue-500 focus:border-blue-500"
                            />
                        </div>
                    </div>

                    <!-- Section 4: Automated Database & Security Defaults -->
                    <div class="p-3 bg-slate-50 rounded border border-slate-200 space-y-2.5">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-slate-800 text-[11.5px] flex items-center gap-1.5">
                                <CircleStackIcon class="w-4 h-4 text-blue-600" />
                                <span>MySQL Database Configuration</span>
                            </span>
                            <button 
                                type="button" 
                                @click="showAdvancedDb = !showAdvancedDb"
                                class="text-[11px] font-bold text-blue-600 hover:underline cursor-pointer"
                            >
                                {{ showAdvancedDb ? 'Use Auto-Generated (Default)' : 'Customize DB Name & Prefix' }}
                            </button>
                        </div>

                        <!-- Auto-generated indicator -->
                        <div v-if="!showAdvancedDb" class="text-[11px] text-slate-500 bg-white p-2 rounded border border-slate-200/70">
                            ⚡ <strong>Auto-Provisioning:</strong> Database name, secure user & credentials will be automatically generated and linked to <code>wp-config.php</code>.
                        </div>

                        <!-- Custom DB inputs if expanded -->
                        <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-2 pt-1">
                            <div class="space-y-1">
                                <label class="block font-bold text-slate-700 text-[11px]">Database Name</label>
                                <input 
                                    type="text" 
                                    v-model="installForm.db_name"
                                    placeholder="e.g. somity_wp"
                                    class="w-full text-xs font-mono rounded-[3px] border-slate-300 bg-white text-slate-900 py-1.5 px-2.5"
                                />
                            </div>
                            <div class="space-y-1">
                                <label class="block font-bold text-slate-700 text-[11px]">Table Prefix</label>
                                <input 
                                    type="text" 
                                    v-model="installForm.db_prefix"
                                    placeholder="wp_"
                                    class="w-full text-xs font-mono rounded-[3px] border-slate-300 bg-white text-slate-900 py-1.5 px-2.5"
                                />
                            </div>
                        </div>

                        <div class="pt-1 space-y-1.5 border-t border-slate-200">
                            <label class="flex items-center gap-2 cursor-pointer font-bold text-slate-700">
                                <input type="checkbox" v-model="installForm.auto_ssl" class="rounded text-blue-600 focus:ring-blue-500 border-slate-300" />
                                <span>Auto-issue Let's Encrypt SSL & Enforce HTTPS</span>
                            </label>

                            <label class="flex items-center gap-2 cursor-pointer font-bold text-slate-700">
                                <input type="checkbox" v-model="installForm.auto_update_core" class="rounded text-blue-600 focus:ring-blue-500 border-slate-300" />
                                <span>Enable Automatic Minor & Security Core Updates</span>
                            </label>
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                        <button 
                            type="button" 
                            @click="showInstallModal = false" 
                            class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-[3px] text-xs font-bold cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button 
                            type="submit" 
                            :disabled="installForm.processing"
                            class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold shadow-2xs transition cursor-pointer disabled:opacity-50"
                        >
                            {{ installForm.processing ? 'Provisioning WordPress & Database...' : 'Install WordPress' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 2. Delete / Uninstall Confirmation Modal -->
        <div v-if="showDeleteModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-2xs flex items-center justify-center p-4 z-50">
            <div class="bg-white rounded-lg max-w-md w-full p-5 shadow-xl space-y-4 border border-slate-200">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div class="flex items-center gap-2">
                        <TrashIcon class="w-5 h-5 text-rose-600" />
                        <h3 class="font-bold text-slate-900 text-sm">Uninstall WordPress</h3>
                    </div>
                    <button @click="showDeleteModal = false" class="text-slate-400 hover:text-slate-600 p-1 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <div class="text-xs text-slate-600 space-y-2">
                    <p>
                        Are you sure you want to completely uninstall WordPress from <strong class="text-slate-900 font-mono">{{ wpToDelete?.full_url }}</strong>?
                    </p>
                    <p class="text-[11px] text-rose-600 font-medium">
                        This will remove WordPress instance configuration and its associated MySQL database (<code>{{ wpToDelete?.db_name }}</code>).
                    </p>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                    <button 
                        type="button" 
                        @click="showDeleteModal = false" 
                        class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-[3px] text-xs font-bold cursor-pointer"
                    >
                        Cancel
                    </button>
                    <button 
                        type="button"
                        @click="submitDelete"
                        :disabled="deleteForm.processing"
                        class="px-4 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-[3px] text-xs font-bold shadow-2xs transition cursor-pointer disabled:opacity-50"
                    >
                        {{ deleteForm.processing ? 'Uninstalling...' : 'Confirm Uninstall' }}
                    </button>
                </div>
            </div>
        </div>

    </AuthenticatedLayout>
</template>
