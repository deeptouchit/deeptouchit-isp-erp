<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import { ArrowLeftIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    plan: {
        type: Object,
        required: true,
    }
})

const form = useForm({
    name: props.plan.name,
    slug: props.plan.slug,
    description: props.plan.description || '',
    disk_space: props.plan.disk_space,
    bandwidth: props.plan.bandwidth,
    max_domains: props.plan.max_domains,
    max_subdomains: props.plan.max_subdomains ?? 5,
    max_databases: props.plan.max_databases ?? 2,
    max_email_accounts: props.plan.max_email_accounts ?? 5,
    max_ftp_accounts: props.plan.max_ftp_accounts ?? 2,
    cpu_limit: props.plan.cpu_limit ?? 100,
    ram_limit: props.plan.ram_limit ?? 1024,
    php_version_default: props.plan.php_version_default || '8.3',
    price_monthly: props.plan.price_monthly,
    price_yearly: props.plan.price_yearly,
    auto_ssl: Boolean(props.plan.auto_ssl),
    allow_ssh_access: Boolean(props.plan.allow_ssh_access),
    allow_custom_php_ini: Boolean(props.plan.allow_custom_php_ini),
    allow_git_deploy: Boolean(props.plan.allow_git_deploy),
    allow_redis: Boolean(props.plan.allow_redis),
    redis_memory_mb: props.plan.redis_memory_mb ?? 64,
    allow_memcached: Boolean(props.plan.allow_memcached),
    allow_nodejs: Boolean(props.plan.allow_nodejs),
    allow_python: Boolean(props.plan.allow_python),
    allow_cron_jobs: props.plan.allow_cron_jobs !== undefined ? Boolean(props.plan.allow_cron_jobs) : true,
    allow_backups: props.plan.allow_backups !== undefined ? Boolean(props.plan.allow_backups) : true,
    is_active: Boolean(props.plan.is_active),
})

const phpOptions = ['8.1', '8.2', '8.3', '8.4', '8.5']

const submit = () => {
    form.put(route('admin.plans.update', props.plan.id))
}
</script>

