<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import { 
    ArrowLeftIcon, 
    GlobeAltIcon, 
    ServerIcon, 
    ShieldCheckIcon,
    ExclamationTriangleIcon,
    InformationCircleIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    subscriptions: {
        type: Array,
        default: () => []
    },
    serverIp: {
        type: String,
        default: '103.59.177.138'
    }
})

const form = useForm({
    subscription_id: props.subscriptions[0]?.id || '',
    domain: '',
    php_version: '8.2',
    auto_ssl: true,
})

const submit = () => {
    form.post(route('websites.store'))
}
</script>

<template>
    <Head title="Connect New Domain - DeepTouch Cloud" />

    <AuthenticatedLayout>
        <div class="max-w-3xl mx-auto space-y-4">
            <!-- 1. Smart Minimal Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Domains', href: '#' },
                    { label: 'Websites', href: route('websites.index') },
                    { label: 'Add New Website' }
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
                </template>
            </PageHeader>

            <div class="space-y-6">
            
            <!-- Information Callout -->
            <div class="p-4 rounded-xl bg-blue-50 border border-blue-200 text-xs text-blue-900 flex items-start gap-3 shadow-2xs">
                <InformationCircleIcon class="w-5 h-5 text-blue-600 shrink-0 mt-0.5" />
                <div>
                    <span class="font-bold">DNS Setup Requirement:</span>
                    <p class="text-blue-800 text-[11px] mt-0.5 leading-relaxed">
                        Before or after adding your domain, point its <strong>A Record</strong> to server IP <code class="bg-blue-100/80 px-1 py-0.2 rounded font-mono font-bold">{{ serverIp }}</code> or set Nameservers to <code class="bg-blue-100/80 px-1 py-0.2 rounded font-mono font-bold">ns1.deeptouchit.com</code> and <code class="bg-blue-100/80 px-1 py-0.2 rounded font-mono font-bold">ns2.deeptouchit.com</code>.
                    </p>
                </div>
            </div>

            <!-- Form Card -->
            <div class="bg-white rounded-xl border border-slate-200 p-6 sm:p-7 shadow-xs">
                <form @submit.prevent="submit" class="space-y-5">
                    
                    <!-- Error Notice -->
                    <div v-if="form.errors.domain" class="p-3.5 rounded-lg bg-rose-50 border border-rose-200 text-xs text-rose-800 flex items-start gap-2.5">
                        <ExclamationTriangleIcon class="w-4 h-4 text-rose-600 shrink-0 mt-0.5" />
                        <span class="font-bold">{{ form.errors.domain }}</span>
                    </div>

                    <!-- Select Target Subscription -->
                    <div>
                        <label class="block text-xs font-bold text-slate-800 uppercase tracking-wider mb-1.5">
                            Target Hosting Plan / Subscription <span class="text-rose-500">*</span>
                        </label>
                        <select 
                            v-model="form.subscription_id" 
                            required 
                            class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3.5 py-2.5 text-xs font-medium text-slate-900 focus:ring-2 focus:ring-blue-600/20 focus:border-blue-600 focus:bg-white transition"
                        >
                            <option v-for="sub in subscriptions" :key="sub.id" :value="sub.id">
                                {{ sub.domain }} • {{ sub.plan_name }} ({{ sub.used_domains }}/{{ sub.max_domains }} Domains Used)
                            </option>
                        </select>
                        <p class="text-[11px] text-slate-400 mt-1">Domains will share the server resources allocated to this subscription.</p>
                    </div>

                    <!-- Domain Name Input -->
                    <div>
                        <label class="block text-xs font-bold text-slate-800 uppercase tracking-wider mb-1.5">
                            Domain Name (FQDN) <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <input 
                                v-model="form.domain" 
                                type="text" 
                                required 
                                placeholder="mybrand.com or app.mybrand.com" 
                                class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3.5 py-2.5 text-xs font-mono font-bold text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-blue-600/20 focus:border-blue-600 focus:bg-white transition" 
                            />
                        </div>
                        <p class="text-[11px] text-slate-400 mt-1">Do not include http:// or https:// (e.g. yourcompany.com).</p>
                    </div>

                    <!-- PHP Version -->
                    <div>
                        <label class="block text-xs font-bold text-slate-800 uppercase tracking-wider mb-1.5">
                            PHP Execution Engine
                        </label>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                            <label 
                                v-for="ver in ['8.1', '8.2', '8.3', '8.5']" 
                                :key="ver"
                                :class="[
                                    'p-3 rounded-lg border text-center cursor-pointer transition font-bold text-xs flex flex-col items-center justify-center gap-0.5',
                                    form.php_version === ver 
                                        ? 'bg-blue-50 border-blue-600 text-blue-700 ring-2 ring-blue-600/20' 
                                        : 'border-slate-200 hover:bg-slate-50 text-slate-700'
                                ]"
                            >
                                <input type="radio" v-model="form.php_version" :value="ver" class="hidden" />
                                <span>PHP {{ ver }}</span>
                                <span v-if="ver === '8.2'" class="text-[9px] text-blue-600 font-medium">Recommended</span>
                            </label>
                        </div>
                    </div>

                    <!-- Auto SSL Checkbox -->
                    <div class="pt-2">
                        <label class="flex items-center gap-2.5 cursor-pointer bg-slate-50 p-3 rounded-lg border border-slate-200 hover:bg-slate-100/60 transition">
                            <input 
                                type="checkbox" 
                                id="auto_ssl" 
                                v-model="form.auto_ssl" 
                                class="w-4 h-4 rounded text-blue-600 focus:ring-blue-500 bg-white border-slate-300 cursor-pointer" 
                            />
                            <div>
                                <span class="text-xs font-bold text-slate-800 block">Enable Automated Let's Encrypt SSL</span>
                                <span class="text-[11px] text-slate-500 block">Issues free wildcard/domain SSL certificate automatically upon DNS resolution.</span>
                            </div>
                        </label>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                        <Link 
                            :href="route('websites.index')" 
                            class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-bold transition"
                        >
                            Cancel
                        </Link>
                        <button 
                            type="submit" 
                            :disabled="form.processing" 
                            class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-bold shadow-md shadow-blue-600/20 transition cursor-pointer flex items-center gap-2 disabled:opacity-50"
                        >
                            <GlobeAltIcon class="w-4 h-4" />
                            <span>{{ form.processing ? 'Deploying vHost...' : 'Deploy Website' }}</span>
                        </button>
                    </div>

                </form>
            </div>

        </div>
        </div>
    </AuthenticatedLayout>
</template>
