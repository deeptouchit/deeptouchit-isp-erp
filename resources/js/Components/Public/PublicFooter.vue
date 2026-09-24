<script setup>
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import {
    ServerIcon,
    PhoneIcon,
    EnvelopeIcon,
    MapPinIcon,
    ShieldCheckIcon,
    BoltIcon,
    GlobeAltIcon,
    HeartIcon
} from '@heroicons/vue/24/outline'

const page = usePage()

const appName = computed(() => page.props.app_name || 'DeepTouchHost')
const appTagline = computed(() => page.props.app_tagline || 'Cloud & IT Solutions')
const appLogo = computed(() => page.props.app_logo || null)
const supportPhone = computed(() => page.props.support_phone || '+880 1977-799300')
const supportEmail = computed(() => page.props.support_email || 'support@deeptouchit.com')
const supportWhatsapp = computed(() => page.props.support_whatsapp || '8801977799300')
const copyrightText = computed(() => page.props.copyright_text || '© 2026 DeepTouch IT Ltd. All rights reserved.')
</script>

<template>
    <footer class="bg-white text-slate-600 text-xs border-t border-slate-200">
        <!-- Top Trust Badges Bar (Light) -->
        <div class="border-b border-slate-200 py-6 bg-slate-50/80">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6 text-center">
                    <div class="flex flex-col items-center">
                        <div class="w-10 h-10 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center mb-2">
                            <BoltIcon class="w-5 h-5" />
                        </div>
                        <span class="text-xs sm:text-sm font-bold text-slate-900">Pure NVMe SSD Storage</span>
                        <span class="text-[11px] text-slate-500">Up to 10x faster disk I/O</span>
                    </div>

                    <div class="flex flex-col items-center">
                        <div class="w-10 h-10 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center mb-2">
                            <GlobeAltIcon class="w-5 h-5" />
                        </div>
                        <span class="text-xs sm:text-sm font-bold text-slate-900">BDIX 10Gbps Peering</span>
                        <span class="text-[11px] text-slate-500">Lowest latency in Bangladesh</span>
                    </div>

                    <div class="flex flex-col items-center">
                        <div class="w-10 h-10 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center mb-2">
                            <ShieldCheckIcon class="w-5 h-5" />
                        </div>
                        <span class="text-xs sm:text-sm font-bold text-slate-900">99.99% Uptime Guarantee</span>
                        <span class="text-[11px] text-slate-500">Enterprise SLA backed</span>
                    </div>

                    <div class="flex flex-col items-center">
                        <div class="w-10 h-10 rounded-full bg-purple-100 text-purple-600 flex items-center justify-center mb-2">
                            <PhoneIcon class="w-5 h-5" />
                        </div>
                        <span class="text-xs sm:text-sm font-bold text-slate-900">24/7 Priority Support</span>
                        <span class="text-[11px] text-slate-500">Bangla & English assistance</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Footer Links Section (Light) -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-8">
                <!-- Column 1: Brand & Bio -->
                <div class="lg:col-span-2 space-y-3">
                    <Link :href="route('home')" class="flex items-center gap-2.5">
                        <img v-if="appLogo" :src="appLogo" :alt="appName" class="h-8 w-auto object-contain rounded" />
                        <div v-else class="w-8 h-8 rounded-lg bg-blue-600 flex items-center justify-center text-white shadow-xs">
                            <ServerIcon class="w-4 h-4" />
                        </div>
                        <span class="text-lg font-black tracking-tight text-slate-900">
                            {{ appName }}
                        </span>
                    </Link>

                    <p class="text-slate-500 text-xs leading-relaxed max-w-sm">
                        {{ appName }} is a premier cloud infrastructure and web hosting provider in Bangladesh. Delivering enterprise-grade NVMe SSD hosting, BDIX low-latency network connectivity, Cloud VPS, and 24/7 technical support for mission-critical businesses.
                    </p>

                    <div class="space-y-1.5 pt-2 text-xs">
                        <div class="flex items-center gap-2 text-slate-700">
                            <MapPinIcon class="w-4 h-4 text-blue-600 shrink-0" />
                            <span>House #42, Road #11, Sector #10, Uttara, Dhaka-1230, Bangladesh</span>
                        </div>
                        <div class="flex items-center gap-2 text-slate-700">
                            <PhoneIcon class="w-4 h-4 text-blue-600 shrink-0" />
                            <span>Hotline: {{ supportPhone }} (24/7 Call & WhatsApp)</span>
                        </div>
                        <div class="flex items-center gap-2 text-slate-700">
                            <EnvelopeIcon class="w-4 h-4 text-blue-600 shrink-0" />
                            <span>{{ supportEmail }}</span>
                        </div>
                    </div>
                </div>

                <!-- Column 2: Web Hosting Services -->
                <div class="space-y-2.5">
                    <h3 class="text-slate-900 text-xs font-bold uppercase tracking-wider">Cloud Hosting</h3>
                    <ul class="space-y-2 text-xs">
                        <li><Link :href="route('public.shared')" class="text-slate-600 hover:text-blue-600 transition">Shared Cloud Hosting</Link></li>
                        <li><Link :href="route('public.shared')" class="text-slate-600 hover:text-blue-600 transition">BDIX NVMe Hosting</Link></li>
                        <li><Link :href="route('public.shared')" class="text-slate-600 hover:text-blue-600 transition">WordPress Optimized</Link></li>
                        <li><Link :href="route('public.vps')" class="text-slate-600 hover:text-blue-600 transition">Cloud VPS Servers</Link></li>
                        <li><Link :href="route('public.vps')" class="text-slate-600 hover:text-blue-600 transition">Dedicated Server Nodes</Link></li>
                    </ul>
                </div>

                <!-- Column 3: Domains & Tools -->
                <div class="space-y-2.5">
                    <h3 class="text-slate-900 text-xs font-bold uppercase tracking-wider">Domains & Addons</h3>
                    <ul class="space-y-2 text-xs">
                        <li><Link :href="route('public.domains')" class="text-slate-600 hover:text-blue-600 transition">Domain Registration</Link></li>
                        <li><Link :href="route('public.domains')" class="text-slate-600 hover:text-blue-600 transition">.com.bd Domain Registration</Link></li>
                        <li><Link :href="route('public.domains')" class="text-slate-600 hover:text-blue-600 transition">Domain Transfer</Link></li>
                        <li><Link :href="route('webmail.login')" class="text-slate-600 hover:text-blue-600 transition">Webmail Portal</Link></li>
                        <li><Link :href="route('login')" class="text-slate-600 hover:text-blue-600 transition">Client Control Panel</Link></li>
                    </ul>
                </div>

                <!-- Column 4: Support & Company -->
                <div class="space-y-2.5">
                    <h3 class="text-slate-900 text-xs font-bold uppercase tracking-wider">Support & Help</h3>
                    <ul class="space-y-2 text-xs">
                        <li><Link :href="route('public.contact')" class="text-slate-600 hover:text-blue-600 transition">24/7 Support Desk</Link></li>
                        <li><Link :href="route('public.contact')" class="text-slate-600 hover:text-blue-600 transition">Open Support Ticket</Link></li>
                        <li><a :href="'https://wa.me/' + supportWhatsapp" target="_blank" class="hover:text-emerald-700 text-emerald-600 font-semibold transition">WhatsApp Live Chat</a></li>
                        <li><Link :href="route('login')" class="text-slate-600 hover:text-blue-600 transition">Client Portal Area</Link></li>
                    </ul>
                </div>

                <!-- Column 5: Payment Methods -->
                <div class="space-y-2.5">
                    <h3 class="text-slate-900 text-xs font-bold uppercase tracking-wider">Instant Payment</h3>
                    <p class="text-slate-500 text-[11px]">
                        We accept bKash, Nagad, Rocket, Upay, Visa, MasterCard & Amex with instant automated activation.
                    </p>
                    <div class="flex flex-wrap gap-1.5 pt-1">
                        <span class="px-2 py-0.5 rounded bg-slate-100 border border-slate-200 text-[11px] font-bold text-pink-600">bKash</span>
                        <span class="px-2 py-0.5 rounded bg-slate-100 border border-slate-200 text-[11px] font-bold text-orange-600">Nagad</span>
                        <span class="px-2 py-0.5 rounded bg-slate-100 border border-slate-200 text-[11px] font-bold text-purple-600">Rocket</span>
                        <span class="px-2 py-0.5 rounded bg-slate-100 border border-slate-200 text-[11px] font-bold text-blue-600">Visa / MC</span>
                    </div>
                </div>
            </div>

            <!-- Bottom Sub-Footer Bar -->
            <div class="border-t border-slate-200 mt-8 pt-5 flex flex-col md:flex-row items-center justify-between gap-3">
                <p class="text-slate-500 text-[11px]">
                    {{ copyrightText }}
                </p>

                <div class="flex items-center gap-3 text-[11px] text-slate-500">
                    <a :href="page.props.help_url || 'https://help.deeptouchit.com'" target="_blank" class="hover:text-blue-600 transition">Knowledgebase</a>
                    <span>•</span>
                    <a href="#" class="hover:text-blue-600 transition">Terms of Service</a>
                    <span>•</span>
                    <a href="#" class="hover:text-blue-600 transition">Privacy Policy</a>
                    <span>•</span>
                    <a href="#" class="hover:text-blue-600 transition">SLA 99.99%</a>
                </div>
            </div>
        </div>
    </footer>
</template>
