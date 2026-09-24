<script setup>
import { ref } from 'vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import { 
    GlobeAltIcon, 
    PlusIcon, 
    ShieldCheckIcon, 
    TrashIcon, 
    ArrowTopRightOnSquareIcon,
    FolderIcon,
    CpuChipIcon,
    CheckIcon,
    ClipboardDocumentIcon,
    ExclamationTriangleIcon,
    ServerIcon,
    ArrowUpCircleIcon,
    XMarkIcon,
    CircleStackIcon,
    CommandLineIcon,
    Cog6ToothIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    websites: {
        type: Array,
        default: () => []
    },
    subscriptions: {
        type: Array,
        default: () => []
    },
    quota: {
        type: Object,
        default: () => ({
            total_allowed: 1,
            total_used: 1,
            remaining: 0,
            can_add: false,
            usage_percentage: 100
        })
    },
    serverInfo: {
        type: Object,
        default: () => ({
            public_ip: '103.59.177.138',
            primary_ns: 'ns1.deeptouchit.com',
            secondary_ns: 'ns2.deeptouchit.com'
        })
    }
})

// Copy to clipboard helper
const copiedText = ref('')
const copyToClipboard = (text, label) => {
    navigator.clipboard.writeText(text)
    copiedText.value = label
    setTimeout(() => {
        copiedText.value = ''
    }, 2000)
}

// PHP Switcher Modal State
const showPhpModal = ref(false)
const selectedWebsite = ref(null)
const phpForm = useForm({
    php_version: '8.2'
})

const openPhpModal = (site) => {
    selectedWebsite.value = site
    phpForm.php_version = site.php_version || '8.2'
    showPhpModal.value = true
}

const submitPhpChange = () => {
    if (!selectedWebsite.value) return
    phpForm.post(route('websites.change-php', selectedWebsite.value.id), {
        onSuccess: () => {
            showPhpModal.value = false
        }
    })
}

// SSL Trigger
const sslForm = useForm({})
const triggerSsl = (site) => {
    sslForm.post(route('ssl.generate', site.id))
}

// Delete Modal State
const showDeleteModal = ref(false)
const siteToDelete = ref(null)
const deleteForm = useForm({})

const confirmDelete = (site) => {
    siteToDelete.value = site
    showDeleteModal.value = true
}

const submitDelete = () => {
    if (!siteToDelete.value) return
    deleteForm.delete(route('websites.destroy', siteToDelete.value.id), {
        onSuccess: () => {
            showDeleteModal.value = false
            siteToDelete.value = null
        }
    })
}
</script>

