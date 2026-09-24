<script setup>
import { ref } from 'vue'
import { Head, useForm, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import RefreshButton from '@/Components/UI/RefreshButton.vue'
import InfoCardsGrid from '@/Components/UI/InfoCardsGrid.vue'
import InfoCard from '@/Components/UI/InfoCard.vue'

import {
    PaintBrushIcon,
    PhotoIcon,
    ShieldCheckIcon,
    SparklesIcon,
    ArrowPathIcon,
    CheckIcon,
    ArrowUturnLeftIcon,
    CodeBracketIcon,
    XMarkIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    settings: {
        type: Object,
        required: true,
    },
    stats: {
        type: Object,
        required: true,
    },
})

// Form instance
const form = useForm({
    brand_name: props.settings.brand_name || 'DeepTouchHost',
    tagline: props.settings.tagline || 'Next-Gen Cloud Hosting & Server Management',
    logo_light_url: props.settings.logo_light_url || '',
    logo_dark_url: props.settings.logo_dark_url || '',
    favicon_url: props.settings.favicon_url || '/favicon.ico',
    primary_color: props.settings.primary_color || '#2563EB',
    secondary_color: props.settings.secondary_color || '#1E40AF',
    admin_theme: props.settings.admin_theme || 'light',
    border_style: props.settings.border_style || 'rounded-sm',
    white_label_enabled: Boolean(props.settings.white_label_enabled),
    title_suffix: props.settings.title_suffix || '| DeepTouchHost Cloud Panel',
    copyright_text: props.settings.copyright_text || '© 2026 DeepTouch IT Ltd. All rights reserved.',
    help_url: props.settings.help_url || 'https://help.deeptouchit.com',
    terms_url: props.settings.terms_url || 'https://deeptouchit.com/terms',
    privacy_url: props.settings.privacy_url || 'https://deeptouchit.com/privacy',
    custom_css: props.settings.custom_css || '',
    custom_js: props.settings.custom_js || '',
    logo_light_file: null,
    logo_dark_file: null,
    favicon_file: null,
})

const colorPresets = [
    { name: 'Classic Blue', hex: '#2563EB' },
    { name: 'Indigo Core', hex: '#4F46E5' },
    { name: 'Emerald Green', hex: '#059669' },
    { name: 'Violet Cyber', hex: '#7C3AED' },
    { name: 'Crimson Red', hex: '#DC2626' },
    { name: 'Slate Dark', hex: '#0F172A' },
]

const showResetModal = ref(false)
const isResetting = ref(false)
const feedbackMsg = ref('')

