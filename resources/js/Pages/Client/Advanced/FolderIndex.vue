<script setup>
import { ref, computed } from 'vue'
import { Head, useForm, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import { 
    FolderIcon, 
    FolderMinusIcon, 
    FolderPlusIcon,
    ShieldCheckIcon,
    TrashIcon, 
    PencilSquareIcon,
    CheckIcon,
    SparklesIcon,
    InformationCircleIcon,
    MagnifyingGlassIcon,
    GlobeAltIcon,
    CommandLineIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    rules: {
        type: Array,
        default: () => []
    },
    domains: {
        type: Array,
        default: () => ['somitysoft.com']
    },
    stats: {
        type: Object,
        default: () => ({
            total_rules: 0,
            root_indexing: 'no_index',
            home_prefix: '/home/somitysoft',
            domain: 'somitysoft.com',
            server_engine: 'Nginx autoindex & Apache mod_autoindex'
        })
    }
})

// Search
const searchQuery = ref('')
const filteredRules = computed(() => {
    if (!searchQuery.value.trim()) return props.rules
    const q = searchQuery.value.toLowerCase().trim()
    return props.rules.filter(r => 
        r.path.toLowerCase().includes(q) ||
        r.domain.toLowerCase().includes(q) ||
        r.indexing_type.toLowerCase().includes(q)
    )
})

// Form (matches user uploaded screenshot)
const form = useForm({
    domain: props.domains[0] || '',
    path: '/',
    is_root: false,
    indexing_type: 'no_index', // 'no_index' | 'default' | 'standard' | 'fancy'
})

// Indexing Type Options
const indexingOptions = [
    {
        value: 'no_index',
        label: 'No index',
        description: 'Disables directory listing. Returns 403 Forbidden if no index.html or index.php exists (Recommended for security).'
    },
    {
        value: 'default',
        label: 'Default system settings',
        description: 'Inherits the global server-level default indexing configuration.'
    },
    {
        value: 'standard',
        label: 'Standard indexing (filename only)',
        description: 'Displays a basic plain HTML file list showing only filenames.'
    },
    {
        value: 'fancy',
        label: 'Fancy indexing (filename and description)',
        description: 'Displays a rich table with file icons, sizes, last-modified dates, and sortable columns.'
    }
]

const submitForm = () => {
    form.post(route('advanced.folder-index.save'), {
        preserveScroll: true,
        onSuccess: () => {
            if (!form.is_root) {
                form.path = '/'
            }
        }
    })
}

const editRule = (rule) => {
    form.domain = rule.domain === 'All Websites' ? (props.domains[0] || '') : rule.domain
    form.path = rule.path
    form.is_root = rule.is_root
    form.indexing_type = rule.indexing_type
    window.scrollTo({ top: 0, behavior: 'smooth' })
}

const deleteRule = (rule) => {
    if (!confirm(`Delete custom indexing rule for '${rule.path}'?`)) return
    router.delete(route('advanced.folder-index.delete', rule.id), {
        preserveScroll: true,
    })
}

const getBadgeForType = (type) => {
    switch(type) {
        case 'no_index': return { label: 'No Index (Secure)', class: 'bg-emerald-50 text-emerald-700 border-emerald-200' }
        case 'standard': return { label: 'Standard Index', class: 'bg-blue-50 text-blue-700 border-blue-200' }
        case 'fancy': return { label: 'Fancy Indexing', class: 'bg-indigo-50 text-indigo-700 border-indigo-200' }
        default: return { label: 'Default System', class: 'bg-slate-100 text-slate-700 border-slate-200' }
    }
}
</script>