<template>
    <Head title="Hosted Websites & Domains - DeepTouch Cloud" />

    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto space-y-4">
            
            <!-- 1. Page Header with Breadcrumbs & Actions -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Domains', href: '#' },
                    { label: 'Hosted Websites' }
                ]"
                :show-refresh="true"
                @refreshed="router.reload({ preserveScroll: true })"
            >
                <template #actions>
                    <Link 
                        v-if="quota.can_add"
                        :href="route('websites.create')" 
                        class="px-2.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold flex items-center gap-1.5 shadow-2xs transition cursor-pointer"
                    >
                        <PlusIcon class="w-3.5 h-3.5 stroke-[2.5]" />
                        <span>Add Website</span>
                    </Link>

                    <Link 
                        v-else
                        :href="route('hosting.upgrade')" 
                        class="px-2.5 py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-200 rounded-[3px] text-xs font-bold flex items-center gap-1.5 shadow-2xs transition cursor-pointer"
                    >
                        <ArrowUpCircleIcon class="w-3.5 h-3.5 text-amber-600" />
                        <span>Upgrade Domain Limit</span>
                    </Link>
                </template>
            </PageHeader>

            <!-- 2. Top Analytics & Reference Cards Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                
                <!-- Card 1: Active Websites -->
                <div class="bg-white rounded-lg border border-slate-200 p-4 shadow-2xs space-y-2.5">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                            <GlobeAltIcon class="w-4 h-4 text-blue-600" />
                            <span>Hosted Websites</span>
                        </span>
                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-200 uppercase">
                            Nginx vHost
                        </span>
                    </div>

                    <div>
                        <div class="flex items-baseline justify-between">
                            <span class="text-2xl font-black text-slate-900 font-mono">{{ websites.length }}</span>
                            <span class="text-xs text-slate-500">Virtual Host(s)</span>
                        </div>
                        <div class="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden mt-2">
                            <div class="bg-blue-600 h-full rounded-full w-full"></div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between text-[11px] text-slate-500 pt-1 border-t border-slate-100">
                        <span>Web Server:</span>
                        <strong class="text-slate-700">Nginx + PHP-FPM</strong>
                    </div>
                </div>

                <!-- Card 2: Package Domain Quota -->
                <div class="bg-white rounded-lg border border-slate-200 p-4 shadow-2xs space-y-2.5">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                            <ServerIcon class="w-4 h-4 text-purple-600" />
                            <span>Domain Quota</span>
                        </span>
                        <span 
                            class="text-[10px] font-bold px-1.5 py-0.5 rounded uppercase border"
                            :class="quota.remaining === 0 ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-emerald-50 text-emerald-700 border-emerald-200'"
                        >
                            {{ quota.remaining === 0 ? 'Full' : `${quota.remaining} Free` }}
                        </span>
                    </div>

                    <div>
                        <div class="flex items-baseline justify-between">
                            <span class="text-2xl font-black text-slate-900 font-mono">{{ quota.total_used }} / {{ quota.total_allowed }}</span>
                            <span class="text-xs text-slate-500 font-mono">{{ quota.usage_percentage }}% Allocated</span>
                        </div>
                        <div class="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden mt-2">
                            <div 
                                class="h-full rounded-full transition-all duration-500"
                                :class="quota.usage_percentage >= 100 ? 'bg-amber-500' : 'bg-purple-600'"
                                :style="{ width: `${quota.usage_percentage}%` }"
                            ></div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between text-[11px] text-slate-500 pt-1 border-t border-slate-100">
                        <span>Available Slots:</span>
                        <strong class="text-slate-700 font-mono">{{ quota.remaining }} Domain(s)</strong>
                    </div>
                </div>

                <!-- Card 3: DNS & Nameservers Quick Copy -->
                <div class="bg-white rounded-lg border border-slate-200 p-4 shadow-2xs space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                            <CommandLineIcon class="w-4 h-4 text-slate-700" />
                            <span>DNS & Server Coordinates</span>
                        </span>
                        <span v-if="copiedText" class="text-[10px] text-emerald-600 font-bold animate-pulse">
                            ✓ Copied!
                        </span>
                    </div>

                    <div class="space-y-1 text-xs font-mono">
                        <div class="flex items-center justify-between p-1.5 bg-slate-50 rounded border border-slate-200/70">
                            <span class="text-slate-500 text-[11px]">Server IP:</span>
                            <button 
                                @click="copyToClipboard(serverInfo.public_ip, 'ip')"
                                class="font-bold text-slate-900 hover:text-blue-600 flex items-center gap-1 transition cursor-pointer"
                            >
                                <span>{{ serverInfo.public_ip }}</span>
                                <ClipboardDocumentIcon class="w-3 h-3 text-slate-400" />
                            </button>
                        </div>

                        <div class="flex items-center justify-between p-1.5 bg-slate-50 rounded border border-slate-200/70">
                            <span class="text-slate-500 text-[11px]">NS1:</span>
                            <button 
                                @click="copyToClipboard(serverInfo.primary_ns, 'ns1')"
                                class="font-bold text-slate-900 hover:text-blue-600 flex items-center gap-1 transition cursor-pointer"
                            >
                                <span>{{ serverInfo.primary_ns }}</span>
                                <ClipboardDocumentIcon class="w-3 h-3 text-slate-400" />
                            </button>
                        </div>

                        <div class="flex items-center justify-between p-1.5 bg-slate-50 rounded border border-slate-200/70">
                            <span class="text-slate-500 text-[11px]">NS2:</span>
                            <button 
                                @click="copyToClipboard(serverInfo.secondary_ns, 'ns2')"
                                class="font-bold text-slate-900 hover:text-blue-600 flex items-center gap-1 transition cursor-pointer"
                            >
                                <span>{{ serverInfo.secondary_ns }}</span>
                                <ClipboardDocumentIcon class="w-3 h-3 text-slate-400" />
                            </button>
                        </div>
                    </div>
                </div>

            </div>

            <!-- 3. Quota Limit Notification Banner (if limit reached) -->
            <div v-if="!quota.can_add" class="p-3.5 rounded-lg bg-amber-50/90 border border-amber-200 text-xs text-amber-900 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-2xs">
                <div class="flex items-center gap-2.5">
                    <ExclamationTriangleIcon class="w-4 h-4 text-amber-600 shrink-0" />
                    <div>
                        <span class="font-bold">Domain Limit Reached ({{ quota.total_used }}/{{ quota.total_allowed }} Allocated)</span>
                        <span class="text-amber-800 text-[11px] block sm:inline sm:ml-2">Upgrade your package to connect additional add-on domains.</span>
                    </div>
                </div>
                <Link 
                    :href="route('hosting.upgrade')" 
                    class="px-2.5 py-1 bg-amber-600 hover:bg-amber-700 text-white rounded-[3px] font-bold text-xs shrink-0 transition flex items-center gap-1 shadow-2xs"
                >
                    <ArrowUpCircleIcon class="w-3.5 h-3.5" />
                    <span>Upgrade Plan</span>
                </Link>
            </div>

            <!-- 4. Empty State -->
            <div v-if="websites.length === 0" class="bg-white rounded-lg border border-slate-200 p-10 text-center shadow-2xs">
                <div class="w-12 h-12 rounded-md bg-blue-50 text-blue-600 flex items-center justify-center mx-auto mb-3">
                    <GlobeAltIcon class="w-6 h-6" />
                </div>
                <h3 class="text-sm font-bold text-slate-900 mb-1">No Hosted Websites Found</h3>
                <p class="text-xs text-slate-500 max-w-sm mx-auto mb-4">
                    Deploy your virtual host with automated Nginx reverse proxy, PHP-FPM pool, and free SSL certificate.
                </p>
                <Link 
                    v-if="quota.can_add"
                    :href="route('websites.create')" 
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold shadow-2xs inline-flex items-center gap-1.5"
                >
                    <PlusIcon class="w-3.5 h-3.5" />
                    <span>Deploy Website Now</span>
                </Link>
            </div>

            <!-- 5. Websites Cards Grid -->
            <div v-else class="space-y-3">
                <div 
                    v-for="site in websites" 
                    :key="site.id" 
                    class="bg-white rounded-lg border border-slate-200 p-5 shadow-2xs space-y-4"
                >
                    <!-- Top Domain Header Row -->
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-100">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-md bg-blue-50 border border-blue-200/80 text-blue-600 flex items-center justify-center shrink-0">
                                <GlobeAltIcon class="w-5 h-5 stroke-[2.2]" />
                            </div>
                            <div>
                                <div class="flex items-center gap-2 flex-wrap">
                                    <a 
                                        :href="'http://' + site.domain" 
                                        target="_blank" 
                                        class="text-base font-bold text-slate-900 hover:text-blue-600 transition flex items-center gap-1 tracking-tight"
                                    >
                                        <span>{{ site.domain }}</span>
                                        <ArrowTopRightOnSquareIcon class="w-3.5 h-3.5 text-slate-400 hover:text-blue-600" />
                                    </a>
                                    <span 
                                        v-if="site.is_primary"
                                        class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200 uppercase"
                                    >
                                        Primary Domain
                                    </span>
                                </div>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    Plan: <strong class="text-slate-800">{{ site.subscription?.plan_name }}</strong> • Created: <span class="font-mono text-slate-600">{{ site.created_at }}</span>
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <span 
                                class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-[3px] border uppercase"
                                :class="site.status === 'active' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-amber-50 text-amber-700 border-amber-200'"
                            >
                                <span class="w-1.5 h-1.5 rounded-full" :class="site.status === 'active' ? 'bg-emerald-500 animate-pulse' : 'bg-amber-500'"></span>
                                <span>{{ site.status }}</span>
                            </span>
                        </div>
                    </div>

                    <!-- 3 Clean Key-Value Micro-Cards Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
                        
                        <!-- 1. Document Root & Files -->
                        <div class="p-3 bg-slate-50 rounded-md border border-slate-200/70 flex flex-col justify-between space-y-2">
                            <div>
                                <span class="text-[10px] uppercase font-bold text-slate-400 block">Document Root</span>
                                <span class="text-slate-800 font-mono text-[11px] truncate block font-bold mt-0.5">{{ site.document_root }}</span>
                            </div>
                            <div class="flex items-center justify-between pt-1 border-t border-slate-200/60">
                                <span class="text-[11px] text-slate-500">File Manager:</span>
                                <Link 
                                    :href="route('file.browse')" 
                                    class="px-2 py-0.5 bg-white hover:bg-slate-100 text-blue-600 rounded border border-slate-200 text-[10.5px] font-bold flex items-center gap-1 transition shadow-2xs"
                                >
                                    <FolderIcon class="w-3 h-3" />
                                    <span>Open Files</span>
                                </Link>
                            </div>
                        </div>

                        <!-- 2. PHP Runtime Engine -->
                        <div class="p-3 bg-slate-50 rounded-md border border-slate-200/70 flex flex-col justify-between space-y-2">
                            <div>
                                <span class="text-[10px] uppercase font-bold text-slate-400 block">PHP Runtime Engine</span>
                                <span class="text-blue-700 font-bold text-xs mt-0.5 block">PHP {{ site.php_version }} FPM</span>
                            </div>
                            <div class="flex items-center justify-between pt-1 border-t border-slate-200/60">
                                <span class="text-[11px] text-slate-500">Isolated Pool:</span>
                                <button 
                                    @click="openPhpModal(site)"
                                    class="px-2 py-0.5 bg-white hover:bg-slate-100 text-slate-700 rounded border border-slate-200 text-[10.5px] font-bold transition shadow-2xs cursor-pointer"
                                >
                                    Switch Version
                                </button>
                            </div>
                        </div>

                        <!-- 3. SSL Security Certificate -->
                        <div class="p-3 bg-slate-50 rounded-md border border-slate-200/70 flex flex-col justify-between space-y-2">
                            <div>
                                <span class="text-[10px] uppercase font-bold text-slate-400 block">SSL Security Certificate</span>
                                <span v-if="site.ssl_status === 'active'" class="text-emerald-700 font-bold text-xs flex items-center gap-1 mt-0.5">
                                    <ShieldCheckIcon class="w-3.5 h-3.5 text-emerald-600" />
                                    <span>Let's Encrypt (Active)</span>
                                </span>
                                <span v-else class="text-slate-500 text-xs font-medium mt-0.5 block">Not Issued</span>
                            </div>
                            <div class="flex items-center justify-between pt-1 border-t border-slate-200/60">
                                <span class="text-[11px] text-slate-500">Auto-Renewal:</span>
                                <button 
                                    v-if="site.ssl_status !== 'active'"
                                    @click="triggerSsl(site)"
                                    :disabled="sslForm.processing"
                                    class="px-2 py-0.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded text-[10.5px] font-bold transition shadow-2xs cursor-pointer disabled:opacity-50"
                                >
                                    Issue Free SSL
                                </button>
                                <span v-else class="text-[10px] font-bold text-emerald-600">Enabled</span>
                            </div>
                        </div>

                    </div>

                    <!-- Bottom Action Controls Bar -->
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pt-3 border-t border-slate-100 text-xs">
                        <div class="flex items-center gap-2 flex-wrap">
                            <Link 
                                :href="route('websites.show', site.id)" 
                                class="px-2.5 py-1.5 bg-slate-50 hover:bg-slate-100 text-slate-700 border border-slate-200 rounded-[3px] text-xs font-bold transition flex items-center gap-1 shadow-2xs"
                            >
                                <Cog6ToothIcon class="w-3.5 h-3.5 text-slate-500" />
                                <span>vHost Settings</span>
                            </Link>

                            <Link 
                                :href="route('databases.index')" 
                                class="px-2.5 py-1.5 bg-slate-50 hover:bg-slate-100 text-slate-700 border border-slate-200 rounded-[3px] text-xs font-bold transition flex items-center gap-1 shadow-2xs"
                            >
                                <CircleStackIcon class="w-3.5 h-3.5 text-slate-500" />
                                <span>Databases</span>
                            </Link>

                            <a 
                                :href="'http://' + site.domain" 
                                target="_blank" 
                                class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-600 border border-slate-200 rounded-[3px] text-xs font-bold transition flex items-center gap-1 shadow-2xs"
                            >
                                <span>Open Website</span>
                                <ArrowTopRightOnSquareIcon class="w-3.5 h-3.5 text-slate-400" />
                            </a>
                        </div>

                        <div>
                            <!-- Cannot delete primary domain -->
                            <span v-if="site.is_primary" class="text-[11px] text-slate-400 font-mono italic">
                                Primary Domain Protected
                            </span>
                            <button 
                                v-else 
                                @click="confirmDelete(site)"
                                class="text-rose-600 hover:text-rose-700 font-bold transition text-xs flex items-center gap-1 cursor-pointer"
                            >
                                <TrashIcon class="w-3.5 h-3.5" />
                                <span>Delete Website</span>
                            </button>
                        </div>
                    </div>

                </div>
            </div>

        </div>

        <!-- PHP Switcher Modal -->
        <div v-if="showPhpModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-2xs flex items-center justify-center p-4 z-50">
            <div class="bg-white rounded-lg max-w-md w-full p-5 shadow-xl space-y-4 border border-slate-200">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div class="flex items-center gap-2">
                        <CpuChipIcon class="w-5 h-5 text-blue-600" />
                        <h3 class="font-bold text-slate-900 text-sm">Switch PHP Engine for {{ selectedWebsite?.domain }}</h3>
                    </div>
                    <button @click="showPhpModal = false" class="text-slate-400 hover:text-slate-600 p-1 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <p class="text-xs text-slate-500 leading-relaxed">
                    Select an isolated PHP-FPM pool for this virtual host. Changing the version applies immediately without downtime.
                </p>

                <form @submit.prevent="submitPhpChange" class="space-y-4">
                    <div>
                        <div class="grid grid-cols-2 gap-2">
                            <label 
                                v-for="ver in ['8.1', '8.2', '8.3', '8.5']" 
                                :key="ver"
                                :class="[
                                    'p-3 rounded-md border text-center cursor-pointer transition font-bold text-xs flex flex-col items-center justify-center gap-1',
                                    phpForm.php_version === ver 
                                        ? 'bg-blue-50/50 border-blue-600 text-blue-700 ring-1 ring-blue-600/20 shadow-2xs' 
                                        : 'border-slate-200 hover:bg-slate-50 text-slate-700'
                                ]"
                            >
                                <input type="radio" v-model="phpForm.php_version" :value="ver" class="hidden" />
                                <span>PHP {{ ver }} FPM</span>
                                <span v-if="ver === '8.2'" class="text-[9.5px] text-blue-600 font-medium">System Default</span>
                            </label>
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                        <button 
                            type="button" 
                            @click="showPhpModal = false" 
                            class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-[3px] text-xs font-bold cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button 
                            type="submit" 
                            :disabled="phpForm.processing"
                            class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold shadow-2xs transition cursor-pointer disabled:opacity-50"
                        >
                            {{ phpForm.processing ? 'Updating...' : 'Apply PHP Engine' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div v-if="showDeleteModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-2xs flex items-center justify-center p-4 z-50">
            <div class="bg-white rounded-lg max-w-md w-full p-5 shadow-xl space-y-4 border border-slate-200">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div class="flex items-center gap-2">
                        <TrashIcon class="w-5 h-5 text-rose-600" />
                        <h3 class="font-bold text-slate-900 text-sm">Delete Website {{ siteToDelete?.domain }}</h3>
                    </div>
                    <button @click="showDeleteModal = false" class="text-slate-400 hover:text-slate-600 p-1 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <div class="text-xs text-slate-600 space-y-2">
                    <p>
                        Are you sure you want to delete <strong class="text-slate-900">{{ siteToDelete?.domain }}</strong>?
                    </p>
                    <div class="p-2.5 bg-rose-50 border border-rose-200 text-rose-800 rounded text-[11px] space-y-1">
                        <p class="font-bold">⚠️ Warning:</p>
                        <p>This will remove the Nginx virtual host configuration and DNS zone for this domain.</p>
                    </div>
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
                        {{ deleteForm.processing ? 'Deleting...' : 'Confirm Delete' }}
                    </button>
                </div>
            </div>
        </div>

    </AuthenticatedLayout>
</template>
