<script setup>
import { ref } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import { 
    ServerIcon, 
    GlobeAltIcon, 
    CircleStackIcon, 
    ShieldCheckIcon, 
    FolderIcon, 
    ArrowLeftIcon,
    EnvelopeIcon,
    DocumentTextIcon,
    ClipboardDocumentIcon,
    CpuChipIcon,
    ArrowTopRightOnSquareIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    subscription: {
        type: Object,
        required: true
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

// Clipboard helper
const copiedItem = ref('')
const copyToClipboard = (text, label) => {
    navigator.clipboard.writeText(text)
    copiedItem.value = label
    setTimeout(() => {
        copiedItem.value = ''
    }, 2000)
}
</script>

<template>
    <Head :title="`Subscription: ${subscription.domain} - DeepTouch Cloud`" />

    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto space-y-4">
            <!-- 1. Smart Minimal Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Hosting Plan', href: '#' },
                    { label: 'Subscriptions', href: route('subscriptions.index') },
                    { label: subscription.domain }
                ]"
            >
                <template #actions>
                    <Link 
                        :href="route('subscriptions.index')" 
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        <ArrowLeftIcon class="w-3.5 h-3.5 text-slate-500" />
                        <span>Subscriptions</span>
                    </Link>

                    <a 
                        :href="'http://' + subscription.domain" 
                        target="_blank" 
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-800 border border-slate-200 rounded-[3px] text-xs font-bold transition flex items-center gap-1.5 shadow-2xs"
                    >
                        <span>Visit Site</span>
                        <ArrowTopRightOnSquareIcon class="w-3.5 h-3.5 text-slate-400" />
                    </a>
                </template>
            </PageHeader>

            <!-- Server Connection & DNS Details -->
            <div class="bg-white rounded-lg border border-slate-200 p-6 shadow-xs space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-2.5">
                        <ServerIcon class="w-5 h-5 text-blue-600" />
                        <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Server Node & DNS Delegation</h3>
                    </div>
                    <span v-if="copiedItem" class="text-xs text-emerald-600 font-bold">✓ Copied to clipboard!</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 text-xs font-medium text-slate-600">
                    <!-- Server IP -->
                    <div class="p-3.5 bg-slate-50 rounded-md border border-slate-100 flex flex-col justify-between">
                        <div>
                            <span class="text-[10px] uppercase font-bold text-slate-400 block">Server Dedicated/Public IP</span>
                            <span class="text-slate-900 font-mono font-bold text-sm mt-1 block">{{ serverInfo.public_ip }}</span>
                        </div>
                        <button 
                            @click="copyToClipboard(serverInfo.public_ip, 'ip')"
                            class="text-blue-600 hover:text-blue-700 font-bold text-[11px] mt-2 text-left cursor-pointer inline-flex items-center gap-1"
                        >
                            <ClipboardDocumentIcon class="w-3.5 h-3.5" />
                            <span>Copy IP</span>
                        </button>
                    </div>

                    <!-- Primary NS -->
                    <div class="p-3.5 bg-slate-50 rounded-md border border-slate-100 flex flex-col justify-between">
                        <div>
                            <span class="text-[10px] uppercase font-bold text-slate-400 block">Primary Nameserver</span>
                            <span class="text-slate-900 font-mono font-bold text-sm mt-1 block">{{ serverInfo.primary_ns }}</span>
                        </div>
                        <button 
                            @click="copyToClipboard(serverInfo.primary_ns, 'ns1')"
                            class="text-blue-600 hover:text-blue-700 font-bold text-[11px] mt-2 text-left cursor-pointer inline-flex items-center gap-1"
                        >
                            <ClipboardDocumentIcon class="w-3.5 h-3.5" />
                            <span>Copy NS1</span>
                        </button>
                    </div>

                    <!-- Secondary NS -->
                    <div class="p-3.5 bg-slate-50 rounded-md border border-slate-100 flex flex-col justify-between">
                        <div>
                            <span class="text-[10px] uppercase font-bold text-slate-400 block">Secondary Nameserver</span>
                            <span class="text-slate-900 font-mono font-bold text-sm mt-1 block">{{ serverInfo.secondary_ns }}</span>
                        </div>
                        <button 
                            @click="copyToClipboard(serverInfo.secondary_ns, 'ns2')"
                            class="text-blue-600 hover:text-blue-700 font-bold text-[11px] mt-2 text-left cursor-pointer inline-flex items-center gap-1"
                        >
                            <ClipboardDocumentIcon class="w-3.5 h-3.5" />
                            <span>Copy NS2</span>
                        </button>
                    </div>

                    <!-- PHP Socket -->
                    <div class="p-3.5 bg-slate-50 rounded-md border border-slate-100 flex flex-col justify-between">
                        <div>
                            <span class="text-[10px] uppercase font-bold text-slate-400 block">PHP Execution Engine</span>
                            <span class="text-blue-700 font-mono font-bold text-sm mt-1 block">PHP {{ subscription.php_version }} FPM</span>
                        </div>
                        <Link 
                            :href="route('websites.index')" 
                            class="text-blue-600 hover:text-blue-700 font-bold text-[11px] mt-2 inline-flex items-center gap-1"
                        >
                            <span>Switch in Websites</span>
                            <span class="text-xs">→</span>
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Package Quotas & Hardware Limits -->
            <div class="bg-white rounded-lg border border-slate-200 p-6 shadow-xs space-y-4">
                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider pb-3 border-b border-slate-100">
                    Allocated Resource Quotas
                </h3>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                    <div class="p-3.5 bg-slate-50 rounded-md border border-slate-100">
                        <span class="text-[10px] uppercase font-bold text-slate-400 block">Websites Quota</span>
                        <span class="text-base font-black text-slate-900 mt-1 block">
                            {{ subscription.websites?.length || 0 }} / {{ subscription.plan?.max_domains || 1 }}
                        </span>
                        <span class="text-[10px] text-slate-400 mt-0.5 block">VirtualHosts limit</span>
                    </div>

                    <div class="p-3.5 bg-slate-50 rounded-md border border-slate-100">
                        <span class="text-[10px] uppercase font-bold text-slate-400 block">Storage Limit</span>
                        <span class="text-base font-black text-slate-900 mt-1 block">
                            {{ subscription.plan?.disk_space >= 1024 ? (subscription.plan?.disk_space / 1024).toFixed(1) + ' GB' : subscription.plan?.disk_space + ' MB' }}
                        </span>
                        <span class="text-[10px] text-slate-400 mt-0.5 block">NVMe SSD Storage</span>
                    </div>

                    <div class="p-3.5 bg-slate-50 rounded-md border border-slate-100">
                        <span class="text-[10px] uppercase font-bold text-slate-400 block">Databases Allowed</span>
                        <span class="text-base font-black text-slate-900 mt-1 block">
                            {{ subscription.databases?.length || 0 }} / {{ subscription.plan?.max_databases || 1 }}
                        </span>
                        <span class="text-[10px] text-slate-400 mt-0.5 block">MySQL / MariaDB</span>
                    </div>

                    <div class="p-3.5 bg-slate-50 rounded-md border border-slate-100">
                        <span class="text-[10px] uppercase font-bold text-slate-400 block">Email Accounts</span>
                        <span class="text-base font-black text-slate-900 mt-1 block">
                            {{ subscription.email_accounts?.length || 0 }} / {{ subscription.plan?.max_email_accounts || 1 }}
                        </span>
                        <span class="text-[10px] text-slate-400 mt-0.5 block">Custom Domain Inboxes</span>
                    </div>

                    <div class="p-3.5 bg-slate-50 rounded-md border border-slate-100">
                        <span class="text-[10px] uppercase font-bold text-slate-400 block">CPU Throttle</span>
                        <span class="text-base font-black text-slate-900 mt-1 block">
                            {{ subscription.plan?.cpu_limit || 75 }}% Core
                        </span>
                        <span class="text-[10px] text-slate-400 mt-0.5 block">Isolated CPU Share</span>
                    </div>

                    <div class="p-3.5 bg-slate-50 rounded-md border border-slate-100">
                        <span class="text-[10px] uppercase font-bold text-slate-400 block">Memory (RAM)</span>
                        <span class="text-base font-black text-slate-900 mt-1 block">
                            {{ subscription.plan?.ram_limit || 768 }} MB
                        </span>
                        <span class="text-[10px] text-slate-400 mt-0.5 block">Physical RAM Limit</span>
                    </div>

                    <div class="p-3.5 bg-slate-50 rounded-md border border-slate-100">
                        <span class="text-[10px] uppercase font-bold text-slate-400 block">Monthly Bandwidth</span>
                        <span class="text-base font-black text-slate-900 mt-1 block">
                            {{ subscription.plan?.bandwidth >= 1024 ? (subscription.plan?.bandwidth / 1024).toFixed(0) + ' GB' : '50 GB' }}
                        </span>
                        <span class="text-[10px] text-slate-400 mt-0.5 block">BDIX High-Speed Transfer</span>
                    </div>

                    <div class="p-3.5 bg-slate-50 rounded-md border border-slate-100">
                        <span class="text-[10px] uppercase font-bold text-slate-400 block">Renewal Expiry</span>
                        <span class="text-base font-black text-slate-900 mt-1 block">
                            {{ subscription.expires_at }}
                        </span>
                        <span class="text-[10px] text-slate-400 mt-0.5 block">{{ subscription.days_remaining }} days left</span>
                    </div>
                </div>
            </div>

            <!-- Quick Management Shortcuts -->
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                <Link :href="route('websites.index')" class="bg-white border border-slate-200 hover:border-blue-300 p-5 rounded-lg shadow-xs transition group">
                    <div class="w-9 h-9 rounded-md bg-blue-50 text-blue-600 flex items-center justify-center mb-3 group-hover:bg-blue-600 group-hover:text-white transition">
                        <GlobeAltIcon class="w-5 h-5" />
                    </div>
                    <h4 class="font-bold text-slate-900 text-xs mb-1">Websites & Domains</h4>
                    <p class="text-[11px] text-slate-500">VirtualHosts & SSL</p>
                </Link>

                <Link :href="route('file.browse')" class="bg-white border border-slate-200 hover:border-blue-300 p-5 rounded-lg shadow-xs transition group">
                    <div class="w-9 h-9 rounded-md bg-amber-50 text-amber-600 flex items-center justify-center mb-3 group-hover:bg-amber-600 group-hover:text-white transition">
                        <FolderIcon class="w-5 h-5" />
                    </div>
                    <h4 class="font-bold text-slate-900 text-xs mb-1">Web File Manager</h4>
                    <p class="text-[11px] text-slate-500">Upload code to public_html</p>
                </Link>

                <Link :href="route('databases.index')" class="bg-white border border-slate-200 hover:border-blue-300 p-5 rounded-lg shadow-xs transition group">
                    <div class="w-9 h-9 rounded-md bg-purple-50 text-purple-600 flex items-center justify-center mb-3 group-hover:bg-purple-600 group-hover:text-white transition">
                        <CircleStackIcon class="w-5 h-5" />
                    </div>
                    <h4 class="font-bold text-slate-900 text-xs mb-1">MySQL Databases</h4>
                    <p class="text-[11px] text-slate-500">phpMyAdmin Single Sign-On</p>
                </Link>

                <Link :href="route('email-accounts.index')" class="bg-white border border-slate-200 hover:border-blue-300 p-5 rounded-lg shadow-xs transition group">
                    <div class="w-9 h-9 rounded-md bg-emerald-50 text-emerald-600 flex items-center justify-center mb-3 group-hover:bg-emerald-600 group-hover:text-white transition">
                        <EnvelopeIcon class="w-5 h-5" />
                    </div>
                    <h4 class="font-bold text-slate-900 text-xs mb-1">Email Accounts</h4>
                    <p class="text-[11px] text-slate-500">Manage Mailboxes & Webmail</p>
                </Link>
            </div>

        </div>
    </AuthenticatedLayout>
</template>
