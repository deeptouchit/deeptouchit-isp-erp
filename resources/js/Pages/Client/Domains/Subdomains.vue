<script setup>
import { ref, computed } from 'vue'
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
    ServerIcon,
    CommandLineIcon,
    ClipboardDocumentIcon,
    ExclamationTriangleIcon,
    XMarkIcon,
    Cog6ToothIcon,
    LockClosedIcon,
    SparklesIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    subdomains: {
        type: Array,
        default: () => []
    },
    subscription: {
        type: Object,
        default: null
    },
    subscriptions: {
        type: Array,
        default: () => []
    },
    quota: {
        type: Object,
        default: () => ({
            total_allowed: 10,
            total_used: 0,
            remaining: 10,
            can_add: true,
            usage_percentage: 0
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

// Copy helper
const copiedText = ref('')
const copyToClipboard = (text, label) => {
    navigator.clipboard.writeText(text)
    copiedText.value = label
    setTimeout(() => {
        copiedText.value = ''
    }, 2000)
}

// Create Subdomain Modal
const showCreateModal = ref(false)
const selectedSubscriptionId = ref(props.subscription?.id || (props.subscriptions[0]?.id ?? ''))

const createForm = useForm({
    subscription_id: selectedSubscriptionId.value,
    subdomain_prefix: '',
    document_root: '',
    php_version: '8.2',
    auto_ssl: true,
})

const selectedSubDomain = computed(() => {
    const sub = props.subscriptions.find(s => s.id === createForm.subscription_id) || props.subscription
    return sub?.domain || 'somitysoft.com'
})

const previewFullDomain = computed(() => {
    const prefix = createForm.subdomain_prefix.toLowerCase().trim() || 'subdomain'
    return `${prefix}.${selectedSubDomain.value}`
})

const previewDocRoot = computed(() => {
    const sub = props.subscriptions.find(s => s.id === createForm.subscription_id) || props.subscription
    const user = sub?.username || 'user'
    return `/var/www/vhosts/${user}/${previewFullDomain.value}/public_html`
})

const submitCreateSubdomain = () => {
    createForm.document_root = previewDocRoot.value
    createForm.post(route('domains.subdomains.store'), {
        onSuccess: () => {
            showCreateModal.value = false
            createForm.reset('subdomain_prefix', 'document_root')
        }
    })
}

// PHP Switcher Modal State
const showPhpModal = ref(false)
const selectedSubdomain = ref(null)
const phpForm = useForm({
    php_version: '8.2'
})

const openPhpModal = (sub) => {
    selectedSubdomain.value = sub
    phpForm.php_version = sub.php_version || '8.2'
    showPhpModal.value = true
}

const submitPhpChange = () => {
    if (!selectedSubdomain.value) return
    phpForm.post(route('domains.subdomains.php', selectedSubdomain.value.id), {
        onSuccess: () => {
            showPhpModal.value = false
        }
    })
}

// SSL Trigger
const sslForm = useForm({})
const triggerSsl = (sub) => {
    sslForm.post(route('domains.subdomains.ssl', sub.id))
}

// Delete Modal State
const showDeleteModal = ref(false)
const subToDelete = ref(null)
const deleteForm = useForm({})

const confirmDelete = (sub) => {
    subToDelete.value = sub
    showDeleteModal.value = true
}

const submitDelete = () => {
    if (!subToDelete.value) return
    deleteForm.delete(route('domains.subdomains.destroy', subToDelete.value.id), {
        onSuccess: () => {
            showDeleteModal.value = false
            subToDelete.value = null
        }
    })
}
</script>

<template>
    <Head title="Subdomain Management - DeepTouch Host" />

    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto space-y-4">
            
            <!-- 1. Page Header with Breadcrumbs & Actions -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Domains', href: route('websites.index') },
                    { label: 'Subdomains' }
                ]"
                :show-refresh="true"
                @refreshed="router.reload({ preserveScroll: true })"
            >
                <template #actions>
                    <button 
                        @click="showCreateModal = true"
                        class="px-2.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold flex items-center gap-1.5 shadow-2xs transition cursor-pointer"
                    >
                        <PlusIcon class="w-3.5 h-3.5 stroke-[2.5]" />
                        <span>Create Subdomain</span>
                    </button>
                </template>
            </PageHeader>

            <!-- 2. Top Analytics & Reference Cards Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                
                <!-- Card 1: Active Subdomains -->
                <div class="bg-white rounded-lg border border-slate-200 p-4 shadow-2xs space-y-2.5">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                            <GlobeAltIcon class="w-4 h-4 text-blue-600" />
                            <span>Active Subdomains</span>
                        </span>
                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-200 uppercase">
                            Nginx vHost
                        </span>
                    </div>

                    <div>
                        <div class="flex items-baseline justify-between">
                            <span class="text-2xl font-black text-slate-900 font-mono">{{ subdomains.length }}</span>
                            <span class="text-xs text-slate-500">Virtual Host(s)</span>
                        </div>
                        <div class="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden mt-2">
                            <div class="bg-blue-600 h-full rounded-full w-full"></div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between text-[11px] text-slate-500 pt-1 border-t border-slate-100">
                        <span>Isolation:</span>
                        <strong class="text-slate-700">Dedicated Root & Pool</strong>
                    </div>
                </div>

                <!-- Card 2: Subdomain Quota -->
                <div class="bg-white rounded-lg border border-slate-200 p-4 shadow-2xs space-y-2.5">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                            <ServerIcon class="w-4 h-4 text-purple-600" />
                            <span>Subdomain Quota</span>
                        </span>
                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-purple-50 text-purple-700 border border-purple-200 uppercase">
                            {{ quota.remaining }} Available
                        </span>
                    </div>

                    <div>
                        <div class="flex items-baseline justify-between">
                            <span class="text-2xl font-black text-slate-900 font-mono">{{ quota.total_used }} / {{ quota.total_allowed }}</span>
                            <span class="text-xs text-slate-500 font-mono">{{ quota.usage_percentage }}% Used</span>
                        </div>
                        <div class="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden mt-2">
                            <div 
                                class="bg-purple-600 h-full rounded-full transition-all duration-500"
                                :style="{ width: `${Math.max(4, quota.usage_percentage)}%` }"
                            ></div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between text-[11px] text-slate-500 pt-1 border-t border-slate-100">
                        <span>Allocated Plan:</span>
                        <strong class="text-slate-700">{{ subscription?.plan?.name || 'Hosting Plan' }}</strong>
                    </div>
                </div>

                <!-- Card 3: DNS & Nameservers Quick Copy -->
                <div class="bg-white rounded-lg border border-slate-200 p-4 shadow-2xs space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                            <CommandLineIcon class="w-4 h-4 text-slate-700" />
                            <span>DNS Routing</span>
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
                            <span class="text-slate-500 text-[11px]">Wildcard SSL:</span>
                            <span class="font-bold text-emerald-700">Supported</span>
                        </div>
                    </div>
                </div>

            </div>

            <!-- 3. Empty State -->
            <div v-if="subdomains.length === 0" class="bg-white rounded-lg border border-slate-200 p-10 text-center shadow-2xs">
                <div class="w-12 h-12 rounded-md bg-blue-50 text-blue-600 flex items-center justify-center mx-auto mb-3">
                    <GlobeAltIcon class="w-6 h-6" />
                </div>
                <h3 class="text-sm font-bold text-slate-900 mb-1">No Subdomains Created</h3>
                <p class="text-xs text-slate-500 max-w-sm mx-auto mb-4">
                    Create subdomains like <code>app.yourdomain.com</code> or <code>api.yourdomain.com</code> with an isolated Nginx VirtualHost and dedicated document root.
                </p>
                <button 
                    @click="showCreateModal = true"
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold shadow-2xs inline-flex items-center gap-1.5 cursor-pointer"
                >
                    <PlusIcon class="w-3.5 h-3.5 stroke-[2.5]" />
                    <span>Create Your First Subdomain</span>
                </button>
            </div>

            <!-- 4. Subdomains Management Table -->
            <div v-else class="bg-white rounded-lg border border-slate-200 shadow-2xs overflow-hidden">
                <div class="px-5 py-3.5 border-b border-slate-100 flex items-center justify-between bg-slate-50/60">
                    <div class="flex items-center gap-2">
                        <GlobeAltIcon class="w-4 h-4 text-blue-600" />
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800">Hosted Subdomains ({{ subdomains.length }})</h3>
                    </div>
                    <button 
                        @click="showCreateModal = true"
                        class="text-xs font-bold text-blue-600 hover:underline flex items-center gap-1 cursor-pointer"
                    >
                        <PlusIcon class="w-3 h-3 stroke-[3]" />
                        <span>Add New</span>
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs whitespace-nowrap">
                        <thead class="bg-slate-50 text-slate-600 font-bold border-b border-slate-200">
                            <tr>
                                <th class="py-2.5 px-4">Subdomain URL</th>
                                <th class="py-2.5 px-4">Document Root</th>
                                <th class="py-2.5 px-4">PHP Runtime</th>
                                <th class="py-2.5 px-4">SSL Security</th>
                                <th class="py-2.5 px-4">Status</th>
                                <th class="py-2.5 px-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            <tr v-for="sub in subdomains" :key="sub.id" class="hover:bg-slate-50/70 transition">
                                <!-- Subdomain Link -->
                                <td class="py-3 px-4 font-bold text-slate-900">
                                    <div class="flex items-center gap-1.5">
                                        <a 
                                            :href="'http://' + sub.domain" 
                                            target="_blank" 
                                            class="text-xs font-bold text-slate-900 hover:text-blue-600 transition flex items-center gap-1 font-mono"
                                        >
                                            <span>{{ sub.domain }}</span>
                                            <ArrowTopRightOnSquareIcon class="w-3 h-3 text-slate-400" />
                                        </a>
                                    </div>
                                    <span class="text-[10px] text-slate-400 font-normal">Parent: {{ sub.parent_domain }}</span>
                                </td>

                                <!-- DocRoot -->
                                <td class="py-3 px-4 font-mono text-[11px] text-slate-600 max-w-xs truncate">
                                    <div class="flex items-center gap-1.5">
                                        <span class="truncate">{{ sub.document_root }}</span>
                                        <Link 
                                            :href="route('file.browse')" 
                                            class="px-1.5 py-0.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded text-[10px] font-sans font-bold shrink-0 transition"
                                            title="Open in File Manager"
                                        >
                                            Files
                                        </Link>
                                    </div>
                                </td>

                                <!-- PHP Engine -->
                                <td class="py-3 px-4">
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-blue-700 font-bold font-mono text-[11px]">PHP {{ sub.php_version }}</span>
                                        <button 
                                            @click="openPhpModal(sub)"
                                            class="px-1.5 py-0.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded text-[10px] font-bold transition cursor-pointer"
                                        >
                                            Switch
                                        </button>
                                    </div>
                                </td>

                                <!-- SSL -->
                                <td class="py-3 px-4">
                                    <span v-if="sub.ssl_status === 'active'" class="inline-flex items-center gap-1 text-[10.5px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                                        <ShieldCheckIcon class="w-3.5 h-3.5 text-emerald-600" />
                                        <span>Active SSL</span>
                                    </span>
                                    <button 
                                        v-else
                                        @click="triggerSsl(sub)"
                                        :disabled="sslForm.processing"
                                        class="px-2 py-0.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded text-[10.5px] font-bold transition cursor-pointer disabled:opacity-50"
                                    >
                                        Issue SSL
                                    </button>
                                </td>

                                <!-- Status -->
                                <td class="py-3 px-4">
                                    <span class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200 uppercase">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        <span>{{ sub.status }}</span>
                                    </span>
                                </td>

                                <!-- Actions -->
                                <td class="py-3 px-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <a 
                                            :href="'http://' + sub.domain" 
                                            target="_blank" 
                                            class="p-1 text-slate-400 hover:text-slate-700 transition"
                                            title="Open Subdomain"
                                        >
                                            <ArrowTopRightOnSquareIcon class="w-4 h-4" />
                                        </a>
                                        <button 
                                            @click="confirmDelete(sub)"
                                            class="p-1 text-rose-500 hover:text-rose-700 transition cursor-pointer"
                                            title="Delete Subdomain"
                                        >
                                            <TrashIcon class="w-4 h-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <!-- 1. Create Subdomain Modal -->
        <div v-if="showCreateModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-2xs flex items-center justify-center p-4 z-50">
            <div class="bg-white rounded-lg max-w-lg w-full p-5 shadow-xl space-y-4 border border-slate-200">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div class="flex items-center gap-2">
                        <GlobeAltIcon class="w-5 h-5 text-blue-600" />
                        <h3 class="font-bold text-slate-900 text-sm">Create New Subdomain VirtualHost</h3>
                    </div>
                    <button @click="showCreateModal = false" class="text-slate-400 hover:text-slate-600 p-1 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitCreateSubdomain" class="space-y-3.5 text-xs">
                    <!-- Parent Subscription Selector -->
                    <div v-if="subscriptions.length > 1">
                        <label class="block font-bold text-slate-700 mb-1">Parent Hosting Domain</label>
                        <select 
                            v-model="createForm.subscription_id" 
                            class="w-full text-xs font-bold rounded-[3px] border-slate-300 bg-slate-50 text-slate-800 py-2 px-2.5 focus:ring-blue-500 focus:border-blue-500"
                        >
                            <option v-for="sub in subscriptions" :key="sub.id" :value="sub.id">
                                {{ sub.domain }} ({{ sub.plan_name }})
                            </option>
                        </select>
                    </div>

                    <!-- Subdomain Prefix & FQDN Preview -->
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Subdomain Prefix</label>
                        <div class="flex rounded-md shadow-2xs">
                            <input 
                                type="text" 
                                v-model="createForm.subdomain_prefix"
                                placeholder="e.g. app, api, dev, blog"
                                required
                                class="flex-1 text-xs font-bold rounded-l-[3px] border-slate-300 bg-white text-slate-900 py-2 px-3 focus:ring-blue-500 focus:border-blue-500"
                            />
                            <span class="inline-flex items-center px-3 rounded-r-[3px] border border-l-0 border-slate-300 bg-slate-100 text-slate-600 font-mono font-bold text-xs">
                                .{{ selectedSubDomain }}
                            </span>
                        </div>
                        <p v-if="createForm.errors.subdomain_prefix" class="text-[11px] text-rose-600 mt-1">
                            {{ createForm.errors.subdomain_prefix }}
                        </p>
                    </div>

                    <!-- Document Root Preview -->
                    <div class="p-2.5 bg-slate-50 rounded border border-slate-200 text-slate-600 font-mono text-[11px] space-y-1">
                        <span class="text-slate-400 font-sans font-bold block text-[10px] uppercase">Automated Document Root</span>
                        <span class="text-slate-800 font-bold block truncate">{{ previewDocRoot }}</span>
                    </div>

                    <!-- PHP Version -->
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">PHP Engine Version</label>
                        <div class="grid grid-cols-4 gap-2 font-mono">
                            <label 
                                v-for="ver in ['8.1', '8.2', '8.3', '8.5']" 
                                :key="ver"
                                :class="[
                                    'p-2 rounded border text-center cursor-pointer transition font-bold text-xs',
                                    createForm.php_version === ver 
                                        ? 'bg-blue-50/70 border-blue-600 text-blue-700 ring-1 ring-blue-600/20' 
                                        : 'border-slate-200 hover:bg-slate-50 text-slate-700'
                                ]"
                            >
                                <input type="radio" v-model="createForm.php_version" :value="ver" class="hidden" />
                                <span>{{ ver }}</span>
                            </label>
                        </div>
                    </div>

                    <!-- Auto SSL Checkbox -->
                    <div class="flex items-center gap-2 pt-1">
                        <input 
                            type="checkbox" 
                            id="auto_ssl" 
                            v-model="createForm.auto_ssl"
                            class="rounded text-blue-600 focus:ring-blue-500 border-slate-300"
                        />
                        <label for="auto_ssl" class="text-xs text-slate-700 font-medium cursor-pointer">
                            Enable Automated Free SSL Certificate (Let's Encrypt)
                        </label>
                    </div>

                    <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                        <button 
                            type="button" 
                            @click="showCreateModal = false" 
                            class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-[3px] text-xs font-bold cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button 
                            type="submit" 
                            :disabled="createForm.processing"
                            class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold shadow-2xs transition cursor-pointer disabled:opacity-50"
                        >
                            {{ createForm.processing ? 'Provisioning...' : 'Deploy Subdomain' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 2. PHP Switcher Modal -->
        <div v-if="showPhpModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-2xs flex items-center justify-center p-4 z-50">
            <div class="bg-white rounded-lg max-w-md w-full p-5 shadow-xl space-y-4 border border-slate-200">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div class="flex items-center gap-2">
                        <CpuChipIcon class="w-5 h-5 text-blue-600" />
                        <h3 class="font-bold text-slate-900 text-sm">Switch PHP for {{ selectedSubdomain?.domain }}</h3>
                    </div>
                    <button @click="showPhpModal = false" class="text-slate-400 hover:text-slate-600 p-1 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitPhpChange" class="space-y-4">
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
                        </label>
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

        <!-- 3. Delete Confirmation Modal -->
        <div v-if="showDeleteModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-2xs flex items-center justify-center p-4 z-50">
            <div class="bg-white rounded-lg max-w-md w-full p-5 shadow-xl space-y-4 border border-slate-200">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div class="flex items-center gap-2">
                        <TrashIcon class="w-5 h-5 text-rose-600" />
                        <h3 class="font-bold text-slate-900 text-sm">Delete Subdomain {{ subToDelete?.domain }}</h3>
                    </div>
                    <button @click="showDeleteModal = false" class="text-slate-400 hover:text-slate-600 p-1 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <div class="text-xs text-slate-600 space-y-2">
                    <p>
                        Are you sure you want to delete <strong class="text-slate-900">{{ subToDelete?.domain }}</strong>?
                    </p>
                    <div class="p-2.5 bg-rose-50 border border-rose-200 text-rose-800 rounded text-[11px] space-y-1">
                        <p class="font-bold">⚠️ Warning:</p>
                        <p>This will remove the Nginx virtual host configuration and DNS zone for this subdomain.</p>
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
