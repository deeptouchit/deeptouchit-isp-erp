<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import {
    Bars3Icon,
    XMarkIcon,
    ChevronDownIcon,
    ServerIcon,
    CpuChipIcon,
    GlobeAltIcon,
    ShieldCheckIcon,
    EnvelopeIcon,
    PhoneIcon,
    ChatBubbleLeftRightIcon,
    ArrowRightIcon,
    UserCircleIcon,
    SparklesIcon,
    CurrencyDollarIcon,
    CheckCircleIcon,
    RocketLaunchIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    currentCurrency: {
        type: String,
        default: 'BDT'
    },
    canLogin: {
        type: Boolean,
        default: true
    },
    canRegister: {
        type: Boolean,
        default: true
    }
})

const emit = defineEmits(['currency-change'])

const page = usePage()

// Dynamic branding & settings from Backend Database
const appName = computed(() => page.props.app_name || 'DeepTouchHost')
const appTagline = computed(() => page.props.app_tagline || 'Cloud & IT Solutions')
const appLogo = computed(() => page.props.app_logo || null)
const supportPhone = computed(() => page.props.support_phone || '+880 1977-799300')
const supportEmail = computed(() => page.props.support_email || 'support@deeptouchit.com')
const supportWhatsapp = computed(() => page.props.support_whatsapp || '8801977799300')
const hostingPlans = computed(() => page.props.hosting_plans || [])

const isScrolled = ref(false)
const mobileMenuOpen = ref(false)
const activeDropdown = ref(null)

const handleScroll = () => {
    isScrolled.value = window.scrollY > 20
}

onMounted(() => {
    window.addEventListener('scroll', handleScroll)
})

onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll)
})

const toggleCurrency = (currency) => {
    emit('currency-change', currency)
}

const formatPlanPrice = (plan) => {
    if (props.currentCurrency === 'USD') {
        const usdPrice = (Number(plan.price_monthly) / 120).toFixed(2)
        return `$${usdPrice}/mo`
    }
    return `৳${Math.round(Number(plan.price_monthly))}/mo`
}
</script>

