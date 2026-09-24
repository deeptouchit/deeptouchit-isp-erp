<script setup>
import { ref } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import { 
    GlobeAltIcon, 
    ArrowLeftIcon, 
    ShieldCheckIcon, 
    FolderIcon, 
    CpuChipIcon, 
    ArrowTopRightOnSquareIcon,
    ServerIcon,
    XMarkIcon,
    CheckIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    website: {
        type: Object,
        required: true
    },
    serverIp: {
        type: String,
        default: '103.59.177.138'
    }
})

// PHP Switcher Modal
const showPhpModal = ref(false)
const phpForm = useForm({
    php_version: props.website.php_version || '8.2'
})

const submitPhp = () => {
    phpForm.post(route('websites.change-php', props.website.id), {
        onSuccess: () => {
            showPhpModal.value = false
        }
    })
}

// SSL Issue
const sslForm = useForm({})
const triggerSsl = () => {
    sslForm.post(route('ssl.generate', props.website.id))
}
</script>

<template>
    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto space-y-4">
            <!-- 1. Smart Minimal Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Domains', href: '#' },
                    { label: 'Websites', href: route('websites.index') },
                    { label: website.domain }
                ]"
            >
                <template #actions>
                    <Link 
                        :href="route('websites.index')" 
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        <ArrowLeftIcon class="w-3.5 h-3.5 text-slate-500" />
                        <span>Websites</span>
                    </Link>

                    <a 
                        :href="'http://' + website.domain" 
                        target="_blank" 
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-800 border border-slate-200 rounded-[3px] text-xs font-bold transition flex items-center gap-1.5 shadow-2xs"
                    >
                        <span>Visit Website</span>
                        <ArrowTopRightOnSquareIcon class="w-3.5 h-3.5 text-slate-400" />
                    </a>
                </template>
            </PageHeader>
            
            <!-- VHost Specs Card -->
            <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-xs space-y-6">
                <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center font-bold">
                            <GlobeAltIcon class="w-6 h-6" />
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-slate-900">{{ website.domain }}</h3>
                            <p class="text-xs text-slate-400 font-mono">Nginx VirtualHost Active • FastCGI Microcache</p>
                        </div>
                    </div>
                    <span 
                        class="px-2.5 py-1 rounded-full text-xs font-bold border capitalize"
                        :class="website.status === 'active' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-amber-50 text-amber-700 border-amber-200'"
                    >
                        ● {{ website.status }}
                    </span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs font-medium text-slate-600">
                    <!-- Document Root -->
                    <div class="p-4 bg-slate-50 rounded-xl border border-slate-100 flex flex-col justify-between">
                        <div>
                            <span class="text-[10px] uppercase font-bold text-slate-400 block">Document Root</span>
                            <span class="text-slate-900 font-mono font-bold text-xs mt-1 block truncate">{{ website.document_root }}</span>
                        </div>
                        <Link :href="route('file.browse')" class="text-blue-600 font-bold text-xs mt-2 hover:underline inline-flex items-center gap-1">
                            <span>Open File Manager</span>
                            <span class="text-xs">→</span>
                        </Link>
                    </div>

                    <!-- PHP Version -->
                    <div class="p-4 bg-slate-50 rounded-xl border border-slate-100 flex flex-col justify-between">
                        <div>
                            <span class="text-[10px] uppercase font-bold text-slate-400 block">PHP Execution Pool</span>
                            <span class="text-blue-700 font-mono font-bold text-sm mt-1 block">PHP {{ website.php_version }} FPM</span>
                        </div>
                        <button 
                            @click="showPhpModal = true"
                            class="text-blue-600 font-bold text-xs mt-2 hover:underline text-left cursor-pointer inline-flex items-center gap-1"
                        >
                            <span>Switch PHP Version</span>
                            <span class="text-xs">→</span>
                        </button>
                    </div>

                    <!-- SSL Certificate -->
                    <div class="p-4 bg-slate-50 rounded-xl border border-slate-100 flex flex-col justify-between">
                        <div>
                            <span class="text-[10px] uppercase font-bold text-slate-400 block">SSL Certificate</span>
                            <span v-if="website.ssl_status === 'active'" class="text-emerald-600 font-bold text-sm mt-1 flex items-center gap-1">
                                <ShieldCheckIcon class="w-4 h-4 text-emerald-600" /> Let's Encrypt Active
                            </span>
                            <span v-else class="text-amber-700 font-bold text-xs mt-1 block">
                                SSL Pending Issue
                            </span>
                        </div>
                        <button 
                            v-if="website.ssl_status !== 'active'"
                            @click="triggerSsl"
                            :disabled="sslForm.processing"
                            class="text-emerald-700 font-bold text-xs mt-2 hover:underline text-left cursor-pointer inline-flex items-center gap-1"
                        >
                            <span>{{ sslForm.processing ? 'Generating SSL...' : 'Issue Free SSL Now' }}</span>
                            <span class="text-xs">→</span>
                        </button>
                        <span v-else class="text-slate-400 text-[11px] mt-2">Auto-renews every 90 days</span>
                    </div>
                </div>
            </div>

            <!-- Quick Management Shortcuts -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <Link :href="route('file.browse')" class="bg-white border border-slate-200 hover:border-blue-300 p-6 rounded-xl shadow-xs transition group">
                    <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center mb-3 group-hover:bg-blue-600 group-hover:text-white transition">
                        <FolderIcon class="w-5 h-5" />
                    </div>
                    <h4 class="font-bold text-slate-900 text-sm mb-1">Open Web File Manager</h4>
                    <p class="text-xs text-slate-500">Upload HTML/PHP files, unpack ZIP archives, and manage permissions in public_html</p>
                </Link>

                <Link :href="route('databases.index')" class="bg-white border border-slate-200 hover:border-blue-300 p-6 rounded-xl shadow-xs transition group">
                    <div class="w-10 h-10 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center mb-3 group-hover:bg-purple-600 group-hover:text-white transition">
                        <CpuChipIcon class="w-5 h-5" />
                    </div>
                    <h4 class="font-bold text-slate-900 text-sm mb-1">Manage MySQL Databases</h4>
                    <p class="text-xs text-slate-500">Create databases and users, and access phpMyAdmin with Single Sign-On</p>
                </Link>
            </div>

        </div>

        <!-- PHP Switcher Modal -->
        <div v-if="showPhpModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 z-50">
            <div class="bg-white rounded-xl max-w-md w-full p-6 shadow-2xl space-y-4 border border-slate-100">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div class="flex items-center gap-2">
                        <CpuChipIcon class="w-5 h-5 text-blue-600" />
                        <h3 class="font-bold text-slate-900 text-sm">Switch PHP Engine</h3>
                    </div>
                    <button @click="showPhpModal = false" class="text-slate-400 hover:text-slate-600 p-1">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitPhp" class="space-y-4">
                    <div class="grid grid-cols-2 gap-2">
                        <label 
                            v-for="ver in ['8.1', '8.2', '8.3', '8.5']" 
                            :key="ver"
                            :class="[
                                'p-3 rounded-lg border text-center cursor-pointer transition font-bold text-xs flex flex-col items-center justify-center gap-0.5',
                                phpForm.php_version === ver 
                                    ? 'bg-blue-50 border-blue-600 text-blue-700 ring-2 ring-blue-600/20' 
                                    : 'border-slate-200 hover:bg-slate-50 text-slate-700'
                            ]"
                        >
                            <input type="radio" v-model="phpForm.php_version" :value="ver" class="hidden" />
                            <span>PHP {{ ver }} FPM</span>
                        </label>
                    </div>

                    <div class="flex justify-end gap-2.5 pt-3 border-t border-slate-100">
                        <button type="button" @click="showPhpModal = false" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-bold">
                            Cancel
                        </button>
                        <button type="submit" :disabled="phpForm.processing" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-bold">
                            {{ phpForm.processing ? 'Saving...' : 'Apply PHP' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </AuthenticatedLayout>
</template>
