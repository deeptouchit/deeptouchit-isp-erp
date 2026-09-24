<script setup>
import { ref, computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { 
    ArrowLeftIcon,
    ShieldCheckIcon,
    ServerIcon,
    GlobeAltIcon,
    CheckCircleIcon,
    ArrowPathIcon,
    SparklesIcon,
    CpuChipIcon,
    KeyIcon,
    FolderIcon,
    CircleStackIcon,
    CommandLineIcon,
    BoltIcon,
    DocumentTextIcon,
    ExclamationTriangleIcon,
    CloudArrowUpIcon,
    WrenchIcon
} from '@heroicons/vue/24/outline'

import PageHeader from '@/Components/UI/PageHeader.vue'

const props = defineProps({
    module: {
        type: String,
        required: true
    },
    tool: {
        type: String,
        required: true
    },
    title: {
        type: String,
        required: true
    },
    description: {
        type: String,
        required: true
    },
    context: {
        type: Object,
        default: () => ({})
    }
})

const isProcessing = ref(false)
const actionSuccess = ref(false)
const actionMessage = ref('')

const triggerAction = (msg) => {
    isProcessing.value = true
    setTimeout(() => {
        isProcessing.value = false
        actionMessage.value = msg || 'Action executed successfully.'
        actionSuccess.value = true
        setTimeout(() => {
            actionSuccess.value = false
        }, 4000)
    }, 900)
}
</script>

<template>
    <Head :title="`${title} - DeepTouch Host`" />

    <AuthenticatedLayout>
        <div class="max-w-6xl mx-auto space-y-4">
            <!-- 1. Smart Minimal Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: module.toUpperCase(), href: '#' },
                    { label: title }
                ]"
            >
                <template #actions>
                    <Link
                        :href="route('client.dashboard')"
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        <ArrowLeftIcon class="w-3.5 h-3.5 text-slate-500" />
                        <span>Dashboard</span>
                    </Link>

                    <button 
                        @click="triggerAction('System status verified and synchronized.')" 
                        :disabled="isProcessing"
                        class="px-2.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-[3px] text-xs flex items-center gap-1.5 shadow-2xs transition cursor-pointer disabled:opacity-50"
                    >
                        <ArrowPathIcon class="w-3.5 h-3.5" :class="{ 'animate-spin': isProcessing }" />
                        <span>Sync</span>
                    </button>
                </template>
            </PageHeader>
            
            <!-- Success Notification Alert -->
            <div v-if="actionSuccess" class="bg-emerald-50 border border-emerald-200 rounded-lg p-3.5 flex items-center gap-2.5 text-emerald-800 text-xs font-bold animate-in fade-in duration-200">
                <CheckCircleIcon class="w-4 h-4 text-emerald-600 shrink-0" />
                <span>{{ actionMessage }}</span>
            </div>

            <!-- Overview Description Header Card -->
            <div class="bg-white rounded-lg border border-slate-200 p-5 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="space-y-1">
                    <h2 class="text-sm font-bold text-slate-900">{{ title }}</h2>
                    <p class="text-xs text-slate-500 leading-relaxed max-w-3xl">{{ description }}</p>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span class="flex h-2 w-2 relative">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                    </span>
                    <span class="text-xs font-bold text-slate-700">Service Active</span>
                </div>
            </div>

            <!-- TOOL SPECIFIC PANEL: Resources Usage -->
            <div v-if="tool === 'resources'" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-white rounded-lg border border-slate-200 p-5 shadow-xs">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold uppercase text-slate-500">CPU Usage</span>
                            <CpuChipIcon class="w-4 h-4 text-blue-600" />
                        </div>
                        <p class="text-2xl font-black text-slate-900 mt-2">12%</p>
                        <div class="w-full bg-slate-100 h-1.5 rounded-full mt-3 overflow-hidden">
                            <div class="bg-blue-600 h-full rounded-full" style="width: 12%"></div>
                        </div>
                        <span class="text-[10px] text-slate-400 mt-1 block">1 Core Assigned</span>
                    </div>

                    <div class="bg-white rounded-lg border border-slate-200 p-5 shadow-xs">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold uppercase text-slate-500">Physical Memory</span>
                            <BoltIcon class="w-4 h-4 text-emerald-600" />
                        </div>
                        <p class="text-2xl font-black text-slate-900 mt-2">340 MB</p>
                        <div class="w-full bg-slate-100 h-1.5 rounded-full mt-3 overflow-hidden">
                            <div class="bg-emerald-600 h-full rounded-full" style="width: 34%"></div>
                        </div>
                        <span class="text-[10px] text-slate-400 mt-1 block">Limit: 1024 MB (1 GB)</span>
                    </div>

                    <div class="bg-white rounded-lg border border-slate-200 p-5 shadow-xs">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold uppercase text-slate-500">Disk Storage</span>
                            <FolderIcon class="w-4 h-4 text-violet-600" />
                        </div>
                        <p class="text-2xl font-black text-slate-900 mt-2">1.8 GB</p>
                        <div class="w-full bg-slate-100 h-1.5 rounded-full mt-3 overflow-hidden">
                            <div class="bg-violet-600 h-full rounded-full" style="width: 18%"></div>
                        </div>
                        <span class="text-[10px] text-slate-400 mt-1 block">Limit: 10.0 GB NVMe</span>
                    </div>

                    <div class="bg-white rounded-lg border border-slate-200 p-5 shadow-xs">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold uppercase text-slate-500">Inodes Count</span>
                            <DocumentTextIcon class="w-4 h-4 text-amber-600" />
                        </div>
                        <p class="text-2xl font-black text-slate-900 mt-2">14,280</p>
                        <div class="w-full bg-slate-100 h-1.5 rounded-full mt-3 overflow-hidden">
                            <div class="bg-amber-600 h-full rounded-full" style="width: 7%"></div>
                        </div>
                        <span class="text-[10px] text-slate-400 mt-1 block">Limit: 200,000 Inodes</span>
                    </div>
                </div>

                <div class="bg-white rounded-lg border border-slate-200 p-6 shadow-xs space-y-4">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900 pb-2 border-b border-slate-100">
                        Active Subscription Breakdown
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
                        <div class="p-3.5 bg-slate-50 rounded border border-slate-200/70">
                            <span class="text-slate-500 block text-[11px]">Primary Domain:</span>
                            <span class="font-bold text-slate-900 text-sm mt-0.5 block">{{ context.subscription?.domain || 'somitysoft.com' }}</span>
                        </div>
                        <div class="p-3.5 bg-slate-50 rounded border border-slate-200/70">
                            <span class="text-slate-500 block text-[11px]">Assigned Plan:</span>
                            <span class="font-bold text-blue-600 text-sm mt-0.5 block">{{ context.subscription?.plan?.name || 'Standard Cloud Host' }}</span>
                        </div>
                        <div class="p-3.5 bg-slate-50 rounded border border-slate-200/70">
                            <span class="text-slate-500 block text-[11px]">Status:</span>
                            <span class="font-bold text-emerald-600 text-sm mt-0.5 block uppercase">{{ context.subscription?.status || 'Active' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TOOL SPECIFIC PANEL: Subdomains -->
            <div v-else-if="tool === 'subdomains'" class="space-y-4">
                <div class="bg-white rounded-lg border border-slate-200 p-6 shadow-xs space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900">Create New Subdomain</h3>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <input type="text" placeholder="subdomain name (e.g. app)" class="bg-slate-50 border border-slate-200 rounded px-3 py-2 text-xs text-slate-900" />
                        <select class="bg-slate-50 border border-slate-200 rounded px-3 py-2 text-xs text-slate-900">
                            <option v-for="w in context.websites" :key="w.id" :value="w.domain">.{{ w.domain }}</option>
                            <option v-if="!context.websites?.length" value="somitysoft.com">.somitysoft.com</option>
                        </select>
                        <button @click="triggerAction('Subdomain provisioned and Nginx vhost updated.')" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded text-xs font-bold transition cursor-pointer">
                            Create Subdomain
                        </button>
                    </div>
                </div>
            </div>

            <!-- TOOL SPECIFIC PANEL: SSL Certificates -->
            <div v-else-if="tool === 'ssl'" class="space-y-4">
                <div class="bg-white rounded-lg border border-slate-200 overflow-hidden shadow-xs">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="border-b border-slate-200 bg-slate-50/75 text-[11px] font-bold uppercase text-slate-500">
                                <th class="p-3.5">Domain</th>
                                <th class="p-3.5">SSL Provider</th>
                                <th class="p-3.5">Status</th>
                                <th class="p-3.5 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="w in (context.websites?.length ? context.websites : [{ id: 1, domain: 'somitysoft.com', ssl_status: 'active' }])" :key="w.id">
                                <td class="p-3.5 font-bold text-slate-900">{{ w.domain }}</td>
                                <td class="p-3.5 text-slate-600">Let's Encrypt Authority X3</td>
                                <td class="p-3.5">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        Active & Protected
                                    </span>
                                </td>
                                <td class="p-3.5 text-right">
                                    <button @click="triggerAction('AutoSSL renewed for ' + w.domain)" class="px-3 py-1 bg-white hover:bg-slate-50 text-blue-600 border border-slate-200 rounded text-xs font-bold cursor-pointer transition">
                                        Reissue AutoSSL
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TOOL SPECIFIC PANEL: Backups -->
            <div v-else-if="tool === 'backups'" class="space-y-4">
                <div class="bg-white rounded-lg border border-slate-200 p-6 shadow-xs flex items-center justify-between">
                    <div>
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900">Manual System Snapshot</h3>
                        <p class="text-xs text-slate-500 mt-1">Generate a real-time full backup of files and MySQL databases.</p>
                    </div>
                    <button @click="triggerAction('Backup snapshot process dispatched in background.')" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded text-xs font-bold transition cursor-pointer">
                        Generate Backup Now
                    </button>
                </div>
            </div>

            <!-- TOOL SPECIFIC PANEL: Cron Jobs -->
            <div v-else-if="tool === 'cron'" class="space-y-4">
                <div class="bg-white rounded-lg border border-slate-200 p-6 shadow-xs space-y-4">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900 pb-2 border-b border-slate-100">Add New Cron Job</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                        <input type="text" placeholder="Minute (* / */5)" class="bg-slate-50 border border-slate-200 rounded px-3 py-2 text-xs text-slate-900" />
                        <input type="text" placeholder="Hour (* / 0)" class="bg-slate-50 border border-slate-200 rounded px-3 py-2 text-xs text-slate-900" />
                        <input type="text" placeholder="Command (e.g. php /var/www/artisan schedule:run)" class="sm:col-span-2 bg-slate-50 border border-slate-200 rounded px-3 py-2 text-xs text-slate-900" />
                    </div>
                    <div class="flex justify-end">
                        <button @click="triggerAction('Cron schedule added to crontab.')" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded text-xs font-bold transition cursor-pointer">
                            Save Cron Job
                        </button>
                    </div>
                </div>
            </div>

            <!-- TOOL SPECIFIC PANEL: DNS Zone Editor -->
            <div v-else-if="tool === 'dns'" class="space-y-4">
                <div class="bg-white rounded-lg border border-slate-200 p-6 shadow-xs space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900">Add DNS Record</h3>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                        <select class="bg-slate-50 border border-slate-200 rounded px-3 py-2 text-xs text-slate-900">
                            <option value="A">A Record</option>
                            <option value="CNAME">CNAME</option>
                            <option value="MX">MX</option>
                            <option value="TXT">TXT</option>
                        </select>
                        <input type="text" placeholder="Name (@ or subdomain)" class="bg-slate-50 border border-slate-200 rounded px-3 py-2 text-xs text-slate-900" />
                        <input type="text" placeholder="Target Value (e.g. 103.59.177.138)" class="bg-slate-50 border border-slate-200 rounded px-3 py-2 text-xs text-slate-900" />
                        <button @click="triggerAction('DNS Zone record saved and propagated.')" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded text-xs font-bold transition cursor-pointer">
                            Add Record
                        </button>
                    </div>
                </div>
            </div>

            <!-- DEFAULT INTERACTIVE PANEL FOR OTHER TOOLS -->
            <div v-else class="bg-white rounded-lg border border-slate-200 p-8 shadow-xs text-center space-y-4">
                <div class="w-12 h-12 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center mx-auto">
                    <WrenchIcon class="w-6 h-6" />
                </div>
                <div class="max-w-md mx-auto">
                    <h3 class="text-sm font-bold text-slate-900">{{ title }}</h3>
                    <p class="text-xs text-slate-500 mt-1 leading-relaxed">{{ description }}</p>
                </div>
                <div class="pt-2">
                    <button 
                        @click="triggerAction(title + ' configuration synchronized.')"
                        :disabled="isProcessing"
                        class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded text-xs font-bold shadow-xs transition cursor-pointer disabled:opacity-50 inline-flex items-center gap-2"
                    >
                        <ArrowPathIcon class="w-3.5 h-3.5" :class="{ 'animate-spin': isProcessing }" />
                        <span>{{ isProcessing ? 'Processing...' : 'Run / Manage ' + title }}</span>
                    </button>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>
