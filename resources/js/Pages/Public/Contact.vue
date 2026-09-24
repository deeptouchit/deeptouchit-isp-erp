<script setup>
import { ref } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import PublicNavbar from '@/Components/Public/PublicNavbar.vue'
import PublicFooter from '@/Components/Public/PublicFooter.vue'
import {
    PhoneIcon,
    EnvelopeIcon,
    MapPinIcon,
    ClockIcon,
    ChatBubbleLeftRightIcon,
    LifebuoyIcon,
    CheckCircleIcon
} from '@heroicons/vue/24/outline'

const currentCurrency = ref('BDT')
const isSubmitted = ref(false)

const form = ref({
    name: '',
    email: '',
    phone: '',
    subject: '',
    message: ''
})

const handleSubmit = () => {
    isSubmitted.value = true
    setTimeout(() => {
        form.value = { name: '', email: '', phone: '', subject: '', message: '' }
    }, 400)
}
</script>

<template>
    <Head title="24/7 Contact & Support Desk - DeepTouch Host" />

    <div class="min-h-screen bg-slate-50/60 text-slate-800 font-sans antialiased">
        <PublicNavbar :current-currency="currentCurrency" @currency-change="c => currentCurrency = c" />

        <!-- Header Hero (Light) -->
        <section class="py-10 lg:py-16 bg-white border-b border-slate-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs font-bold uppercase tracking-wider mb-3">
                    <LifebuoyIcon class="w-3.5 h-3.5" />
                    <span>24/7 Expert Technical Assistance</span>
                </div>
                <h1 class="text-2xl sm:text-4xl font-black text-slate-900 tracking-tight">
                    We Are Always Here to <span class="text-blue-600">Help Your Business</span>
                </h1>
                <p class="mt-2.5 text-xs sm:text-sm text-slate-600 max-w-2xl mx-auto">
                    Have questions or require emergency server support? Contact our dedicated engineering team via Hotline, WhatsApp or Support Ticket.
                </p>
            </div>
        </section>

        <!-- Contact Channels & Form Grid (Light) -->
        <section class="py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Column 1: Direct Channels -->
                    <div class="space-y-3.5">
                        <div class="p-5 rounded-xl bg-white border border-slate-200 shadow-xs space-y-1.5">
                            <div class="w-9 h-9 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center mb-2">
                                <PhoneIcon class="w-4 h-4" />
                            </div>
                            <h3 class="text-sm font-bold text-slate-900">24/7 Phone Hotline</h3>
                            <p class="text-xs text-slate-600">Instant verbal technical assistance & sales inquiries.</p>
                            <a href="tel:+8801977799300" class="text-sm font-black text-blue-600 hover:underline block pt-1">
                                +880 1977-799300
                            </a>
                        </div>

                        <div class="p-5 rounded-xl bg-white border border-emerald-200 shadow-xs space-y-1.5">
                            <div class="w-9 h-9 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center mb-2">
                                <ChatBubbleLeftRightIcon class="w-4 h-4" />
                            </div>
                            <h3 class="text-sm font-bold text-slate-900">WhatsApp Live Chat</h3>
                            <p class="text-xs text-slate-600">Fast messaging support for screenshots and quick queries.</p>
                            <a href="https://wa.me/8801977799300" target="_blank" class="inline-flex items-center gap-1.5 text-xs font-bold text-emerald-600 hover:underline pt-1">
                                <span>Message on WhatsApp</span>
                                <span>→</span>
                            </a>
                        </div>

                        <div class="p-5 rounded-xl bg-white border border-slate-200 shadow-xs space-y-1.5">
                            <div class="w-9 h-9 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center mb-2">
                                <MapPinIcon class="w-4 h-4" />
                            </div>
                            <h3 class="text-sm font-bold text-slate-900">Corporate Office</h3>
                            <p class="text-xs text-slate-600 leading-relaxed">
                                DeepTouch IT<br />
                                House #42, Road #11, Sector #10<br />
                                Uttara, Dhaka-1230, Bangladesh
                            </p>
                        </div>
                    </div>

                    <!-- Column 2 & 3: Contact Form (Light) -->
                    <div class="lg:col-span-2 p-6 sm:p-8 rounded-2xl bg-white border border-slate-200 shadow-xs">
                        <h2 class="text-lg sm:text-xl font-black text-slate-900">Send Us a Direct Message</h2>
                        <p class="text-xs text-slate-600 mt-1 mb-6">Our support team will respond to your ticket within 15 minutes.</p>

                        <div v-if="isSubmitted" class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-900 text-xs flex items-center gap-3">
                            <CheckCircleIcon class="w-5 h-5 text-emerald-600 shrink-0" />
                            <span>Thank you! Your message has been received. Our support engineers will contact you shortly.</span>
                        </div>

                        <form v-else @submit.prevent="handleSubmit" class="space-y-4">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="space-y-1">
                                    <label class="text-xs font-bold text-slate-700">Your Full Name</label>
                                    <input v-model="form.name" type="text" required class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3.5 py-2 text-xs text-slate-900 focus:border-blue-600 focus:bg-white focus:outline-none" placeholder="John Doe" />
                                </div>
                                <div class="space-y-1">
                                    <label class="text-xs font-bold text-slate-700">Email Address</label>
                                    <input v-model="form.email" type="email" required class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3.5 py-2 text-xs text-slate-900 focus:border-blue-600 focus:bg-white focus:outline-none" placeholder="you@example.com" />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="space-y-1">
                                    <label class="text-xs font-bold text-slate-700">Phone Number / WhatsApp</label>
                                    <input v-model="form.phone" type="text" required class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3.5 py-2 text-xs text-slate-900 focus:border-blue-600 focus:bg-white focus:outline-none" placeholder="+880 17XXXXXXXX" />
                                </div>
                                <div class="space-y-1">
                                    <label class="text-xs font-bold text-slate-700">Subject</label>
                                    <input v-model="form.subject" type="text" required class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3.5 py-2 text-xs text-slate-900 focus:border-blue-600 focus:bg-white focus:outline-none" placeholder="Hosting Inquiry / Support" />
                                </div>
                            </div>

                            <div class="space-y-1">
                                <label class="text-xs font-bold text-slate-700">Message / Query Details</label>
                                <textarea v-model="form.message" rows="4" required class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3.5 py-2 text-xs text-slate-900 focus:border-blue-600 focus:bg-white focus:outline-none" placeholder="Describe your website needs, migration assistance, or query..."></textarea>
                            </div>

                            <button type="submit" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs uppercase tracking-wider rounded-lg shadow-sm cursor-pointer transition">
                                Send Message
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </section>

        <PublicFooter />
    </div>
</template>
