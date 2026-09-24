<script setup>
import { ref, computed } from 'vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import { 
    ArrowsRightLeftIcon, 
    PlusIcon, 
    TrashIcon, 
    ArrowTopRightOnSquareIcon,
    GlobeAltIcon,
    ShieldCheckIcon,
    ServerIcon,
    CommandLineIcon,
    SparklesIcon,
    XMarkIcon,
    CheckIcon,
    BoltIcon,
    ArrowPathIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    redirects: {
        type: Array,
        default: () => []
    },
    websites: {
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
    stats: {
        type: Object,
        default: () => ({
            total: 0,
            active: 0,
            permanent_301: 0,
            temporary_302: 0,
            wildcards: 0
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

// Create Modal State
const showCreateModal = ref(false)
const selectedSubscriptionId = ref(props.subscription?.id || (props.subscriptions[0]?.id ?? ''))

const form = useForm({
    subscription_id: selectedSubscriptionId.value,
    source_domain: 'all',
    source_path: '/',
    target_url: '',
    redirect_code: '301',
    www_redirect_type: 'with_or_without',
    match_wildcard: false,
})

const submitCreate = () => {
    form.post(route('domains.redirects.store'), {
        onSuccess: () => {
            showCreateModal.value = false
            form.reset('source_path', 'target_url', 'match_wildcard')
        }
    })
}

// Toggle status
const toggleForm = useForm({})
const toggleStatus = (redirect) => {
    toggleForm.post(route('domains.redirects.toggle', redirect.id), {
        preserveScroll: true
    })
}

// Delete Modal State
const showDeleteModal = ref(false)
const redirectToDelete = ref(null)
const deleteForm = useForm({})

const confirmDelete = (redirect) => {
    redirectToDelete.value = redirect
    showDeleteModal.value = true
}

const submitDelete = () => {
    if (!redirectToDelete.value) return
    deleteForm.delete(route('domains.redirects.destroy', redirectToDelete.value.id), {
        onSuccess: () => {
            showDeleteModal.value = false
            redirectToDelete.value = null
        }
    })
}

const getFullSourceDisplay = (redirect) => {
    const domain = redirect.source_domain === 'all' ? 'All Domains' : redirect.source_domain
    const path = redirect.source_path || '/'
    return `${domain}${path}`
}
</script>

<template>
    <Head title="URL Redirects (301 & 302) - DeepTouch Host" />

    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto space-y-4">
            
            <!-- 1. Page Header with Breadcrumbs & Actions -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Domains', href: route('websites.index') },
                    { label: 'URL Redirects' }
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
                        <span>Add Redirect</span>
                    </button>
                </template>
            </PageHeader>

            <!-- 2. Top Analytics & Reference Cards Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                
                <!-- Card 1: Active Redirects -->
                <div class="bg-white rounded-lg border border-slate-200 p-4 shadow-2xs space-y-2.5">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                            <ArrowsRightLeftIcon class="w-4 h-4 text-blue-600" />
                            <span>Active Forwarding Rules</span>
                        </span>
                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-200 uppercase">
                            Nginx Engine
                        </span>
                    </div>

                    <div>
                        <div class="flex items-baseline justify-between">
                            <span class="text-2xl font-black text-slate-900 font-mono">{{ stats.active }}</span>
                            <span class="text-xs text-slate-500 font-mono">of {{ stats.total }} Total Rules</span>
                        </div>
                        <div class="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden mt-2">
                            <div class="bg-blue-600 h-full rounded-full w-full"></div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between text-[11px] text-slate-500 pt-1 border-t border-slate-100">
                        <span>Status:</span>
                        <strong class="text-slate-700">Instant Edge Routing</strong>
                    </div>
                </div>

                <!-- Card 2: 301 vs 302 Distribution -->
                <div class="bg-white rounded-lg border border-slate-200 p-4 shadow-2xs space-y-2.5">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                            <SparklesIcon class="w-4 h-4 text-purple-600" />
                            <span>SEO & Protocol Distribution</span>
                        </span>
                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-purple-50 text-purple-700 border border-purple-200 uppercase">
                            HTTP Status
                        </span>
                    </div>

                    <div>
                        <div class="flex items-baseline justify-between">
                            <span class="text-2xl font-black text-slate-900 font-mono">{{ stats.permanent_301 }} <span class="text-xs text-slate-400 font-normal">Perm</span></span>
                            <span class="text-xs text-slate-500 font-mono">{{ stats.temporary_302 }} Temp (302)</span>
                        </div>
                        <div class="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden mt-2">
                            <div 
                                class="bg-purple-600 h-full rounded-full"
                                :style="{ width: `${stats.total > 0 ? (stats.permanent_301 / stats.total) * 100 : 100}%` }"
                            ></div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between text-[11px] text-slate-500 pt-1 border-t border-slate-100">
                        <span>PageRank Pass:</span>
                        <strong class="text-emerald-700 font-bold">100% SEO Link Equity</strong>
                    </div>
                </div>

                <!-- Card 3: DNS & Wildcard Forwarding -->
                <div class="bg-white rounded-lg border border-slate-200 p-4 shadow-2xs space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                            <CommandLineIcon class="w-4 h-4 text-slate-700" />
                            <span>Routing Capabilities</span>
                        </span>
                        <span class="text-[10px] font-bold text-slate-500 uppercase">
                            Capabilities
                        </span>
                    </div>

                    <div class="space-y-1 text-xs font-mono">
                        <div class="flex items-center justify-between p-1.5 bg-slate-50 rounded border border-slate-200/70">
                            <span class="text-slate-500 text-[11px]">Wildcard Forwarding:</span>
                            <span class="font-bold text-slate-900">{{ stats.wildcards }} Active</span>
                        </div>

                        <div class="flex items-center justify-between p-1.5 bg-slate-50 rounded border border-slate-200/70">
                            <span class="text-slate-500 text-[11px]">HTTPS Preservation:</span>
                            <span class="font-bold text-emerald-700">SSL Enabled</span>
                        </div>
                    </div>
                </div>

            </div>

            <!-- 3. Empty State -->
            <div v-if="redirects.length === 0" class="bg-white rounded-lg border border-slate-200 p-10 text-center shadow-2xs">
                <div class="w-12 h-12 rounded-md bg-blue-50 text-blue-600 flex items-center justify-center mx-auto mb-3">
                    <ArrowsRightLeftIcon class="w-6 h-6" />
                </div>
                <h3 class="text-sm font-bold text-slate-900 mb-1">No URL Redirects Configured</h3>
                <p class="text-xs text-slate-500 max-w-sm mx-auto mb-4">
                    Send visitors from an old webpage or domain to your new landing page or external URL with permanent (301) or temporary (302) headers.
                </p>
                <button 
                    @click="showCreateModal = true"
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold shadow-2xs inline-flex items-center gap-1.5 cursor-pointer"
                >
                    <PlusIcon class="w-3.5 h-3.5 stroke-[2.5]" />
                    <span>Create Your First Redirect</span>
                </button>
            </div>

            <!-- 4. Configured Redirects Table -->
            <div v-else class="bg-white rounded-lg border border-slate-200 shadow-2xs overflow-hidden">
                <div class="px-5 py-3.5 border-b border-slate-100 flex items-center justify-between bg-slate-50/60">
                    <div class="flex items-center gap-2">
                        <ArrowsRightLeftIcon class="w-4 h-4 text-blue-600" />
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800">Configured Redirect Rules ({{ redirects.length }})</h3>
                    </div>
                    <button 
                        @click="showCreateModal = true"
                        class="text-xs font-bold text-blue-600 hover:underline flex items-center gap-1 cursor-pointer"
                    >
                        <PlusIcon class="w-3 h-3 stroke-[3]" />
                        <span>Add New Redirect</span>
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs whitespace-nowrap">
                        <thead class="bg-slate-50 text-slate-600 font-bold border-b border-slate-200">
                            <tr>
                                <th class="py-2.5 px-4">Source URL / Path</th>
                                <th class="py-2.5 px-4">Destination Target</th>
                                <th class="py-2.5 px-4">Type</th>
                                <th class="py-2.5 px-4">www. Handling</th>
                                <th class="py-2.5 px-4">Status</th>
                                <th class="py-2.5 px-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            <tr v-for="r in redirects" :key="r.id" class="hover:bg-slate-50/70 transition">
                                <!-- Source URL -->
                                <td class="py-3 px-4 font-bold text-slate-900 font-mono">
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-blue-700">{{ getFullSourceDisplay(r) }}</span>
                                        <span v-if="r.match_wildcard" class="text-[9.5px] px-1.5 py-0.2 rounded bg-amber-50 text-amber-700 border border-amber-200 uppercase font-sans font-bold">
                                            Wildcard (*)
                                        </span>
                                    </div>
                                    <span class="text-[10px] text-slate-400 font-normal font-sans">vHost: {{ r.subscription_domain }}</span>
                                </td>

                                <!-- Target URL -->
                                <td class="py-3 px-4 font-mono text-[11px] text-slate-700 max-w-xs truncate">
                                    <a 
                                        :href="r.target_url" 
                                        target="_blank" 
                                        class="text-blue-600 hover:underline flex items-center gap-1 truncate"
                                    >
                                        <span class="truncate">{{ r.target_url }}</span>
                                        <ArrowTopRightOnSquareIcon class="w-3 h-3 text-slate-400 shrink-0" />
                                    </a>
                                </td>

                                <!-- Type -->
                                <td class="py-3 px-4">
                                    <span 
                                        class="px-2 py-0.5 rounded-[3px] text-[10.5px] font-bold uppercase border"
                                        :class="r.redirect_code === 301 ? 'bg-blue-50 text-blue-700 border-blue-200' : 'bg-amber-50 text-amber-700 border-amber-200'"
                                    >
                                        {{ r.redirect_code }} {{ r.redirect_code === 301 ? 'Permanent' : 'Temporary' }}
                                    </span>
                                </td>

                                <!-- www handling -->
                                <td class="py-3 px-4 text-[11px] text-slate-600 capitalize">
                                    <span v-if="r.www_redirect_type === 'with_or_without'">With or without www</span>
                                    <span v-else-if="r.www_redirect_type === 'only_with'">Only with www</span>
                                    <span v-else>Do not redirect www</span>
                                </td>

                                <!-- Status Toggle -->
                                <td class="py-3 px-4">
                                    <button 
                                        @click="toggleStatus(r)"
                                        class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded border uppercase transition cursor-pointer"
                                        :class="r.status === 'active' ? 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100' : 'bg-slate-100 text-slate-600 border-slate-200 hover:bg-slate-200'"
                                        title="Click to toggle rule status"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full" :class="r.status === 'active' ? 'bg-emerald-500' : 'bg-slate-400'"></span>
                                        <span>{{ r.status }}</span>
                                    </button>
                                </td>

                                <!-- Actions -->
                                <td class="py-3 px-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <a 
                                            :href="r.target_url" 
                                            target="_blank" 
                                            class="p-1 text-slate-400 hover:text-slate-700 transition"
                                            title="Test Destination URL"
                                        >
                                            <ArrowTopRightOnSquareIcon class="w-4 h-4" />
                                        </a>
                                        <button 
                                            @click="confirmDelete(r)"
                                            class="p-1 text-rose-500 hover:text-rose-700 transition cursor-pointer"
                                            title="Delete Redirect"
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

        <!-- 1. Create Redirect Modal -->
        <div v-if="showCreateModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-2xs flex items-center justify-center p-4 z-50">
            <div class="bg-white rounded-lg max-w-lg w-full p-5 shadow-xl space-y-4 border border-slate-200">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div class="flex items-center gap-2">
                        <ArrowsRightLeftIcon class="w-5 h-5 text-blue-600" />
                        <h3 class="font-bold text-slate-900 text-sm">Add New URL Redirect</h3>
                    </div>
                    <button @click="showCreateModal = false" class="text-slate-400 hover:text-slate-600 p-1 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitCreate" class="space-y-3.5 text-xs">
                    
                    <!-- Redirect Type Selector (301 vs 302) -->
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Redirect Type</label>
                        <div class="grid grid-cols-2 gap-2 font-mono">
                            <label 
                                class="p-2.5 rounded border text-center cursor-pointer transition flex flex-col items-center justify-center gap-0.5"
                                :class="form.redirect_code === '301' ? 'bg-blue-50/70 border-blue-600 text-blue-700 ring-1 ring-blue-600/20' : 'border-slate-200 hover:bg-slate-50 text-slate-700'"
                            >
                                <input type="radio" v-model="form.redirect_code" value="301" class="hidden" />
                                <span class="font-bold text-xs">301 Permanent</span>
                                <span class="text-[9.5px] text-slate-500 font-sans">SEO Equity Passed</span>
                            </label>

                            <label 
                                class="p-2.5 rounded border text-center cursor-pointer transition flex flex-col items-center justify-center gap-0.5"
                                :class="form.redirect_code === '302' ? 'bg-blue-50/70 border-blue-600 text-blue-700 ring-1 ring-blue-600/20' : 'border-slate-200 hover:bg-slate-50 text-slate-700'"
                            >
                                <input type="radio" v-model="form.redirect_code" value="302" class="hidden" />
                                <span class="font-bold text-xs">302 Temporary</span>
                                <span class="text-[9.5px] text-slate-500 font-sans">Short-term / Promo</span>
                            </label>
                        </div>
                    </div>

                    <!-- Source Domain & Path -->
                    <div class="space-y-2">
                        <label class="block font-bold text-slate-700">https?:// (Source Domain & Path)</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <select 
                                v-model="form.source_domain"
                                class="text-xs font-bold rounded-[3px] border-slate-300 bg-slate-50 text-slate-800 py-2 px-2.5 focus:ring-blue-500 focus:border-blue-500"
                            >
                                <option value="all">** All Public Domains **</option>
                                <option v-for="site in websites" :key="site.id" :value="site.domain">
                                    {{ site.domain }}
                                </option>
                            </select>

                            <div class="flex rounded-md shadow-2xs">
                                <span class="inline-flex items-center px-2.5 rounded-l-[3px] border border-r-0 border-slate-300 bg-slate-100 text-slate-500 font-mono text-xs">
                                    /
                                </span>
                                <input 
                                    type="text" 
                                    v-model="form.source_path"
                                    placeholder="old-page or folder"
                                    class="flex-1 text-xs font-mono rounded-r-[3px] border-slate-300 bg-white text-slate-900 py-2 px-2.5 focus:ring-blue-500 focus:border-blue-500"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Destination Target URL -->
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Redirects to (Destination URL)</label>
                        <input 
                            type="url" 
                            v-model="form.target_url"
                            placeholder="https://example.com/new-destination"
                            required
                            class="w-full text-xs font-mono font-bold rounded-[3px] border-slate-300 bg-white text-slate-900 py-2 px-3 focus:ring-blue-500 focus:border-blue-500"
                        />
                        <p v-if="form.errors.target_url" class="text-[11px] text-rose-600 mt-1">
                            {{ form.errors.target_url }}
                        </p>
                    </div>

                    <!-- www. Redirection Handling -->
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">www. Redirection Mode</label>
                        <div class="space-y-1.5 p-2.5 bg-slate-50 rounded border border-slate-200">
                            <label class="flex items-center gap-2 cursor-pointer text-slate-700 font-medium">
                                <input type="radio" v-model="form.www_redirect_type" value="with_or_without" class="text-blue-600 focus:ring-blue-500" />
                                <span>Redirect with or without www. (Recommended)</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer text-slate-700 font-medium">
                                <input type="radio" v-model="form.www_redirect_type" value="only_with" class="text-blue-600 focus:ring-blue-500" />
                                <span>Only redirect with www.</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer text-slate-700 font-medium">
                                <input type="radio" v-model="form.www_redirect_type" value="do_not_redirect" class="text-blue-600 focus:ring-blue-500" />
                                <span>Do Not Redirect www.</span>
                            </label>
                        </div>
                    </div>

                    <!-- Wildcard Option -->
                    <div class="flex items-center gap-2 pt-1">
                        <input 
                            type="checkbox" 
                            id="match_wildcard" 
                            v-model="form.match_wildcard"
                            class="rounded text-blue-600 focus:ring-blue-500 border-slate-300"
                        />
                        <label for="match_wildcard" class="text-xs text-slate-700 font-medium cursor-pointer">
                            <strong>Wildcard Redirect:</strong> Redirect all files in directory to the same file name at destination.
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
                            :disabled="form.processing"
                            class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold shadow-2xs transition cursor-pointer disabled:opacity-50"
                        >
                            {{ form.processing ? 'Saving...' : 'Add Redirect' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 2. Delete Confirmation Modal -->
        <div v-if="showDeleteModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-2xs flex items-center justify-center p-4 z-50">
            <div class="bg-white rounded-lg max-w-md w-full p-5 shadow-xl space-y-4 border border-slate-200">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div class="flex items-center gap-2">
                        <TrashIcon class="w-5 h-5 text-rose-600" />
                        <h3 class="font-bold text-slate-900 text-sm">Delete Redirect Rule</h3>
                    </div>
                    <button @click="showDeleteModal = false" class="text-slate-400 hover:text-slate-600 p-1 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <div class="text-xs text-slate-600 space-y-2">
                    <p>
                        Are you sure you want to remove the redirect for <strong class="text-slate-900 font-mono">{{ getFullSourceDisplay(redirectToDelete) }}</strong>?
                    </p>
                    <p class="text-[11px] text-slate-400">
                        Visitors accessing this path will no longer be forwarded to <code>{{ redirectToDelete?.target_url }}</code>.
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
                        {{ deleteForm.processing ? 'Deleting...' : 'Confirm Delete' }}
                    </button>
                </div>
            </div>
        </div>

    </AuthenticatedLayout>
</template>
