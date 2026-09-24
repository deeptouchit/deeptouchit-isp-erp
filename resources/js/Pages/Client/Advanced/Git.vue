<script setup>
import { ref, computed } from 'vue'
import { Head, useForm, router, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import { 
    CodeBracketIcon, 
    PlusIcon, 
    PencilSquareIcon, 
    TrashIcon, 
    PlayIcon,
    ClipboardDocumentIcon,
    CheckIcon,
    KeyIcon,
    ShieldCheckIcon,
    ArrowPathIcon,
    XMarkIcon,
    ExclamationTriangleIcon,
    InformationCircleIcon,
    MagnifyingGlassIcon,
    DocumentTextIcon,
    CommandLineIcon,
    SparklesIcon,
    LinkIcon,
    ArrowTopRightOnSquareIcon,
    CheckCircleIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    repositories: {
        type: Array,
        default: () => []
    },
    recentLogs: {
        type: Array,
        default: () => []
    },
    systemInfo: {
        type: Object,
        default: () => ({
            git_version: 'git version 2.53.0',
            deploy_key: 'ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAICxt2GvnplflsT2pwZ27/j08raqBaJ+M7I14mVqSqsrb deeptouchit@github',
            default_doc_root: '/var/www/vhosts/somitysoft/somitysoft.com/public_html',
            username: 'somitysoft',
            domain: 'somitysoft.com'
        })
    },
    sessionFlash: {
        type: Object,
        default: () => ({})
    }
})

// Active Tab
const activeTab = ref('repos') // 'repos' | 'logs' | 'guide'

// Search
const repoSearch = ref('')
const filteredRepos = computed(() => {
    if (!repoSearch.value.trim()) return props.repositories
    const q = repoSearch.value.toLowerCase().trim()
    return props.repositories.filter(r => 
        r.name.toLowerCase().includes(q) ||
        r.repository_url.toLowerCase().includes(q) ||
        r.branch.toLowerCase().includes(q) ||
        r.deploy_path.toLowerCase().includes(q)
    )
})

// Modals
const showCreateModal = ref(false)
const showEditModal = ref(false)
const showDeleteModal = ref(false)
const showWebhookModal = ref(false)
const showDeployKeyModal = ref(false)
const showLogModal = ref(false)

const editingRepo = ref(null)
const deletingRepo = ref(null)
const viewingWebhookRepo = ref(null)
const viewingLog = ref(null)
const deployingRepoId = ref(null)

// Copy helper
const copiedField = ref(null)
const copyToClipboard = (text, fieldName) => {
    navigator.clipboard.writeText(text)
    copiedField.value = fieldName
    setTimeout(() => {
        copiedField.value = null
    }, 2000)
}

// Form
const repoForm = useForm({
    name: '',
    repository_url: '',
    branch: 'main',
    deploy_path: props.systemInfo.default_doc_root,
    post_deploy_script: 'composer install --no-dev --optimize-autoloader\nphp artisan optimize:clear',
    auto_deploy: true,
})

const openCreateModal = () => {
    repoForm.reset({
        name: '',
        repository_url: '',
        branch: 'main',
        deploy_path: props.systemInfo.default_doc_root,
        post_deploy_script: 'composer install --no-dev --optimize-autoloader\nphp artisan optimize:clear',
        auto_deploy: true,
    })
    showCreateModal.value = true
}

const openEditModal = (repo) => {
    editingRepo.value = repo
    repoForm.name = repo.name
    repoForm.repository_url = repo.repository_url
    repoForm.branch = repo.branch
    repoForm.deploy_path = repo.deploy_path
    repoForm.post_deploy_script = repo.post_deploy_script || ''
    repoForm.auto_deploy = repo.auto_deploy
    showEditModal.value = true
}

const submitCreateRepo = () => {
    repoForm.post(route('advanced.git.create-repo'), {
        preserveScroll: true,
        onSuccess: () => {
            showCreateModal.value = false
            repoForm.reset()
        }
    })
}

const submitEditRepo = () => {
    if (!editingRepo.value) return
    repoForm.put(route('advanced.git.update-repo', editingRepo.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showEditModal.value = false
            editingRepo.value = null
        }
    })
}

const confirmDelete = (repo) => {
    deletingRepo.value = repo
    showDeleteModal.value = true
}

const executeDelete = () => {
    if (!deletingRepo.value) return
    router.delete(route('advanced.git.delete-repo', deletingRepo.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false
            deletingRepo.value = null
        }
    })
}

const deployRepoNow = (repo) => {
    deployingRepoId.value = repo.id
    router.post(route('advanced.git.deploy-repo', repo.id), {}, {
        preserveScroll: true,
        onFinish: () => {
            deployingRepoId.value = null
        }
    })
}

