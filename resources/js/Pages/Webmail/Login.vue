<script setup>
import { ref, computed } from 'vue'
import { Head, Link, useForm, usePage } from '@inertiajs/vue3'
import {
    EnvelopeIcon,
    LockClosedIcon,
    EyeIcon,
    EyeSlashIcon,
    ShieldCheckIcon,
    ArrowRightIcon,
    GlobeAltIcon,
    ServerIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    initialEmail: {
        type: String,
        default: '',
    },
    status: {
        type: String,
        default: null,
    },
    error: {
        type: String,
        default: null,
    }
})

const page = usePage()
const appName = computed(() => page.props.app_name || 'DeepTouch Host')
const appLogo = computed(() => page.props.app_logo || null)

const showPassword = ref(false)
const rememberEmail = ref(true)

const form = useForm({
    email: props.initialEmail || '',
    password: '',
})

const submit = () => {
    form.post(route('webmail.authenticate'), {
        onFinish: () => form.reset('password'),
    })
}
</script>

<template>
    <Head title="Webmail Login - Business Mail Portal" />

    <div class="min-h-screen bg-slate-900 flex flex-col justify-between items-center p-4 sm:p-6 relative overflow-hidden font-sans select-none">
        
        <!-- Ambient Background Highlights -->
        <div class="absolute -top-32 -left-32 w-80 h-80 bg-blue-600/15 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-32 -right-32 w-80 h-80 bg-indigo-600/15 rounded-full blur-3xl pointer-events-none"></div>

        <!-- Top Navigation Bar -->
        <header class="w-full max-w-5xl flex items-center justify-between z-10 py-3">
            <div class="flex items-center gap-2.5">
                <div v-if="appLogo" class="w-8 h-8 rounded-[4px] overflow-hidden bg-white p-0.5 border border-slate-700">
                    <img :src="appLogo" :alt="appName" class="w-full h-full object-contain" />
                </div>
                <div v-else class="w-8 h-8 rounded-[4px] bg-blue-600 text-white flex items-center justify-center font-black text-sm shadow-md">
                    <EnvelopeIcon class="w-4 h-4" />
                </div>
                <div>
                    <span class="text-sm font-black text-white tracking-tight block">{{ appName }}</span>
                    <span class="text-[9.5px] font-extrabold uppercase tracking-wider text-blue-400 block -mt-1">Webmail Portal</span>
                </div>
            </div>

            <div class="flex items-center gap-3 text-xs text-slate-400">
                <span class="hidden sm:inline-flex items-center gap-1.5 bg-slate-800/80 px-2.5 py-1 rounded-[3px] border border-slate-700 text-emerald-400 text-[11px] font-mono">
                    <ShieldCheckIcon class="w-3.5 h-3.5" />
                    <span>TLS 1.3 Encrypted</span>
                </span>
                <Link
                    :href="route('client.dashboard')"
                    class="px-3 py-1 rounded-[3px] bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 text-xs font-bold transition cursor-pointer"
                >
                    Client Panel ↗
                </Link>
            </div>
        </header>

        <!-- Main Authentication Card -->
        <main class="w-full max-w-md my-auto z-10 py-4">
            <div class="bg-white rounded-lg shadow-xl border border-slate-200 p-6 sm:p-7 space-y-5">
                
                <!-- Card Header -->
                <div class="text-center space-y-1">
                    <div class="w-10 h-10 rounded-lg bg-blue-50 border border-blue-100 text-blue-600 flex items-center justify-center mx-auto mb-2">
                        <EnvelopeIcon class="w-5 h-5" />
                    </div>
                    <h1 class="text-xl font-black text-slate-900 tracking-tight">
                        Sign In to Webmail
                    </h1>
                    <p class="text-xs text-slate-500 font-medium">
                        Access your business inbox, calendar & address book
                    </p>
                </div>

                <!-- Status Feedback -->
                <div v-if="status" class="p-2.5 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold rounded-[3px] text-center">
                    {{ status }}
                </div>
                <div v-if="error" class="p-2.5 bg-rose-50 border border-rose-200 text-rose-800 text-xs font-bold rounded-[3px] text-center">
                    {{ error }}
                </div>

                <!-- Form -->
                <form @submit.prevent="submit" class="space-y-4 text-xs font-medium">
                    
                    <!-- Email Address Input -->
                    <div class="space-y-1.5">
                        <label class="block font-bold text-slate-700">Email Address</label>
                        <div class="relative">
                            <input
                                v-model="form.email"
                                type="email"
                                placeholder="name@yourdomain.com"
                                class="w-full px-3 py-2 rounded-[3px] border border-slate-300 text-slate-900 bg-white placeholder-slate-400 focus:ring-1 focus:ring-blue-600 focus:border-blue-600 outline-none transition text-xs font-mono font-bold"
                                required
                                autofocus
                            />
                        </div>
                        <p v-if="form.errors.email" class="text-rose-600 text-[11px] font-bold mt-1">{{ form.errors.email }}</p>
                    </div>

                    <!-- Password Input -->
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between">
                            <label class="block font-bold text-slate-700">Mailbox Password</label>
                        </div>
                        <div class="relative">
                            <input
                                v-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                placeholder="Enter mailbox password"
                                class="w-full pl-3 pr-10 py-2 rounded-[3px] border border-slate-300 text-slate-900 bg-white placeholder-slate-400 focus:ring-1 focus:ring-blue-600 focus:border-blue-600 outline-none transition text-xs font-mono font-bold"
                                required
                            />
                            <button
                                type="button"
                                @click="showPassword = !showPassword"
                                class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 cursor-pointer"
                            >
                                <EyeSlashIcon v-if="showPassword" class="w-4 h-4" />
                                <EyeIcon v-else class="w-4 h-4" />
                            </button>
                        </div>
                        <p v-if="form.errors.password" class="text-rose-600 text-[11px] font-bold mt-1">{{ form.errors.password }}</p>
                    </div>

                    <!-- Remember Email Checkbox -->
                    <div class="flex items-center justify-between pt-0.5">
                        <label class="flex items-center gap-2 cursor-pointer text-slate-600 text-xs font-medium">
                            <input
                                v-model="rememberEmail"
                                type="checkbox"
                                class="w-3.5 h-3.5 rounded-[2px] border-slate-300 text-blue-600 focus:ring-blue-500"
                            />
                            <span>Remember email address</span>
                        </label>
                    </div>

                    <!-- Submit Button -->
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full py-2.5 rounded-[3px] bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-bold text-xs tracking-wide shadow-2xs transition flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50"
                    >
                        <span>{{ form.processing ? 'Signing In...' : 'Log In to Webmail' }}</span>
                        <ArrowRightIcon class="w-3.5 h-3.5 stroke-[2.5]" />
                    </button>

                </form>

                <!-- Help Info -->
                <div class="pt-3 border-t border-slate-100 text-center text-[11px] text-slate-400">
                    <span>Having trouble logging in? Contact your administrator or reset password from the Client Portal.</span>
                </div>

            </div>
        </main>

        <!-- Footer -->
        <footer class="w-full max-w-5xl flex items-center justify-between text-[11px] text-slate-500 z-10 py-3 border-t border-slate-800">
            <span>Powered by {{ appName }} Mail Engine</span>
            <span class="font-mono">Dovecot IMAP • Postfix SMTP</span>
        </footer>

    </div>
</template>
