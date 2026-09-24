<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Cog6ToothIcon, ShieldCheckIcon, EnvelopeIcon, ServerIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    settings: {
        type: Object,
        default: () => ({})
    }
})

const form = useForm({
    panel_name: props.settings.panel_name || 'DeepTouch Host Control Panel',
    default_php_version: props.settings.default_php_version || '8.2',
    default_quota_mb: props.settings.default_quota_mb || 2048,
    currency: props.settings.currency || 'BDT',
    currency_symbol: props.settings.currency_symbol || '৳',
    enable_auto_ssl: props.settings.enable_auto_ssl !== 'false',
    smtp_host: props.settings.smtp_host || 'smtp.gmail.com',
    smtp_port: props.settings.smtp_port || '587',
})

const submit = () => {
    form.post(route('admin.settings'))
}
</script>

<template>
    <Head title="System Settings - DeepTouch Host hPanel" />

    <AdminLayout>
        <template #header>
            <div>
                <h1 class="text-2xl font-black text-slate-900 tracking-tight">System Settings & Defaults</h1>
                <p class="text-xs text-slate-500 font-medium mt-0.5">Control global panel configurations, multi-PHP defaults, currency, and mailer</p>
            </div>
        </template>

        <div class="max-w-4xl mx-auto space-y-6">
            <form @submit.prevent="submit" class="space-y-6">
                <!-- General Panel Config -->
                <div class="bg-white rounded-3xl border border-slate-200 p-8 shadow-sm space-y-6">
                    <div class="flex items-center gap-3 pb-4 border-b border-slate-100">
                        <div class="w-9 h-9 rounded-xl bg-violet-50 text-violet-600 flex items-center justify-center font-bold">
                            <Cog6ToothIcon class="w-5 h-5" />
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-slate-900">General Branding & Regional</h3>
                            <p class="text-xs text-slate-500">Panel name, currency symbol, and localization</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-xs font-bold uppercase text-slate-700 mb-1">Panel Brand Name</label>
                            <input v-model="form.panel_name" type="text" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-900 focus:ring-2 focus:ring-violet-600 focus:bg-white" />
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold uppercase text-slate-700 mb-1">Currency Code</label>
                                <input v-model="form.currency" type="text" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-900 focus:ring-2 focus:ring-violet-600 focus:bg-white" />
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase text-slate-700 mb-1">Symbol</label>
                                <input v-model="form.currency_symbol" type="text" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-900 focus:ring-2 focus:ring-violet-600 focus:bg-white" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Hosting & PHP Defaults -->
                <div class="bg-white rounded-3xl border border-slate-200 p-8 shadow-sm space-y-6">
                    <div class="flex items-center gap-3 pb-4 border-b border-slate-100">
                        <div class="w-9 h-9 rounded-xl bg-violet-50 text-violet-600 flex items-center justify-center font-bold">
                            <ServerIcon class="w-5 h-5" />
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-slate-900">Hosting Provisioning Defaults</h3>
                            <p class="text-xs text-slate-500">Default PHP version pool and storage quota</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-xs font-bold uppercase text-slate-700 mb-1">Default PHP Version</label>
                            <select v-model="form.default_php_version" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-900 focus:ring-2 focus:ring-violet-600 focus:bg-white">
                                <option value="8.1">PHP 8.1 FPM</option>
                                <option value="8.2">PHP 8.2 FPM (Recommended)</option>
                                <option value="8.3">PHP 8.3 FPM</option>
                                <option value="8.5">PHP 8.5 FPM</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase text-slate-700 mb-1">Default Storage Quota (MB)</label>
                            <input v-model="form.default_quota_mb" type="number" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-900 focus:ring-2 focus:ring-violet-600 focus:bg-white" />
                        </div>
                    </div>
                </div>

                <!-- Save Action -->
                <div class="flex justify-end">
                    <button type="submit" :disabled="form.processing" class="px-8 py-3.5 bg-violet-600 hover:bg-violet-700 text-white rounded-xl text-xs font-bold shadow-lg shadow-violet-600/20 transition cursor-pointer">
                        Save System Settings
                    </button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