const regenerateWebhook = (repo) => {
    if (!confirm('Regenerate secret webhook token? You must update your GitHub / GitLab webhook URL.')) return
    router.post(route('advanced.git.regenerate-webhook', repo.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            // Find updated repo
            const updated = props.repositories.find(r => r.id === repo.id)
            if (updated) {
                viewingWebhookRepo.value = updated
            }
        }
    })
}

const clearLogs = () => {
    if (!confirm('Clear all deployment history logs?')) return
    router.post(route('advanced.git.clear-logs'), {}, {
        preserveScroll: true,
    })
}

// Provider badge styling
const getProviderBadge = (provider) => {
    switch(provider) {
        case 'github': return { name: 'GitHub', class: 'bg-slate-900 text-white' }
        case 'gitlab': return { name: 'GitLab', class: 'bg-amber-600 text-white' }
        case 'bitbucket': return { name: 'Bitbucket', class: 'bg-blue-600 text-white' }
        default: return { name: 'Git / SSH', class: 'bg-slate-700 text-white' }
    }
}
</script>

<template>
    <Head title="Git Version Control & CI/CD Deployment - DeepTouch Host" />

    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto space-y-4">
            
            <!-- 1. Page Header with Breadcrumbs & Actions -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Advanced Tools', href: '#' },
                    { label: 'Git Version Control & CI/CD' }
                ]"
                :show-refresh="true"
                @refreshed="router.reload({ preserveScroll: true })"
            >
                <template #actions>
                    <button 
                        @click="showDeployKeyModal = true"
                        class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200 rounded-[3px] text-xs font-bold flex items-center gap-1.5 transition cursor-pointer shadow-2xs"
                    >
                        <KeyIcon class="w-3.5 h-3.5 text-indigo-600" />
                        <span>SSH Deploy Key</span>
                    </button>

                    <button 
                        @click="openCreateModal"
                        class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold flex items-center gap-1.5 shadow-2xs transition cursor-pointer"
                    >
                        <PlusIcon class="w-3.5 h-3.5 stroke-[2.5]" />
                        <span>Clone / Add Repository</span>
                    </button>
                </template>
            </PageHeader>

            <!-- Manual Deployment Terminal Console Output (If executed in session) -->
            <div 
                v-if="sessionFlash?.deploy_output" 
                class="bg-slate-900 border border-slate-800 rounded-lg p-4 shadow-xl space-y-2.5 text-slate-100 animate-in fade-in duration-200"
            >
                <div class="flex items-center justify-between border-b border-slate-800 pb-2">
                    <div class="flex items-center gap-2">
                        <CommandLineIcon class="w-4 h-4 text-emerald-400" />
                        <span class="text-xs font-bold font-mono">Deployment Console Build Output</span>
                        <span 
                            class="text-[10px] font-mono font-bold px-1.5 py-0.2 rounded uppercase"
                            :class="sessionFlash.deploy_exit_code === 0 ? 'bg-emerald-950 text-emerald-400 border border-emerald-800' : 'bg-rose-950 text-rose-400 border border-rose-800'"
                        >
                            {{ sessionFlash.deploy_status }} • Exit Code: {{ sessionFlash.deploy_exit_code }} ({{ Math.round(sessionFlash.deploy_duration_ms / 1000 * 100) / 100 }}s)
                        </span>
                    </div>

                    <button 
                        @click="sessionFlash.deploy_output = null" 
                        class="text-slate-400 hover:text-white cursor-pointer"
                    >
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <pre class="font-mono text-xs text-slate-300 bg-slate-950/80 p-3 rounded overflow-x-auto leading-relaxed max-h-56 whitespace-pre-wrap">{{ sessionFlash.deploy_output }}</pre>
            </div>

            <!-- 2. Top 3 KPI / Engine Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                
                <!-- Card 1: Git Engine & Core Info -->
                <div class="bg-white border border-slate-200 rounded-lg p-4 shadow-2xs space-y-2.5">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                            <CodeBracketIcon class="w-4 h-4 text-rose-600" />
                            <span>Git Version Control Engine</span>
                        </span>
                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-200 font-mono">
                            INSTALLED
                        </span>
                    </div>

                    <div>
                        <div class="flex items-baseline gap-2">
                            <span class="text-2xl font-black text-slate-900 font-mono">{{ repositories.length }}</span>
                            <span class="text-xs text-slate-400 font-bold font-mono">Repositories Connected</span>
                        </div>
                        <p class="text-[11px] text-slate-500 mt-1 font-mono">
                            Engine: <strong class="text-slate-800">{{ systemInfo.git_version }}</strong>
                        </p>
                    </div>

                    <div class="flex items-center justify-between text-[11px] text-slate-500 pt-1 border-t border-slate-100 font-mono">
                        <span>Deploy User:</span>
                        <strong class="text-slate-700">{{ systemInfo.username }}</strong>
                    </div>
                </div>

                <!-- Card 2: Server SSH Deploy Key -->
                <div class="bg-white border border-slate-200 rounded-lg p-4 shadow-2xs space-y-2.5">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                            <KeyIcon class="w-4 h-4 text-indigo-600" />
                            <span>Server Public SSH Key</span>
                        </span>
                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-indigo-50 text-indigo-700 border border-indigo-200 font-mono">
                            ED25519
                        </span>
                    </div>

                    <div class="bg-slate-50 p-2 rounded border border-slate-200/70 font-mono text-[11px] flex items-center justify-between">
                        <span class="truncate text-slate-700 font-medium">{{ systemInfo.deploy_key.slice(0, 32) }}...</span>
                        <button 
                            @click="copyToClipboard(systemInfo.deploy_key, 'kpi_key')"
                            class="text-slate-400 hover:text-blue-600 cursor-pointer ml-1.5 shrink-0"
                            title="Copy Deploy Key"
                        >
                            <CheckIcon v-if="copiedField === 'kpi_key'" class="w-3.5 h-3.5 text-emerald-600" />
                            <ClipboardDocumentIcon v-else class="w-3.5 h-3.5" />
                        </button>
                    </div>

                    <div class="flex items-center justify-between text-[11px] text-slate-500 pt-1 border-t border-slate-100">
                        <span>Add as Deploy Key in:</span>
                        <span class="font-bold text-slate-800">GitHub / GitLab</span>
                    </div>
                </div>

                <!-- Card 3: CI/CD Webhook Automation -->
                <div class="bg-gradient-to-br from-indigo-50/70 to-blue-50/50 border border-indigo-100 rounded-lg p-4 shadow-2xs flex flex-col justify-between space-y-2.5">
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-indigo-900 flex items-center gap-1.5">
                                <SparklesIcon class="w-4 h-4 text-indigo-600" />
                                <span>Zero-Downtime Auto-Deploy</span>
                            </span>
                            <span class="text-[10px] font-bold px-1.5 py-0.5 bg-indigo-100 text-indigo-800 rounded font-mono">
                                WEBHOOKS
                            </span>
                        </div>
                        <p class="text-[11px] text-slate-600 mt-1 line-clamp-2">
                            Push to your repository branch and the server will automatically pull and run build scripts.
                        </p>
                    </div>

                    <div class="pt-2 border-t border-indigo-100 flex items-center justify-between text-[11px] text-indigo-900 font-mono">
                        <span>Deployments Logged:</span>
                        <span class="font-bold">{{ recentLogs.length }} Runs</span>
                    </div>
                </div>

            </div>

            <!-- 3. Navigation Tabs -->
            <div class="border-b border-slate-200 flex items-center gap-4 text-xs font-bold">
                <button 
                    @click="activeTab = 'repos'"
                    class="pb-2.5 border-b-2 transition cursor-pointer flex items-center gap-1.5"
                    :class="activeTab === 'repos' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-800'"
                >
                    <CodeBracketIcon class="w-4 h-4" />
                    <span>Repositories ({{ repositories.length }})</span>
                </button>

                <button 
                    @click="activeTab = 'logs'"
                    class="pb-2.5 border-b-2 transition cursor-pointer flex items-center gap-1.5"
                    :class="activeTab === 'logs' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-800'"
                >
                    <DocumentTextIcon class="w-4 h-4" />
                    <span>Deployment Logs ({{ recentLogs.length }})</span>
                </button>

                <button 
                    @click="activeTab = 'guide'"
                    class="pb-2.5 border-b-2 transition cursor-pointer flex items-center gap-1.5"
                    :class="activeTab === 'guide' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-800'"
                >
                    <InformationCircleIcon class="w-4 h-4" />
                    <span>CI/CD Setup Guide</span>
                </button>
            </div>

            <!-- 4. TAB 1: Repositories List Table -->
            <div v-if="activeTab === 'repos'" class="bg-white border border-slate-200 rounded-lg shadow-2xs overflow-hidden">
                
                <div class="px-4 py-3 bg-slate-50/80 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <CodeBracketIcon class="w-4 h-4 text-indigo-600" />
                        <span class="text-xs font-bold text-slate-900">Connected Git Repositories</span>
                        <span class="text-[11px] text-slate-400 font-mono">({{ filteredRepos.length }})</span>
                    </div>

                    <div class="relative w-full sm:w-64">
                        <MagnifyingGlassIcon class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-1/2 -translate-y-1/2" />
                        <input 
                            v-model="repoSearch"
                            type="text" 
                            placeholder="Filter repositories..." 
                            class="w-full pl-8 pr-2.5 py-1.5 bg-white border border-slate-300 rounded-[3px] text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-blue-500 font-medium shadow-2xs"
                        />
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="filteredRepos.length === 0" class="p-12 text-center text-slate-500 text-xs">
                    <CodeBracketIcon class="w-12 h-12 text-slate-300 mx-auto mb-2" />
                    <h4 class="text-sm font-bold text-slate-900 mb-1">
                        {{ repoSearch ? 'No repositories match your filter' : 'No Git Repositories Configured' }}
                    </h4>
                    <p class="text-[11px] text-slate-400 max-w-sm mx-auto mb-4">
                        {{ repoSearch ? 'Try clearing your search query.' : 'Connect GitHub, GitLab, or Bitbucket repositories for one-click and automated webhook deployments.' }}
                    </p>
                    <button 
                        @click="openCreateModal"
                        class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold shadow-2xs transition cursor-pointer"
                    >
                        Clone / Add Repository
                    </button>
                </div>

                <!-- Table Content -->
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-700">
                        <thead class="bg-slate-50/90 border-b border-slate-200 text-slate-500 uppercase font-bold tracking-wider text-[10.5px] select-none">
                            <tr>
                                <th class="py-2.5 px-4">Repository</th>
                                <th class="py-2.5 px-4 w-28">Branch</th>
                                <th class="py-2.5 px-4">Deploy Target Path</th>
                                <th class="py-2.5 px-4 w-44">Last Commit / Deployed</th>
                                <th class="py-2.5 px-4 w-28">Auto-Deploy</th>
                                <th class="py-2.5 px-4 w-36 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-medium">
                            <tr v-for="repo in filteredRepos" :key="repo.id" class="hover:bg-slate-50/80 transition-colors">
                                
                                <!-- Repository Info -->
                                <td class="py-3 px-4">
                                    <div class="flex items-center gap-2.5">
                                        <div 
                                            class="w-8 h-8 rounded flex items-center justify-center font-bold text-[10px] uppercase shadow-2xs"
                                            :class="getProviderBadge(repo.provider).class"
                                        >
                                            {{ repo.provider.slice(0, 2) }}
                                        </div>
                                        <div>
                                            <span class="font-bold text-slate-900 block text-xs">{{ repo.name }}</span>
                                            <span class="text-[11px] font-mono text-slate-400 truncate max-w-xs block" :title="repo.repository_url">
                                                {{ repo.repository_url }}
                                            </span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Branch -->
                                <td class="py-3 px-4 font-mono">
                                    <span class="px-2 py-0.5 rounded-[3px] bg-slate-100 text-slate-700 border border-slate-200 font-bold text-[11px]">
                                        {{ repo.branch }}
                                    </span>
                                </td>

                                <!-- Deploy Path -->
                                <td class="py-3 px-4 font-mono text-[11px] text-slate-600 truncate max-w-xs" :title="repo.deploy_path">
                                    {{ repo.deploy_path }}
                                </td>

                                <!-- Last Commit / Deployed -->
                                <td class="py-3 px-4 text-[11px]">
                                    <div v-if="repo.last_commit_hash" class="space-y-0.5">
                                        <div class="flex items-center gap-1 font-mono">
                                            <span class="font-bold text-slate-800">{{ repo.last_commit_hash }}</span>
                                            <span class="text-[10px] text-slate-400 truncate max-w-[100px]">{{ repo.last_commit_message }}</span>
                                        </div>
                                        <div class="text-[10px] text-slate-400">
                                            {{ repo.last_deployed_at }}
                                        </div>
                                    </div>
                                    <span v-else class="text-slate-400">Never deployed</span>
                                </td>

                                <!-- Auto-Deploy Status -->
                                <td class="py-3 px-4">
                                    <span 
                                        class="px-2 py-0.5 rounded text-[10px] font-bold border uppercase font-mono"
                                        :class="repo.auto_deploy ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-600 border-slate-200'"
                                    >
                                        {{ repo.auto_deploy ? 'ENABLED' : 'MANUAL' }}
                                    </span>
                                </td>

                                <!-- Actions -->
                                <td class="py-3 px-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        
                                        <!-- Deploy Now -->
                                        <button 
                                            @click="deployRepoNow(repo)"
                                            :disabled="deployingRepoId === repo.id"
                                            title="Deploy / Pull Now"
                                            class="p-1.5 bg-white hover:bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-[3px] shadow-2xs transition cursor-pointer disabled:opacity-50"
                                        >
                                            <PlayIcon class="w-3.5 h-3.5" :class="{ 'animate-spin': deployingRepoId === repo.id }" />
                                        </button>

                                        <!-- Webhook Setup Info -->
                                        <button 
                                            @click="viewingWebhookRepo = repo; showWebhookModal = true"
                                            title="View Webhook & CI/CD URL"
                                            class="p-1.5 bg-white hover:bg-indigo-50 text-indigo-700 border border-indigo-200 rounded-[3px] shadow-2xs transition cursor-pointer"
                                        >
                                            <LinkIcon class="w-3.5 h-3.5" />
                                        </button>

                                        <!-- Edit Repo -->
                                        <button 
                                            @click="openEditModal(repo)"
                                            title="Edit Repository Settings"
                                            class="p-1.5 bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 rounded-[3px] shadow-2xs transition cursor-pointer"
                                        >
                                            <PencilSquareIcon class="w-3.5 h-3.5 text-blue-600" />
                                        </button>

                                        <!-- Delete Repo -->
                                        <button 
                                            @click="confirmDelete(repo)"
                                            title="Delete Repository"
                                            class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded transition cursor-pointer"
                                        >
                                            <TrashIcon class="w-4 h-4" />
                                        </button>

                                    </div>
                                </td>

                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="px-4 py-2.5 bg-slate-50 border-t border-slate-200 text-[11px] text-slate-500 flex items-center justify-between">
                    <span>Supports GitHub, GitLab, Bitbucket & SSH Git endpoints.</span>
                    <span class="font-mono">User: {{ systemInfo.username }}</span>
                </div>

            </div>

            <!-- 5. TAB 2: Deployment History Logs -->
            <div v-else-if="activeTab === 'logs'" class="bg-white border border-slate-200 rounded-lg shadow-2xs overflow-hidden">
                
                <div class="px-4 py-3 bg-slate-50/80 border-b border-slate-200 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <DocumentTextIcon class="w-4 h-4 text-indigo-600" />
                        <span class="text-xs font-bold text-slate-900">Deployment History Logs</span>
                        <span class="text-[11px] text-slate-400 font-mono">({{ recentLogs.length }} recorded)</span>
                    </div>

                    <button 
                        v-if="recentLogs.length > 0"
                        @click="clearLogs"
                        class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-[3px] text-xs font-bold transition cursor-pointer"
                    >
                        Clear History
                    </button>
                </div>

                <!-- Empty State -->
                <div v-if="recentLogs.length === 0" class="p-12 text-center text-slate-500 text-xs">
                    <DocumentTextIcon class="w-12 h-12 text-slate-300 mx-auto mb-2" />
                    <h4 class="text-sm font-bold text-slate-900 mb-1">No Deployment Logs Recorded</h4>
                    <p class="text-[11px] text-slate-400 max-w-sm mx-auto">
                        When repositories are deployed manually or via webhooks, detailed logs will appear here.
                    </p>
                </div>

                <!-- Table Content -->
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-700">
                        <thead class="bg-slate-50/90 border-b border-slate-200 text-slate-500 uppercase font-bold tracking-wider text-[10.5px]">
                            <tr>
                                <th class="py-2.5 px-4 w-40">Timestamp</th>
                                <th class="py-2.5 px-4 w-48">Repository</th>
                                <th class="py-2.5 px-4 w-28">Trigger</th>
                                <th class="py-2.5 px-4 w-28">Status</th>
                                <th class="py-2.5 px-4 w-28">Duration</th>
                                <th class="py-2.5 px-4">Commit Message</th>
                                <th class="py-2.5 px-4 w-20 text-right">View</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-medium">
                            <tr v-for="log in recentLogs" :key="log.id" class="hover:bg-slate-50/80 transition-colors">
                                
                                <td class="py-2.5 px-4 font-mono text-[11px] text-slate-600">
                                    {{ log.deployed_at }}
                                </td>

                                <td class="py-2.5 px-4 font-bold text-slate-900">
                                    {{ log.repo_name }}
                                </td>

                                <td class="py-2.5 px-4 font-mono text-[11px] uppercase text-slate-500">
                                    {{ log.trigger_type }}
                                </td>

                                <td class="py-2.5 px-4">
                                    <span 
                                        class="px-2 py-0.5 rounded text-[10px] font-bold border uppercase font-mono"
                                        :class="log.status === 'success' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200'"
                                    >
                                        {{ log.status }} (Exit: {{ log.exit_code }})
                                    </span>
                                </td>

                                <td class="py-2.5 px-4 font-mono text-slate-600">
                                    {{ Math.round(log.duration_ms / 1000 * 100) / 100 }}s
                                </td>

                                <td class="py-2.5 px-4 font-mono text-[11px] text-slate-500 max-w-md truncate">
                                    <span v-if="log.commit_hash" class="font-bold text-slate-800 mr-1">{{ log.commit_hash }}:</span>
                                    <span>{{ log.commit_message || '(No commit message)' }}</span>
                                </td>

                                <td class="py-2.5 px-4 text-right">
                                    <button 
                                        @click="viewingLog = log; showLogModal = true"
                                        class="px-2 py-1 bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 rounded-[3px] text-[11px] font-bold transition cursor-pointer"
                                    >
                                        Log
                                    </button>
                                </td>

                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>

            <!-- 6. TAB 3: CI/CD Setup Guide -->
            <div v-else-if="activeTab === 'guide'" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                
                <!-- GitHub Webhook Guide -->
                <div class="bg-white border border-slate-200 rounded-lg p-5 shadow-2xs space-y-3">
                    <h3 class="font-bold text-slate-900 text-sm flex items-center gap-2">
                        <span class="w-6 h-6 rounded bg-slate-900 text-white flex items-center justify-center font-bold text-xs">G</span>
                        <span>GitHub Automated Deployment Setup</span>
                    </h3>
                    <ol class="text-xs text-slate-600 space-y-2 list-decimal list-inside leading-relaxed">
                        <li>Go to your GitHub Repository > <strong>Settings</strong> > <strong>Webhooks</strong> > <strong>Add Webhook</strong>.</li>
                        <li>Paste your repository's <strong>Webhook URL</strong> from the action buttons above.</li>
                        <li>Set Content type to <code>application/json</code>.</li>
                        <li>Select <strong>"Just the push event"</strong> and click <strong>Add Webhook</strong>.</li>
                    </ol>
                </div>

                <!-- Deploy Key Guide -->
                <div class="bg-white border border-slate-200 rounded-lg p-5 shadow-2xs space-y-3">
                    <h3 class="font-bold text-slate-900 text-sm flex items-center gap-2">
                        <KeyIcon class="w-4 h-4 text-indigo-600" />
                        <span>SSH Deploy Key Setup for Private Repositories</span>
                    </h3>
                    <ol class="text-xs text-slate-600 space-y-2 list-decimal list-inside leading-relaxed">
                        <li>Copy the server's <strong>Public SSH Deploy Key</strong> from the top bar.</li>
                        <li>Go to GitHub / GitLab Repository > <strong>Settings</strong> > <strong>Deploy Keys</strong> > <strong>Add Deploy Key</strong>.</li>
                        <li>Paste the key and check <strong>Allow write access</strong> if git tags are pushed.</li>
                    </ol>
                </div>

            </div>

        </div>

        <!-- ========================================================= -->
        <!-- MODALS                                                    -->
        <!-- ========================================================= -->
        <Teleport to="body">
            
            <!-- 1. Clone / Create Repository Modal -->
            <div v-if="showCreateModal || showEditModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-2xs flex items-center justify-center p-4 z-50">
                <div class="bg-white rounded-lg max-w-xl w-full p-5 shadow-2xl space-y-4 border border-slate-200">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <div class="flex items-center gap-2">
                            <CodeBracketIcon class="w-5 h-5 text-blue-600" />
                            <h3 class="font-bold text-slate-900 text-sm">
                                {{ showEditModal ? 'Edit Repository Settings' : 'Clone / Connect Git Repository' }}
                            </h3>
                        </div>
                        <button @click="showCreateModal = false; showEditModal = false" class="text-slate-400 hover:text-slate-600 cursor-pointer">
                            <XMarkIcon class="w-4 h-4" />
                        </button>
                    </div>

                    <form @submit.prevent="showEditModal ? submitEditRepo() : submitCreateRepo()" class="space-y-3.5 text-xs">
                        
                        <!-- Name & Branch Grid -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div class="sm:col-span-2 space-y-1">
                                <label class="block font-bold text-slate-700">Repository Name / Alias</label>
                                <input 
                                    v-model="repoForm.name" 
                                    type="text" 
                                    required 
                                    placeholder="e.g. My Website, API Backend" 
                                    class="w-full bg-white border border-slate-300 rounded-[3px] px-2.5 py-2 text-xs font-bold text-slate-900 focus:ring-1 focus:ring-blue-600" 
                                />
                            </div>

                            <div class="space-y-1">
                                <label class="block font-bold text-slate-700">Branch</label>
                                <input 
                                    v-model="repoForm.branch" 
                                    type="text" 
                                    required 
                                    placeholder="main" 
                                    class="w-full bg-white border border-slate-300 rounded-[3px] px-2.5 py-2 text-xs font-mono font-bold text-slate-900 focus:ring-1 focus:ring-blue-600" 
                                />
                            </div>
                        </div>

                        <!-- Repository URL -->
                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Remote Repository URL (HTTPS or SSH)</label>
                            <input 
                                v-model="repoForm.repository_url" 
                                type="text" 
                                required 
                                placeholder="https://github.com/username/repository.git or git@github.com:..." 
                                class="w-full bg-white border border-slate-300 rounded-[3px] px-2.5 py-2 text-xs font-mono font-bold text-slate-900 focus:ring-1 focus:ring-blue-600" 
                            />
                        </div>

                        <!-- Deploy Target Path -->
                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Deploy Target Path</label>
                            <input 
                                v-model="repoForm.deploy_path" 
                                type="text" 
                                required 
                                placeholder="/var/www/vhosts/..." 
                                class="w-full bg-white border border-slate-300 rounded-[3px] px-2.5 py-2 text-xs font-mono font-bold text-slate-900 focus:ring-1 focus:ring-blue-600" 
                            />
                        </div>

                        <!-- Post Deploy Script -->
                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Post-Deployment Build Script (Optional)</label>
                            <textarea 
                                v-model="repoForm.post_deploy_script"
                                rows="3"
                                placeholder="composer install --no-dev&#10;php artisan migrate --force&#10;npm run build"
                                class="w-full bg-slate-50 border border-slate-300 rounded-[3px] p-2.5 text-xs font-mono text-slate-900 focus:ring-1 focus:ring-blue-600 outline-none leading-relaxed"
                            ></textarea>
                        </div>

                        <!-- Auto-Deploy Checkbox -->
                        <div class="pt-1">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input 
                                    v-model="repoForm.auto_deploy" 
                                    type="checkbox" 
                                    class="w-4 h-4 text-blue-600 rounded-[2px] border-slate-300"
                                />
                                <span class="font-bold text-slate-700">Enable automatic deployment on webhook push event</span>
                            </label>
                        </div>

                        <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                            <button 
                                type="button" 
                                @click="showCreateModal = false; showEditModal = false" 
                                class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-[3px] text-xs font-bold cursor-pointer"
                            >
                                Cancel
                            </button>
                            <button 
                                type="submit" 
                                :disabled="repoForm.processing"
                                class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold shadow-2xs transition cursor-pointer disabled:opacity-50 flex items-center gap-1"
                            >
                                <span>{{ repoForm.processing ? 'Saving...' : (showEditModal ? 'Update Settings' : 'Connect Repository') }}</span>
                            </button>
                        </div>

                    </form>
                </div>
            </div>

            <!-- 2. Webhook Setup Modal -->
            <div v-if="showWebhookModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-2xs flex items-center justify-center p-4 z-50">
                <div class="bg-white rounded-lg max-w-lg w-full p-5 shadow-2xl border border-slate-200 space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <div class="flex items-center gap-2">
                            <LinkIcon class="w-5 h-5 text-indigo-600" />
                            <h3 class="font-bold text-slate-900 text-sm">CI/CD Webhook Configuration</h3>
                        </div>
                        <button @click="showWebhookModal = false" class="text-slate-400 hover:text-slate-600 cursor-pointer">
                            <XMarkIcon class="w-4 h-4" />
                        </button>
                    </div>

                    <div class="space-y-3 text-xs">
                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Webhook Payload URL</label>
                            <div class="flex items-center gap-1.5">
                                <input 
                                    :value="viewingWebhookRepo?.webhook_url" 
                                    readonly 
                                    class="w-full bg-slate-50 border border-slate-300 rounded-[3px] px-2.5 py-1.5 text-xs font-mono text-slate-900 select-all" 
                                />
                                <button 
                                    @click="copyToClipboard(viewingWebhookRepo?.webhook_url, 'modal_webhook')"
                                    class="p-1.5 bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 rounded-[3px] cursor-pointer"
                                    title="Copy Webhook URL"
                                >
                                    <CheckIcon v-if="copiedField === 'modal_webhook'" class="w-4 h-4 text-emerald-600" />
                                    <ClipboardDocumentIcon v-else class="w-4 h-4" />
                                </button>
                            </div>
                        </div>

                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Secret Token</label>
                            <div class="flex items-center gap-1.5">
                                <input 
                                    :value="viewingWebhookRepo?.webhook_secret" 
                                    readonly 
                                    class="w-full bg-slate-50 border border-slate-300 rounded-[3px] px-2.5 py-1.5 text-xs font-mono text-slate-900 select-all" 
                                />
                                <button 
                                    @click="copyToClipboard(viewingWebhookRepo?.webhook_secret, 'modal_secret')"
                                    class="p-1.5 bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 rounded-[3px] cursor-pointer"
                                    title="Copy Secret"
                                >
                                    <CheckIcon v-if="copiedField === 'modal_secret'" class="w-4 h-4 text-emerald-600" />
                                    <ClipboardDocumentIcon v-else class="w-4 h-4" />
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-3 border-t border-slate-100">
                        <button 
                            @click="regenerateWebhook(viewingWebhookRepo)"
                            type="button" 
                            class="text-rose-600 hover:underline text-[11px] font-bold cursor-pointer"
                        >
                            Regenerate Secret
                        </button>

                        <button 
                            @click="showWebhookModal = false" 
                            type="button" 
                            class="px-4 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-[3px] text-xs font-bold cursor-pointer"
                        >
                            Done
                        </button>
                    </div>
                </div>
            </div>

            <!-- 3. SSH Deploy Key Modal -->
            <div v-if="showDeployKeyModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-2xs flex items-center justify-center p-4 z-50">
                <div class="bg-white rounded-lg max-w-lg w-full p-5 shadow-2xl border border-slate-200 space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <div class="flex items-center gap-2">
                            <KeyIcon class="w-5 h-5 text-indigo-600" />
                            <h3 class="font-bold text-slate-900 text-sm">Server Public SSH Deploy Key</h3>
                        </div>
                        <button @click="showDeployKeyModal = false" class="text-slate-400 hover:text-slate-600 cursor-pointer">
                            <XMarkIcon class="w-4 h-4" />
                        </button>
                    </div>

                    <div class="space-y-2 text-xs">
                        <p class="text-slate-500">
                            Add this key to your remote Git provider (GitHub, GitLab, Bitbucket) under <strong>Repository Settings > Deploy Keys</strong> to grant read-only clone access.
                        </p>

                        <div class="bg-slate-900 text-slate-100 p-3 rounded font-mono text-[11px] leading-relaxed break-all relative">
                            {{ systemInfo.deploy_key }}
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                        <button 
                            @click="copyToClipboard(systemInfo.deploy_key, 'modal_deploy_key')"
                            type="button" 
                            class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold shadow-2xs transition cursor-pointer flex items-center gap-1.5"
                        >
                            <CheckIcon v-if="copiedField === 'modal_deploy_key'" class="w-3.5 h-3.5" />
                            <ClipboardDocumentIcon v-else class="w-3.5 h-3.5" />
                            <span>{{ copiedField === 'modal_deploy_key' ? 'Copied!' : 'Copy Deploy Key' }}</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- 4. View Log Output Modal -->
            <div v-if="showLogModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-2xs flex items-center justify-center p-4 z-50">
                <div class="bg-white rounded-lg max-w-xl w-full p-5 shadow-2xl space-y-4 border border-slate-200">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <div class="flex items-center gap-2">
                            <DocumentTextIcon class="w-5 h-5 text-indigo-600" />
                            <h3 class="font-bold text-slate-900 text-sm">Deployment Build Log</h3>
                        </div>
                        <button @click="showLogModal = false" class="text-slate-400 hover:text-slate-600 cursor-pointer">
                            <XMarkIcon class="w-4 h-4" />
                        </button>
                    </div>

                    <div class="space-y-3 text-xs">
                        <div class="flex items-center justify-between bg-slate-50 p-2.5 rounded border border-slate-200/70 font-mono">
                            <span>Repository: {{ viewingLog?.repo_name }} ({{ viewingLog?.branch }})</span>
                            <span 
                                class="font-bold px-2 py-0.5 rounded"
                                :class="viewingLog?.status === 'success' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'"
                            >
                                {{ viewingLog?.status }} (Exit: {{ viewingLog?.exit_code }})
                            </span>
                        </div>

                        <div class="bg-slate-900 text-slate-100 p-3.5 rounded-[3px] font-mono text-[11px] overflow-x-auto leading-relaxed max-h-64 whitespace-pre-wrap">
                            {{ viewingLog?.output || '(No terminal output generated)' }}
                        </div>
                    </div>

                    <div class="flex justify-end pt-2 border-t border-slate-100">
                        <button 
                            type="button" 
                            @click="showLogModal = false" 
                            class="px-4 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-[3px] text-xs font-bold cursor-pointer"
                        >
                            Close
                        </button>
                    </div>
                </div>
            </div>

            <!-- 5. Delete Confirmation Modal -->
            <div v-if="showDeleteModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-2xs flex items-center justify-center p-4 z-50">
                <div class="bg-white rounded-lg max-w-md w-full p-5 shadow-2xl border border-slate-200 space-y-4">
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 rounded-lg bg-rose-50 border border-rose-100 text-rose-600 flex items-center justify-center shrink-0">
                            <TrashIcon class="w-5 h-5 stroke-[1.75]" />
                        </div>
                        <div class="flex-1">
                            <h3 class="text-sm font-black text-slate-900">Delete Repository Connection?</h3>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Are you sure you want to remove <strong class="text-slate-900">{{ deletingRepo?.name }}</strong>? Webhook triggers and automated deployment for this repository will be stopped.
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                        <button 
                            @click="showDeleteModal = false; deletingRepo = null" 
                            type="button" 
                            class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-[3px] text-xs font-bold transition cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button 
                            @click="executeDelete" 
                            type="button"
                            class="px-4 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-[3px] text-xs font-bold shadow-2xs transition cursor-pointer flex items-center gap-1.5"
                        >
                            <TrashIcon class="w-3.5 h-3.5" />
                            <span>Delete Connection</span>
                        </button>
                    </div>
                </div>
            </div>

        </Teleport>

    </AuthenticatedLayout>
</template>