<template>
    <Head :title="`Edit ${plan.name} - DeepTouchHost`" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Hosting', href: '#' },
                    { label: 'Packages / Plans', href: route('admin.plans.index') },
                    { label: `Edit: ${plan.name}` }
                ]"
            >
                <template #actions>
                    <Link
                        :href="route('admin.plans.index')"
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        <ArrowLeftIcon class="w-3.5 h-3.5 text-slate-500" />
                        <span>Back to Packages</span>
                    </Link>
                </template>
            </PageHeader>

            <!-- 2. Full-Width Form -->
            <form @submit.prevent="submit" class="space-y-3.5 text-xs">
                <!-- Section 1: General Information -->
                <div class="bg-white rounded-[4px] border border-[#E2E8F0] p-4 shadow-2xs space-y-3">
                    <div class="flex items-center gap-2 pb-2 border-b border-slate-100">
                        <span class="w-1.5 h-1.5 rounded-full bg-blue-600"></span>
                        <h2 class="text-xs font-bold uppercase tracking-wider text-slate-900">General Package Identity</h2>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">
                                Package Name <span class="text-rose-500">*</span>
                            </label>
                            <input
                                v-model="form.name"
                                type="text"
                                required
                                class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 transition-all shadow-2xs"
                            />
                            <span v-if="form.errors.name" class="text-rose-600 text-[10.5px] font-semibold block">{{ form.errors.name }}</span>
                        </div>

                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">
                                Slug Identifier <span class="text-rose-500">*</span>
                            </label>
                            <input
                                v-model="form.slug"
                                type="text"
                                required
                                class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 transition-all shadow-2xs"
                            />
                            <span v-if="form.errors.slug" class="text-rose-600 text-[10.5px] font-semibold block">{{ form.errors.slug }}</span>
                        </div>
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">
                            Short Description
                        </label>
                        <input
                            v-model="form.description"
                            type="text"
                            placeholder="e.g. Ideal for personal websites, blogs, and corporate portfolio portals."
                            class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 transition-all shadow-2xs"
                        />
                    </div>
                </div>

                <!-- Section 2: Storage & Quota Constraints -->
                <div class="bg-white rounded-[4px] border border-[#E2E8F0] p-4 shadow-2xs space-y-3">
                    <div class="flex items-center gap-2 pb-2 border-b border-slate-100">
                        <span class="w-1.5 h-1.5 rounded-full bg-blue-600"></span>
                        <h2 class="text-xs font-bold uppercase tracking-wider text-slate-900">Storage & Quota Constraints</h2>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Storage (MB) <span class="text-rose-500">*</span></label>
                            <input v-model.number="form.disk_space" type="number" required min="50" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 font-mono" />
                        </div>
                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Bandwidth (MB) <span class="text-rose-500">*</span></label>
                            <input v-model.number="form.bandwidth" type="number" required min="100" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 font-mono" />
                        </div>
                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Max Domains <span class="text-rose-500">*</span></label>
                            <input v-model.number="form.max_domains" type="number" required min="1" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 font-mono" />
                        </div>
                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Subdomains</label>
                            <input v-model.number="form.max_subdomains" type="number" min="0" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 font-mono" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-1">
                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">MySQL Databases</label>
                            <input v-model.number="form.max_databases" type="number" min="0" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 font-mono" />
                        </div>
                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Email Mailboxes</label>
                            <input v-model.number="form.max_email_accounts" type="number" min="0" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 font-mono" />
                        </div>
                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">FTP Accounts</label>
                            <input v-model.number="form.max_ftp_accounts" type="number" min="0" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 font-mono" />
                        </div>
                    </div>
                </div>

                <!-- Section 3: Advanced Services & Stack Entitlements (Redis, Memcached, Node.js, Python, etc.) -->
                <div class="bg-white rounded-[4px] border border-[#E2E8F0] p-4 shadow-2xs space-y-3">
                    <div class="flex items-center gap-2 pb-2 border-b border-slate-100">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#673DE6]"></span>
                        <h2 class="text-xs font-bold uppercase tracking-wider text-slate-900">Services & Stack Entitlements</h2>
                        <span class="text-[10.5px] text-slate-400 font-medium ml-auto">Control which services clients on this plan can access</span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <!-- Redis Cache -->
                        <div class="p-3 rounded-[4px] border transition-all" :class="form.allow_redis ? 'border-[#673DE6]/40 bg-[#EDE8FC]/20' : 'border-slate-200 bg-slate-50/50'">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="flex items-center gap-1.5">
                                        <span class="font-bold text-slate-800">⚡ Redis In-Memory Cache</span>
                                        <span v-if="form.allow_redis" class="px-1.5 py-0.2 rounded text-[10px] font-black uppercase bg-[#EDE8FC] text-[#673DE6]">Active</span>
                                    </div>
                                    <p class="text-[11px] text-slate-500 mt-0.5">High-speed object caching, database query cache & sessions.</p>
                                </div>
                                <input v-model="form.allow_redis" type="checkbox" class="w-4 h-4 rounded text-[#673DE6] focus:ring-[#673DE6] border-slate-300 cursor-pointer mt-0.5" />
                            </div>
                            <!-- Redis Memory Quota Slider/Input -->
                            <div v-if="form.allow_redis" class="mt-2.5 pt-2.5 border-t border-slate-200/60 flex items-center justify-between gap-2">
                                <label class="text-[11px] font-bold text-slate-700">Redis Memory Limit (MB):</label>
                                <div class="flex items-center gap-1.5">
                                    <input v-model.number="form.redis_memory_mb" type="number" min="16" max="4096" class="w-20 px-2 py-1 text-xs rounded-[3px] border border-slate-200 bg-white font-mono text-right" />
                                    <span class="text-[11px] font-bold text-slate-500">MB</span>
                                </div>
                            </div>
                        </div>

                        <!-- Memcached -->
                        <div class="p-3 rounded-[4px] border transition-all" :class="form.allow_memcached ? 'border-[#673DE6]/40 bg-[#EDE8FC]/20' : 'border-slate-200 bg-slate-50/50'">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="flex items-center gap-1.5">
                                        <span class="font-bold text-slate-800">⚡ Memcached Service</span>
                                        <span v-if="form.allow_memcached" class="px-1.5 py-0.2 rounded text-[10px] font-black uppercase bg-[#EDE8FC] text-[#673DE6]">Active</span>
                                    </div>
                                    <p class="text-[11px] text-slate-500 mt-0.5">Distributed key-value memory cache daemon on port 11211.</p>
                                </div>
                                <input v-model="form.allow_memcached" type="checkbox" class="w-4 h-4 rounded text-[#673DE6] focus:ring-[#673DE6] border-slate-300 cursor-pointer mt-0.5" />
                            </div>
                        </div>

                        <!-- Node.js App Support -->
                        <div class="p-3 rounded-[4px] border transition-all" :class="form.allow_nodejs ? 'border-emerald-500/40 bg-emerald-50/20' : 'border-slate-200 bg-slate-50/50'">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="flex items-center gap-1.5">
                                        <span class="font-bold text-slate-800">🟢 Node.js Applications</span>
                                        <span v-if="form.allow_nodejs" class="px-1.5 py-0.2 rounded text-[10px] font-black uppercase bg-emerald-100 text-emerald-700">Active</span>
                                    </div>
                                    <p class="text-[11px] text-slate-500 mt-0.5">PM2 daemon & reverse proxy for Express/Next.js/Nuxt apps.</p>
                                </div>
                                <input v-model="form.allow_nodejs" type="checkbox" class="w-4 h-4 rounded text-emerald-600 focus:ring-emerald-500 border-slate-300 cursor-pointer mt-0.5" />
                            </div>
                        </div>

                        <!-- Python Apps -->
                        <div class="p-3 rounded-[4px] border transition-all" :class="form.allow_python ? 'border-amber-500/40 bg-amber-50/20' : 'border-slate-200 bg-slate-50/50'">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="flex items-center gap-1.5">
                                        <span class="font-bold text-slate-800">🐍 Python Runtime & WSGI</span>
                                        <span v-if="form.allow_python" class="px-1.5 py-0.2 rounded text-[10px] font-black uppercase bg-amber-100 text-amber-700">Active</span>
                                    </div>
                                    <p class="text-[11px] text-slate-500 mt-0.5">Deploy Django/Flask applications with virtual environments.</p>
                                </div>
                                <input v-model="form.allow_python" type="checkbox" class="w-4 h-4 rounded text-amber-600 focus:ring-amber-500 border-slate-300 cursor-pointer mt-0.5" />
                            </div>
                        </div>

                        <!-- Cron Jobs -->
                        <div class="p-3 rounded-[4px] border transition-all" :class="form.allow_cron_jobs ? 'border-blue-500/40 bg-blue-50/20' : 'border-slate-200 bg-slate-50/50'">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="flex items-center gap-1.5">
                                        <span class="font-bold text-slate-800">⏰ Cron Jobs Automation</span>
                                        <span v-if="form.allow_cron_jobs" class="px-1.5 py-0.2 rounded text-[10px] font-black uppercase bg-blue-100 text-blue-700">Active</span>
                                    </div>
                                    <p class="text-[11px] text-slate-500 mt-0.5">Automate recurring scripts, webhooks, and background schedules.</p>
                                </div>
                                <input v-model="form.allow_cron_jobs" type="checkbox" class="w-4 h-4 rounded text-blue-600 focus:ring-blue-500 border-slate-300 cursor-pointer mt-0.5" />
                            </div>
                        </div>

                        <!-- Backups & Restore -->
                        <div class="p-3 rounded-[4px] border transition-all" :class="form.allow_backups ? 'border-blue-500/40 bg-blue-50/20' : 'border-slate-200 bg-slate-50/50'">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="flex items-center gap-1.5">
                                        <span class="font-bold text-slate-800">💾 Self-Service Backups & Restore</span>
                                        <span v-if="form.allow_backups" class="px-1.5 py-0.2 rounded text-[10px] font-black uppercase bg-blue-100 text-blue-700">Active</span>
                                    </div>
                                    <p class="text-[11px] text-slate-500 mt-0.5">Clients can create instant ZIP backups and restore single files or DBs.</p>
                                </div>
                                <input v-model="form.allow_backups" type="checkbox" class="w-4 h-4 rounded text-blue-600 focus:ring-blue-500 border-slate-300 cursor-pointer mt-0.5" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 4: Pricing, Runtimes & Feature Flags -->
                <div class="bg-white rounded-[4px] border border-[#E2E8F0] p-4 shadow-2xs space-y-3">
                    <div class="flex items-center gap-2 pb-2 border-b border-slate-100">
                        <span class="w-1.5 h-1.5 rounded-full bg-blue-600"></span>
                        <h2 class="text-xs font-bold uppercase tracking-wider text-slate-900">Pricing, Runtime & Security Flags</h2>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Monthly Price (৳) <span class="text-rose-500">*</span></label>
                            <input v-model.number="form.price_monthly" type="number" step="0.01" required min="0" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 font-mono" />
                        </div>
                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Yearly Price (৳) <span class="text-rose-500">*</span></label>
                            <input v-model.number="form.price_yearly" type="number" step="0.01" required min="0" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 font-mono" />
                        </div>
                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Default PHP Engine</label>
                            <select v-model="form.php_version_default" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500">
                                <option v-for="ver in phpOptions" :key="ver" :value="ver">PHP {{ ver }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 pt-2">
                        <label class="flex items-center gap-2 cursor-pointer font-bold text-slate-700">
                            <input v-model="form.auto_ssl" type="checkbox" class="w-4 h-4 rounded text-blue-600 focus:ring-blue-500 border-slate-300" />
                            <span>Free Auto-SSL</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer font-bold text-slate-700">
                            <input v-model="form.allow_ssh_access" type="checkbox" class="w-4 h-4 rounded text-blue-600 focus:ring-blue-500 border-slate-300" />
                            <span>SSH Shell Access</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer font-bold text-slate-700">
                            <input v-model="form.allow_git_deploy" type="checkbox" class="w-4 h-4 rounded text-blue-600 focus:ring-blue-500 border-slate-300" />
                            <span>Git Auto Deploy</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer font-bold text-slate-700">
                            <input v-model="form.is_active" type="checkbox" class="w-4 h-4 rounded text-blue-600 focus:ring-blue-500 border-slate-300" />
                            <span>Publish Live</span>
                        </label>
                    </div>
                </div>

                <!-- Footer Action Buttons -->
                <div class="flex items-center justify-end gap-2 pt-2">
                    <Link
                        :href="route('admin.plans.index')"
                        class="px-3.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        Cancel
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-[3px] shadow-2xs transition cursor-pointer disabled:opacity-50"
                    >
                        {{ form.processing ? 'Saving Changes...' : 'Save Changes' }}
                    </button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
