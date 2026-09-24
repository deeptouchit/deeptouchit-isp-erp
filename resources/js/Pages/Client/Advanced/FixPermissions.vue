<script setup>
import { ref } from 'vue'
import { Head, useForm, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import { 
    WrenchScrewdriverIcon, 
    ShieldCheckIcon,
    FolderIcon,
    UserCircleIcon,
    CheckIcon,
    CheckCircleIcon,
    CommandLineIcon,
    InformationCircleIcon,
    ExclamationTriangleIcon,
    XMarkIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    domains: {
        type: Array,
        default: () => ['somitysoft.com']
    },
    stats: {
        type: Object,
        default: () => ({
            doc_root: '/var/www/vhosts/somitysoft/somitysoft.com/public_html',
            home_prefix: '/home/somitysoft',
            domain: 'somitysoft.com',
            username: 'somitysoft',
            public_html_exists: true,
            default_dir_perm: '0755 (drwxr-xr-x)',
            default_file_perm: '0644 (-rw-r--r--)',
            secure_file_perm: '0600 (-rw-------)',
            web_owner: 'www-data:www-data'
        })
    },
    sessionFlash: {
        type: Object,
        default: () => ({})
    }
})

// Form (matches user uploaded screenshot)
const form = useForm({
    domain: props.domains[0] || '',
    agree_terms: false,
})

const submitFix = () => {
    form.post(route('advanced.fix-permissions.execute'), {
        preserveScroll: true,
    })
}
</script>