<template>
    <Head title="Directory Indexing Manager - DeepTouch Host" />

    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto space-y-4">
            
            <!-- 1. Page Header with Breadcrumbs & Refresh -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Advanced Tools', href: '#' },
                    { label: 'Directory Indexing Manager' }
                ]"
                :show-refresh="true"
                @refreshed="router.reload({ preserveScroll: true })"
            />

            <!-- 2. Top 3 KPI / Security Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                
                <!-- Card 1: Root Indexing Status -->
                <div class="bg-white border border-slate-200 rounded-lg p-4 shadow-2xs space-y-2.5">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                            <ShieldCheckIcon class="w-4 h-4 text-emerald-600" />
                            <span>Website Root Indexing</span>
                        </span>
                        <span 
                            class="text-[10px] font-bold px-1.5 py-0.5 rounded font-mono uppercase"
                            :class="stats.root_indexing === 'no_index' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200'"
                        >
                            {{ stats.root_indexing === 'no_index' ? 'PROTECTED' : stats.root_indexing.toUpperCase() }}
                        </span>
                    </div>

                    <div>
                        <div class="flex items-baseline gap-2">
                            <span class="text-2xl font-black text-slate-900 font-mono">{{ stats.root_indexing === 'no_index' ? 'NO INDEX' : stats.root_indexing }}</span>
                        </div>
                        <p class="text-[11px] text-slate-500 mt-1">
                            Directories without <code>index.html</code>/<code>index.php</code> will not expose their file list.
                        </p>
                    </div>

                    <div class="flex items-center justify-between text-[11px] text-slate-500 pt-1 border-t border-slate-100 font-mono">
                        <span>Scope:</span>
                        <strong class="text-slate-800">{{ stats.domain }}</strong>
                    </div>
                </div>

                <!-- Card 2: Custom Rules Configured -->
                <div class="bg-white border border-slate-200 rounded-lg p-4 shadow-2xs space-y-2.5">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                            <FolderIcon class="w-4 h-4 text-indigo-600" />
                            <span>Configured Folder Rules</span>
                        </span>
                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-indigo-50 text-indigo-700 border border-indigo-200 font-mono">
                            {{ rules.length }} RULES
                        </span>
                    </div>

                    <div>
                        <div class="flex items-baseline gap-2">
                            <span class="text-2xl font-black text-slate-900 font-mono">{{ rules.length }}</span>
                            <span class="text-xs text-slate-400 font-bold font-mono">Directory Overrides</span>
                        </div>
                        <p class="text-[11px] text-slate-500 mt-1">
                            Fine-tune indexing for specific subfolders like <code>/downloads</code> or <code>/files</code>.
                        </p>
                    </div>

                    <div class="flex items-center justify-between text-[11px] text-slate-500 pt-1 border-t border-slate-100 font-mono">
                        <span>Directory Root:</span>
                        <strong class="text-slate-800 truncate max-w-[150px]">{{ stats.home_prefix }}</strong>
                    </div>
                </div>

                <!-- Card 3: Web Server Engine -->
                <div class="bg-gradient-to-br from-indigo-50/70 to-blue-50/50 border border-indigo-100 rounded-lg p-4 shadow-2xs flex flex-col justify-between space-y-2.5">
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-indigo-900 flex items-center gap-1.5">
                                <SparklesIcon class="w-4 h-4 text-indigo-600" />
                                <span>Web Server Indexing Directives</span>
                            </span>
                            <span class="text-[10px] font-bold px-1.5 py-0.5 bg-indigo-100 text-indigo-800 rounded font-mono">
                                NGINX & APACHE
                            </span>
                        </div>
                        <p class="text-[11px] text-slate-600 mt-1">
                            Directly maps to Nginx <code>autoindex on/off</code> and Apache <code>Options -Indexes / +FancyIndexing</code>.
                        </p>
                    </div>

                    <div class="pt-2 border-t border-indigo-100 flex items-center justify-between text-[11px] text-indigo-900 font-mono">
                        <span>Engine:</span>
                        <span class="font-bold text-slate-800">mod_autoindex</span>
                    </div>
                </div>

            </div>

            <!-- 3. MAIN FORM CARD: Setup Indexing Type (Matches Screenshot Exactly) -->
            <div class="bg-white border border-slate-200 rounded-lg p-6 shadow-2xs space-y-5">
                
                <h3 class="text-base font-bold text-slate-900">
                    Setup indexing type
                </h3>

                <form @submit.prevent="submitForm" class="space-y-4">
                    
                    <!-- Domain Selector if multiple -->
                    <div v-if="domains.length > 1" class="space-y-1">
                        <label class="block text-xs font-bold text-slate-700">Select Website Domain</label>
                        <select 
                            v-model="form.domain"
                            class="w-full sm:w-80 bg-white border border-slate-300 rounded-[3px] px-2.5 py-1.5 text-xs font-bold text-slate-900 focus:ring-1 focus:ring-blue-600 cursor-pointer shadow-2xs"
                        >
                            <option v-for="d in domains" :key="d" :value="d">{{ d }}</option>
                        </select>
                    </div>

                    <!-- Directory Prefix + Input (Matches Screenshot) -->
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-slate-900">Directory</label>
                        
                        <div class="flex items-center rounded-lg border border-slate-300 overflow-hidden shadow-2xs focus-within:ring-1 focus-within:ring-blue-500 focus-within:border-blue-500">
                            <!-- Disabled Prefix Box -->
                            <div class="bg-slate-100/90 text-slate-500 px-4 py-2.5 text-xs font-mono font-medium border-r border-slate-200 select-none">
                                {{ stats.home_prefix }}
                            </div>

                            <!-- Path Input -->
                            <input 
                                v-model="form.path" 
                                type="text" 
                                :disabled="form.is_root"
                                placeholder="/" 
                                class="flex-1 px-3.5 py-2.5 text-xs font-mono text-slate-900 placeholder-slate-400 bg-white focus:outline-none disabled:bg-slate-50 disabled:text-slate-400"
                            />
                        </div>
                    </div>

                    <!-- Checkbox: Apply for the whole website (Matches Screenshot) -->
                    <div class="pt-1">
                        <label class="flex items-center gap-2.5 cursor-pointer select-none group">
                            <div 
                                @click.prevent="form.is_root = !form.is_root; if(form.is_root) form.path = '/';"
                                class="w-5 h-5 rounded-[4px] border flex items-center justify-center transition-all"
                                :class="form.is_root 
                                    ? 'bg-blue-600 border-blue-600 text-white shadow-2xs' 
                                    : 'border-slate-400 bg-white group-hover:border-blue-500'"
                            >
                                <CheckIcon v-if="form.is_root" class="w-3.5 h-3.5 stroke-[3]" />
                            </div>
                            <span class="text-xs text-slate-800 font-medium">Apply for the whole website</span>
                        </label>
                    </div>

                    <!-- Radio Buttons: Choose a new indexing type (Matches Screenshot) -->
                    <div class="space-y-3 pt-2">
                        <label class="block text-xs font-bold text-slate-900">Choose a new indexing type</label>

                        <div class="space-y-2.5">
                            <label 
                                v-for="opt in indexingOptions" 
                                :key="opt.value"
                                class="flex items-start gap-3 cursor-pointer select-none group"
                            >
                                <div 
                                    @click.prevent="form.indexing_type = opt.value"
                                    class="w-5 h-5 rounded-full border flex items-center justify-center transition-all mt-0.5 shrink-0"
                                    :class="form.indexing_type === opt.value 
                                        ? 'border-blue-600 bg-white' 
                                        : 'border-slate-400 bg-white group-hover:border-blue-400'"
                                >
                                    <div 
                                        v-if="form.indexing_type === opt.value" 
                                        class="w-2.5 h-2.5 rounded-full bg-blue-600"
                                    ></div>
                                </div>

                                <div>
                                    <span class="text-xs font-medium text-slate-900 block">{{ opt.label }}</span>
                                    <span class="text-[11px] text-slate-400 block">{{ opt.description }}</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Add Button (Matches Screenshot) -->
                    <div class="pt-3">
                        <button 
                            type="submit" 
                            :disabled="form.processing"
                            class="px-5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-800 border border-slate-200 rounded-lg text-xs font-bold shadow-2xs transition cursor-pointer disabled:opacity-50 flex items-center gap-1.5"
                        >
                            <span>{{ form.processing ? 'Saving...' : 'Add' }}</span>
                        </button>
                    </div>

                </form>

            </div>

            <!-- 4. ACTIVE RULES TABLE -->
            <div class="bg-white border border-slate-200 rounded-lg shadow-2xs overflow-hidden">
                
                <div class="px-4 py-3 bg-slate-50/80 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <FolderIcon class="w-4 h-4 text-indigo-600" />
                        <span class="text-xs font-bold text-slate-900">Configured Directory Indexing Rules</span>
                        <span class="text-[11px] text-slate-400 font-mono">({{ filteredRules.length }})</span>
                    </div>

                    <div class="relative w-full sm:w-64">
                        <MagnifyingGlassIcon class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-1/2 -translate-y-1/2" />
                        <input 
                            v-model="searchQuery"
                            type="text" 
                            placeholder="Filter by directory path..." 
                            class="w-full pl-8 pr-2.5 py-1.5 bg-white border border-slate-300 rounded-[3px] text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-blue-500 font-medium shadow-2xs"
                        />
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="filteredRules.length === 0" class="p-10 text-center text-slate-500 text-xs">
                    <FolderMinusIcon class="w-10 h-10 text-slate-300 mx-auto mb-2" />
                    <h4 class="text-xs font-bold text-slate-900 mb-1">
                        {{ searchQuery ? 'No rules match your filter' : 'No Custom Directory Index Rules Added' }}
                    </h4>
                    <p class="text-[11px] text-slate-400 max-w-sm mx-auto">
                        By default, directory indexing is disabled (No index) across the website to prevent unauthorized file snooping.
                    </p>
                </div>

                <!-- Table Content -->
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-700">
                        <thead class="bg-slate-50/90 border-b border-slate-200 text-slate-500 uppercase font-bold tracking-wider text-[10.5px] select-none">
                            <tr>
                                <th class="py-2.5 px-4">Directory Path</th>
                                <th class="py-2.5 px-4 w-44">Domain Scope</th>
                                <th class="py-2.5 px-4 w-52">Indexing Type</th>
                                <th class="py-2.5 px-4 w-36">Created At</th>
                                <th class="py-2.5 px-4 w-28 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-medium">
                            <tr v-for="r in filteredRules" :key="r.id" class="hover:bg-slate-50/80 transition-colors">
                                
                                <td class="py-3 px-4 font-mono font-bold text-slate-900 text-[11.5px]">
                                    <div class="flex items-center gap-1.5">
                                        <FolderIcon class="w-4 h-4 text-slate-400" />
                                        <span>{{ r.path }}</span>
                                        <span v-if="r.is_root" class="px-1.5 py-0.2 rounded bg-indigo-50 text-indigo-700 text-[10px] font-mono font-bold border border-indigo-200">WHOLE SITE</span>
                                    </div>
                                </td>

                                <td class="py-3 px-4 text-slate-600 font-mono text-[11px]">
                                    {{ r.domain }}
                                </td>

                                <td class="py-3 px-4">
                                    <span 
                                        class="px-2 py-0.5 rounded text-[10.5px] font-bold border font-mono"
                                        :class="getBadgeForType(r.indexing_type).class"
                                    >
                                        {{ getBadgeForType(r.indexing_type).label }}
                                    </span>
                                </td>

                                <td class="py-3 px-4 text-slate-500 font-mono text-[11px]">
                                    {{ r.created_at }}
                                </td>

                                <td class="py-3 px-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button 
                                            @click="editRule(r)"
                                            title="Edit Rule"
                                            class="p-1.5 bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 rounded-[3px] shadow-2xs transition cursor-pointer"
                                        >
                                            <PencilSquareIcon class="w-3.5 h-3.5 text-blue-600" />
                                        </button>
                                        <button 
                                            @click="deleteRule(r)"
                                            title="Delete Rule"
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
                    <span>Compliant with standard Apache .htaccess & Nginx server blocks.</span>
                    <span class="font-mono">Security Profile: Maximum Hardening</span>
                </div>

            </div>

        </div>

    </AuthenticatedLayout>
</template>