const submit = () => {
    form.post(route('admin.settings.branding.update'), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            feedbackMsg.value = 'Branding & theme preferences updated.'
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

const confirmReset = () => {
    isResetting.value = true
    router.post(route('admin.settings.branding.reset'), {}, {
        preserveScroll: true,
        onFinish: () => {
            isResetting.value = false
            showResetModal.value = false
            feedbackMsg.value = 'Branding reset to default theme.'
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}
</script>

<template>
    <Head title="Branding Settings - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Settings', href: route('admin.settings.general') },
                    { label: 'Branding & Theme' }
                ]"
            >
                <template #actions>
                    <button
                        type="button"
                        @click="showResetModal = true"
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        <ArrowUturnLeftIcon class="w-3.5 h-3.5 text-slate-500" />
                        <span>Reset Defaults</span>
                    </button>

                    <button
                        type="button"
                        @click="submit"
                        :disabled="form.processing"
                        class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white font-bold rounded-[3px] text-xs flex items-center gap-1.5 shadow-2xs transition cursor-pointer"
                    >
                        <CheckIcon class="w-3.5 h-3.5" />
                        <span>{{ form.processing ? 'Saving...' : 'Save Settings' }}</span>
                    </button>

                    <RefreshButton />
                </template>
            </PageHeader>

            <!-- Feedback Banner -->
            <div v-if="feedbackMsg" class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-3 py-2 rounded-[3px] text-xs font-bold flex items-center justify-between shadow-2xs">
                <div class="flex items-center gap-2">
                    <CheckIcon class="w-4 h-4 text-emerald-600" />
                    <span>{{ feedbackMsg }}</span>
                </div>
                <button @click="feedbackMsg = ''" class="text-emerald-600 hover:text-emerald-800 text-xs cursor-pointer">✕</button>
            </div>

            <!-- 2. Ultra-Compact KPI Info Cards (56px) -->
            <InfoCardsGrid :cols="4">
                <InfoCard
                    title="Brand Label"
                    :value="form.brand_name || 'DeepTouchHost'"
                    badge="Active"
                    badgeType="info"
                    color="blue"
                    :icon="PaintBrushIcon"
                />

                <InfoCard
                    title="White-Label"
                    :value="form.white_label_enabled ? 'Fully White-Labeled' : 'Standard Branding'"
                    :badge="form.white_label_enabled ? 'Enterprise' : 'Default'"
                    badgeType="success"
                    color="emerald"
                    :icon="ShieldCheckIcon"
                />

                <InfoCard
                    title="Theme Layout"
                    :value="form.admin_theme === 'light' ? 'Light Theme' : 'Dark Theme'"
                    badge="UI"
                    badgeType="info"
                    color="purple"
                    :icon="SparklesIcon"
                />

                <InfoCard
                    title="Primary Accent"
                    :value="form.primary_color || '#2563EB'"
                    badge="HEX"
                    badgeType="info"
                    color="sky"
                    :icon="PhotoIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Branding Form Panels -->
            <form @submit.prevent="submit" class="space-y-3.5">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                    <!-- Brand Identity & Logos -->
                    <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs p-4 space-y-3">
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide flex items-center gap-2 border-b border-slate-100 pb-2">
                            <PaintBrushIcon class="w-4 h-4 text-blue-600" />
                            Visual Brand & Logo Assets
                        </h3>

                        <div class="space-y-2.5 text-xs">
                            <div class="space-y-1">
                                <label class="block font-bold text-slate-700">Brand Title Name</label>
                                <input v-model="form.brand_name" type="text" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                            </div>

                            <div class="space-y-1">
                                <label class="block font-bold text-slate-700">Tagline / Slogan</label>
                                <input v-model="form.tagline" type="text" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                            </div>

                            <div class="space-y-1">
                                <label class="block font-bold text-slate-700">Browser Page Title Suffix</label>
                                <input v-model="form.title_suffix" type="text" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                            </div>

                            <div class="grid grid-cols-2 gap-2 pt-1">
                                <div class="space-y-1">
                                    <label class="block font-bold text-slate-700">Light Mode Logo URL</label>
                                    <input v-model="form.logo_light_url" type="text" placeholder="/images/logo.svg" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                                </div>
                                <div class="space-y-1">
                                    <label class="block font-bold text-slate-700">Dark Mode Logo URL</label>
                                    <input v-model="form.logo_dark_url" type="text" placeholder="/images/logo-dark.svg" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Color System & Theming -->
                    <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs p-4 space-y-3">
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide flex items-center gap-2 border-b border-slate-100 pb-2">
                            <SparklesIcon class="w-4 h-4 text-blue-600" />
                            Color Palette & Theme Mode
                        </h3>

                        <div class="space-y-2.5 text-xs">
                            <div class="space-y-1">
                                <label class="block font-bold text-slate-700">Quick Palette Presets</label>
                                <div class="flex flex-wrap items-center gap-2">
                                    <button
                                        v-for="p in colorPresets"
                                        :key="p.hex"
                                        type="button"
                                        @click="form.primary_color = p.hex"
                                        :class="form.primary_color === p.hex ? 'ring-2 ring-blue-500 ring-offset-1' : ''"
                                        class="px-2.5 py-1 rounded-[3px] font-bold text-xs flex items-center gap-1.5 border border-slate-200 transition cursor-pointer shadow-2xs"
                                    >
                                        <span class="w-3 h-3 rounded-full" :style="{ backgroundColor: p.hex }"></span>
                                        <span>{{ p.name }}</span>
                                    </button>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-2 pt-2">
                                <div class="space-y-1">
                                    <label class="block font-bold text-slate-700">Primary Color (HEX)</label>
                                    <div class="flex items-center gap-2">
                                        <input v-model="form.primary_color" type="color" class="w-8 h-8 rounded-[3px] border border-slate-200 cursor-pointer p-0.5" />
                                        <input v-model="form.primary_color" type="text" class="flex-1 px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                                    </div>
                                </div>

                                <div class="space-y-1">
                                    <label class="block font-bold text-slate-700">Secondary Accent (HEX)</label>
                                    <div class="flex items-center gap-2">
                                        <input v-model="form.secondary_color" type="color" class="w-8 h-8 rounded-[3px] border border-slate-200 cursor-pointer p-0.5" />
                                        <input v-model="form.secondary_color" type="text" class="flex-1 px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center gap-2 pt-2">
                                <input v-model="form.white_label_enabled" id="white_label" type="checkbox" class="rounded-[2px] text-blue-600 focus:ring-blue-500 cursor-pointer" />
                                <label for="white_label" class="font-bold text-slate-900 cursor-pointer">Enable Complete White-Label Mode (Hide vendor references)</label>
                            </div>
                        </div>
                    </div>

                    <!-- Legal Links & Copyright -->
                    <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs p-4 space-y-3">
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide flex items-center gap-2 border-b border-slate-100 pb-2">
                            <ShieldCheckIcon class="w-4 h-4 text-blue-600" />
                            Legal Notices & Support Links
                        </h3>

                        <div class="space-y-2.5 text-xs">
                            <div class="space-y-1">
                                <label class="block font-bold text-slate-700">Footer Copyright Notice</label>
                                <input v-model="form.copyright_text" type="text" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                            </div>

                            <div class="grid grid-cols-3 gap-2">
                                <div class="space-y-1">
                                    <label class="block font-bold text-slate-700">Help / Docs URL</label>
                                    <input v-model="form.help_url" type="url" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                                </div>
                                <div class="space-y-1">
                                    <label class="block font-bold text-slate-700">Terms URL</label>
                                    <input v-model="form.terms_url" type="url" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                                </div>
                                <div class="space-y-1">
                                    <label class="block font-bold text-slate-700">Privacy URL</label>
                                    <input v-model="form.privacy_url" type="url" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Custom CSS / JS Code Injection -->
                    <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs p-4 space-y-3">
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide flex items-center gap-2 border-b border-slate-100 pb-2">
                            <CodeBracketIcon class="w-4 h-4 text-blue-600" />
                            Custom CSS & JavaScript Injections
                        </h3>

                        <div class="space-y-2.5 text-xs">
                            <div class="space-y-1">
                                <label class="block font-bold text-slate-700">Custom CSS Stylesheet</label>
                                <textarea v-model="form.custom_css" rows="3" placeholder="/* Custom CSS overrides */ :root { --brand-primary: #2563eb; }" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500"></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button
                        type="button"
                        @click="showResetModal = true"
                        class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        Reset Defaults
                    </button>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white font-bold rounded-[3px] text-xs shadow-2xs transition cursor-pointer"
                    >
                        {{ form.processing ? 'Saving...' : 'Save All Branding Settings' }}
                    </button>
                </div>
            </form>
        </div>

        <!-- RESET CONFIRMATION MODAL -->
        <div v-if="showResetModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-amber-50 text-amber-600 flex items-center justify-center font-bold border border-amber-100">
                            <ArrowUturnLeftIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Reset Branding Settings
                        </h3>
                    </div>
                    <button @click="showResetModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <div class="p-4 space-y-2.5 text-xs">
                    <p class="text-slate-600">
                        Are you sure you want to reset all branding, color palettes, and stylesheets back to factory defaults?
                    </p>
                </div>

                <div class="px-4 py-2.5 bg-slate-50/70 border-t border-slate-100 flex items-center justify-end gap-2 text-xs">
                    <button
                        type="button"
                        @click="showResetModal = false"
                        class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        @click="confirmReset"
                        :disabled="isResetting"
                        class="px-3.5 py-1.5 bg-amber-600 hover:bg-amber-700 text-white font-bold rounded-[3px] transition shadow-2xs cursor-pointer disabled:opacity-50"
                    >
                        {{ isResetting ? 'Resetting...' : 'Confirm Reset' }}
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