<template>
    <!-- Top Micro Announcement & Utility Bar (Light) -->
    <div class="bg-slate-50 text-slate-600 text-[11px] border-b border-slate-200/80 py-1.5 px-4 sm:px-6 lg:px-8 hidden md:block">
        <div class="max-w-7xl mx-auto flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="flex items-center gap-1.5 text-emerald-600 font-semibold">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>BDIX & Global Cloud Node: 100% Operational (99.99% SLA)</span>
                </div>
                <span class="text-slate-300">|</span>
                <a :href="'tel:' + supportPhone.replace(/\s+/g, '')" class="hover:text-blue-600 flex items-center gap-1 transition text-slate-600">
                    <PhoneIcon class="w-3 h-3 text-blue-600" />
                    <span>Hotline: {{ supportPhone }}</span>
                </a>
                <span class="text-slate-300">|</span>
                <a :href="'https://wa.me/' + supportWhatsapp" target="_blank" class="hover:text-emerald-700 flex items-center gap-1 transition text-emerald-600 font-medium">
                    <ChatBubbleLeftRightIcon class="w-3 h-3" />
                    <span>WhatsApp Support</span>
                </a>
            </div>

            <div class="flex items-center gap-3.5">
                <!-- Currency Switcher (BDT ৳ / USD $) -->
                <div class="flex items-center bg-slate-200/70 rounded p-0.5 border border-slate-300">
                    <button
                        type="button"
                        @click="toggleCurrency('BDT')"
                        :class="currentCurrency === 'BDT' ? 'bg-white text-blue-600 font-bold shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                        class="px-2 py-0.5 rounded text-[10px] transition cursor-pointer"
                    >
                        ৳ BDT
                    </button>
                    <button
                        type="button"
                        @click="toggleCurrency('USD')"
                        :class="currentCurrency === 'USD' ? 'bg-white text-blue-600 font-bold shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                        class="px-2 py-0.5 rounded text-[10px] transition cursor-pointer"
                    >
                        $ USD
                    </button>
                </div>

                <span class="text-slate-300">|</span>

                <Link :href="route('login')" class="hover:text-blue-600 flex items-center gap-1 font-medium transition text-slate-700">
                    <UserCircleIcon class="w-3.5 h-3.5 text-blue-600" />
                    <span>Client Portal</span>
                </Link>

                <Link :href="route('webmail.login')" class="hover:text-blue-600 flex items-center gap-1 text-slate-500 transition">
                    <EnvelopeIcon class="w-3.5 h-3.5" />
                    <span>Webmail</span>
                </Link>
            </div>
        </div>
    </div>

    <!-- Main Sticky Navigation Bar (Light) -->
    <header
        :class="[
            'sticky top-0 z-50 transition-all duration-300 bg-white/95 backdrop-blur-md border-b border-slate-200/90 shadow-xs py-3'
        ]"
    >
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between">
                <!-- Dynamic Logo & Brand Identity -->
                <Link :href="route('home')" class="flex items-center gap-2.5 group cursor-pointer">
                    <img v-if="appLogo" :src="appLogo" :alt="appName" class="h-9 w-auto object-contain rounded" />
                    <div v-else class="w-9 h-9 rounded-lg bg-blue-600 flex items-center justify-center text-white shadow-sm group-hover:scale-105 transition">
                        <ServerIcon class="w-5 h-5" />
                    </div>
                    <span class="text-xl font-black tracking-tight text-slate-900">
                        {{ appName }}
                    </span>
                </Link>

                <!-- Desktop Navigation Links (Direct Clean Links) -->
                <nav class="hidden lg:flex items-center gap-1">
                    <!-- Web Hosting (Direct Link) -->
                    <Link
                        :href="route('public.shared')"
                        :class="[
                            'px-3.5 py-1.5 text-sm font-semibold transition rounded-lg',
                            route().current('public.shared') ? 'text-blue-600 bg-blue-50 font-bold' : 'text-slate-700 hover:text-blue-600 hover:bg-slate-50'
                        ]"
                    >
                        Shared Hosting
                    </Link>

                    <!-- Cloud VPS -->
                    <Link
                        :href="route('public.vps')"
                        :class="[
                            'px-3.5 py-1.5 text-sm font-semibold transition rounded-lg',
                            route().current('public.vps') ? 'text-blue-600 bg-blue-50 font-bold' : 'text-slate-700 hover:text-blue-600 hover:bg-slate-50'
                        ]"
                    >
                        Cloud VPS
                    </Link>

                    <!-- Domain Names -->
                    <Link
                        :href="route('public.domains')"
                        :class="[
                            'px-3.5 py-1.5 text-sm font-semibold transition rounded-lg',
                            route().current('public.domains') ? 'text-blue-600 bg-blue-50 font-bold' : 'text-slate-700 hover:text-blue-600 hover:bg-slate-50'
                        ]"
                    >
                        Domain Names
                    </Link>

                    <!-- Contact & Enterprise -->
                    <Link
                        :href="route('public.contact')"
                        :class="[
                            'px-3.5 py-1.5 text-sm font-semibold transition rounded-lg',
                            route().current('public.contact') ? 'text-blue-600 bg-blue-50 font-bold' : 'text-slate-700 hover:text-blue-600 hover:bg-slate-50'
                        ]"
                    >
                        Contact & Support
                    </Link>
                </nav>

                <!-- Action CTA & Portal Login Buttons -->
                <div class="hidden lg:flex items-center gap-2.5">
                    <Link
                        :href="route('login')"
                        class="px-3.5 py-1.5 text-xs font-bold text-slate-700 hover:text-slate-900 border border-slate-300 hover:border-slate-400 rounded-lg transition hover:bg-slate-50"
                    >
                        Sign In
                    </Link>

                    <Link
                        :href="route('register')"
                        class="px-4 py-1.5 text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-lg shadow-sm flex items-center gap-1.5 transition cursor-pointer"
                    >
                        <span>Get Started</span>
                        <ArrowRightIcon class="w-3.5 h-3.5" />
                    </Link>
                </div>

                <!-- Mobile Menu Hamburger Button -->
                <div class="flex items-center gap-2 lg:hidden">
                    <!-- Mobile Currency Switcher -->
                    <button
                        type="button"
                        @click="toggleCurrency(currentCurrency === 'BDT' ? 'USD' : 'BDT')"
                        class="px-2 py-1 bg-slate-100 text-blue-600 border border-slate-300 rounded text-xs font-bold"
                    >
                        {{ currentCurrency === 'BDT' ? '৳ BDT' : '$ USD' }}
                    </button>

                    <button
                        @click="mobileMenuOpen = !mobileMenuOpen"
                        class="p-2 text-slate-600 hover:text-slate-900 focus:outline-none"
                    >
                        <Bars3Icon v-if="!mobileMenuOpen" class="w-6 h-6" />
                        <XMarkIcon v-else class="w-6 h-6" />
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Drawer Menu (Light) -->
        <div v-show="mobileMenuOpen" class="lg:hidden bg-white border-b border-slate-200 px-4 pt-3 pb-6 space-y-2">
            <Link :href="route('public.shared')" class="block py-2 text-sm font-semibold text-slate-700 border-b border-slate-100 hover:text-blue-600">
                Shared Cloud Hosting
            </Link>
            <Link :href="route('public.vps')" class="block py-2 text-sm font-semibold text-slate-700 border-b border-slate-100 hover:text-blue-600">
                Cloud VPS Servers
            </Link>
            <Link :href="route('public.domains')" class="block py-2 text-sm font-semibold text-slate-700 border-b border-slate-100 hover:text-blue-600">
                Domain Registration (.com, .com.bd)
            </Link>
            <Link :href="route('public.contact')" class="block py-2 text-sm font-semibold text-slate-700 border-b border-slate-100 hover:text-blue-600">
                Contact & Support
            </Link>

            <div class="pt-3 grid grid-cols-2 gap-2">
                <Link :href="route('login')" class="w-full text-center py-2 text-xs font-bold text-slate-700 border border-slate-300 rounded-lg hover:bg-slate-50">
                    Sign In
                </Link>
                <Link :href="route('register')" class="w-full text-center py-2 text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-lg">
                    Get Started
                </Link>
            </div>
        </div>
    </header>
</template>