<template>
    <Head title="Fix File Permissions & Ownership - DeepTouch Host" />

    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto space-y-4">
            
            <!-- 1. Page Header with Breadcrumbs -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Advanced Tools', href: '#' },
                    { label: 'Fix File Permissions & Ownership' }
                ]"
                :show-refresh="true"
                @refreshed="router.reload({ preserveScroll: true })"
            />

            <!-- Execution Result Terminal Console Box (If executed) -->
            <div 
                v-if="sessionFlash?.fix_output" 
                class="bg-slate-900 border border-slate-800 rounded-lg p-4 shadow-xl space-y-2.5 text-slate-100 animate-in fade-in duration-200"
            >
                <div class="flex items-center justify-between border-b border-slate-800 pb-2">
                    <div class="flex items-center gap-2">
                        <CommandLineIcon class="w-4 h-4 text-emerald-400" />
                        <span class="text-xs font-bold font-mono">File Permissions & Ownership Diagnostic Log</span>
                        <span class="text-[10px] font-mono font-bold px-1.5 py-0.2 rounded uppercase bg-emerald-950 text-emerald-400 border border-emerald-800">
                            FINISHED ({{ Math.round(sessionFlash.duration_ms / 1000 * 100) / 100 }}s)
                        </span>
                    </div>

                    <button 
                        @click="sessionFlash.fix_output = null" 
                        class="text-slate-400 hover:text-white cursor-pointer"
                    >
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <pre class="font-mono text-xs text-slate-300 bg-slate-950/80 p-3 rounded overflow-x-auto leading-relaxed max-h-56 whitespace-pre-wrap">{{ sessionFlash.fix_output }}</pre>
            </div>

            <!-- 2. Top 3 KPI / Health Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                
                <!-- Card 1: Default Permissions Policy -->
                <div class="bg-white border border-slate-200 rounded-lg p-4 shadow-2xs space-y-2.5">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                            <ShieldCheckIcon class="w-4 h-4 text-emerald-600" />
                            <span>Standard Permissions Policy</span>
                        </span>
                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-200 font-mono">
                            POSIX 755 / 644
                        </span>
                    </div>

                    <div class="space-y-1 text-xs font-mono">
                        <div class="flex items-center justify-between text-slate-600">
                            <span>Directories:</span>
                            <strong class="text-slate-900">{{ stats.default_dir_perm }}</strong>
                        </div>
                        <div class="flex items-center justify-between text-slate-600">
                            <span>Files:</span>
                            <strong class="text-slate-900">{{ stats.default_file_perm }}</strong>
                        </div>
                        <div class="flex items-center justify-between text-slate-600">
                            <span>Sensitive (.env):</span>
                            <strong class="text-indigo-700 font-bold">{{ stats.secure_file_perm }}</strong>
                        </div>
                    </div>

                    <div class="flex items-center justify-between text-[11px] text-slate-500 pt-1 border-t border-slate-100 font-mono">
                        <span>Security Level:</span>
                        <strong class="text-emerald-700">Hardened POSIX</strong>
                    </div>
                </div>

                <!-- Card 2: Web Server Ownership -->
                <div class="bg-white border border-slate-200 rounded-lg p-4 shadow-2xs space-y-2.5">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                            <UserCircleIcon class="w-4 h-4 text-indigo-600" />
                            <span>Target Ownership</span>
                        </span>
                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-indigo-50 text-indigo-700 border border-indigo-200 font-mono">
                            WEB OWNER
                        </span>
                    </div>

                    <div>
                        <div class="flex items-baseline gap-2">
                            <span class="text-xl font-black text-slate-900 font-mono">{{ stats.web_owner }}</span>
                        </div>
                        <p class="text-[11px] text-slate-500 mt-1 font-mono">
                            User: <strong>{{ stats.username }}</strong> • Web Server Group: <strong>www-data</strong>
                        </p>
                    </div>

                    <div class="flex items-center justify-between text-[11px] text-slate-500 pt-1 border-t border-slate-100 font-mono">
                        <span>PHP-FPM Execution:</span>
                        <strong class="text-indigo-700">Unrestricted</strong>
                    </div>
                </div>

                <!-- Card 3: Document Root Status -->
                <div class="bg-gradient-to-br from-indigo-50/70 to-blue-50/50 border border-indigo-100 rounded-lg p-4 shadow-2xs flex flex-col justify-between space-y-2.5">
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-indigo-900 flex items-center gap-1.5">
                                <FolderIcon class="w-4 h-4 text-indigo-600" />
                                <span>public_html Status</span>
                            </span>
                            <span 
                                class="text-[10px] font-bold px-1.5 py-0.5 rounded font-mono uppercase"
                                :class="stats.public_html_exists ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'"
                            >
                                {{ stats.public_html_exists ? 'FOUND' : 'MISSING' }}
                            </span>
                        </div>

                        <p class="text-[11px] font-mono text-slate-700 mt-2 truncate" :title="stats.doc_root">
                            {{ stats.doc_root }}
                        </p>
                    </div>

                    <div class="pt-2 border-t border-indigo-100 flex items-center justify-between text-[11px] text-indigo-900 font-mono">
                        <span>Auto-Recreate:</span>
                        <span class="font-bold text-emerald-700">Supported</span>
                    </div>
                </div>

            </div>

            <!-- 3. MAIN CARD: Fix File Ownership (Matches User Screenshot Exactly) -->
            <div class="bg-white border border-slate-200 rounded-lg p-6 shadow-2xs space-y-4">
                
                <h3 class="text-base font-bold text-slate-900">
                    Fix File Ownership
                </h3>

                <p class="text-xs text-slate-600 leading-relaxed">
                    If you have any problems with editing, deleting files or you have accidentally deleted the public_html folder, please run this tool.
                </p>

                <!-- Domain selector if multiple -->
                <div v-if="domains.length > 1" class="space-y-1 pt-1">
                    <label class="block text-xs font-bold text-slate-700">Target Website Domain</label>
                    <select 
                        v-model="form.domain"
                        class="w-full sm:w-80 bg-white border border-slate-300 rounded-[3px] px-2.5 py-1.5 text-xs font-bold text-slate-900 focus:ring-1 focus:ring-blue-600 cursor-pointer shadow-2xs"
                    >
                        <option v-for="d in domains" :key="d" :value="d">{{ d }}</option>
                    </select>
                </div>

                <form @submit.prevent="submitFix" class="space-y-4 pt-1">
                    
                    <!-- Checkbox (Matches User Screenshot Exactly) -->
                    <label class="flex items-center gap-2.5 cursor-pointer select-none group">
                        <div 
                            @click.prevent="form.agree_terms = !form.agree_terms"
                            class="w-5 h-5 rounded-[4px] border flex items-center justify-center transition-all shrink-0"
                            :class="form.agree_terms 
                                ? 'bg-[#6348eb] border-[#6348eb] text-white shadow-2xs' 
                                : 'border-slate-400 bg-white group-hover:border-[#6348eb]'"
                        >
                            <CheckIcon v-if="form.agree_terms" class="w-3.5 h-3.5 stroke-[3]" />
                        </div>
                        <span class="text-xs text-slate-800 font-medium">
                            I understand that permissions of my files will be set to default values.
                        </span>
                    </label>

                    <!-- Execute Button (Matches User Screenshot Exactly) -->
                    <div class="pt-1">
                        <button 
                            type="submit" 
                            :disabled="!form.agree_terms || form.processing"
                            class="px-6 py-2 bg-[#6348eb] hover:bg-[#5237de] text-white rounded-lg text-xs font-bold shadow-2xs transition cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-1.5"
                        >
                            <WrenchScrewdriverIcon class="w-3.5 h-3.5" :class="{ 'animate-spin': form.processing }" />
                            <span>{{ form.processing ? 'Executing Repair...' : 'Execute' }}</span>
                        </button>
                    </div>

                </form>

            </div>

            <!-- 4. Reference & POSIX Permissions Guide -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                
                <div class="bg-white border border-slate-200 rounded-lg p-5 shadow-2xs space-y-3">
                    <h4 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                        <ShieldCheckIcon class="w-4 h-4 text-emerald-600" />
                        <span>Why 755 & 644 Permissions are Standard</span>
                    </h4>
                    <ul class="text-xs text-slate-600 space-y-2 leading-relaxed">
                        <li>
                            <strong>Folders 0755 (drwxr-xr-x):</strong> Allows the owner to create, read, and delete files inside, while granting web visitors read-only access to execute scripts.
                        </li>
                        <li>
                            <strong>Files 0644 (-rw-r--r--):</strong> Gives the file owner full read and write capabilities, while public web visitors can only read the content without altering it.
                        </li>
                        <li>
                            <span class="text-rose-600 font-bold">Never use 777:</span> Full 777 permissions allow any malicious actor or compromised script on the server to overwrite your files.
                        </li>
                    </ul>
                </div>

                <div class="bg-white border border-slate-200 rounded-lg p-5 shadow-2xs space-y-3">
                    <h4 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                        <FolderIcon class="w-4 h-4 text-indigo-600" />
                        <span>Accidental public_html Recovery</span>
                    </h4>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        If your <code>public_html</code> folder was accidentally removed through FTP or File Manager, running this tool will automatically recreate the root directory with correct 0755 permissions and a welcome placeholder.
                    </p>
                </div>

            </div>

        </div>

    </AuthenticatedLayout>
</template>
