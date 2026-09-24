<script setup>
import { ref } from 'vue'
import { Head, useForm, Link } from '@inertiajs/vue3'
import { 
    ShieldCheckIcon, 
    LockClosedIcon, 
    EnvelopeIcon, 
    EyeIcon, 
    EyeSlashIcon,
    ArrowRightIcon,
    ServerIcon,
    ExclamationTriangleIcon,
    KeyIcon
} from '@heroicons/vue/24/outline'

defineProps({
    status: {
        type: String,
    },
})

const showPassword = ref(false)

const form = useForm({
    email: '',
    password: '',
    remember: false,
})

const submit = () => {
    form.post(route('admin.login'), {
        onFinish: () => form.reset('password'),
    })
}
</script>

<template>
    <Head title="Admin Control Portal - DeepTouch Host" />

    <div class="min-h-screen bg-gradient-to-b from-slate-50 via-violet-50/40 to-white text-slate-800 flex flex-col justify-center py-12 sm:px-6 lg:px-8 font-sans selection:bg-violet-600 selection:text-white">
        <div class="sm:mx-auto sm:w-full sm:max-w-md text-center">
            <!-- Brand & Icon -->
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-violet-600 shadow-xl shadow-violet-600/30 mb-4 text-white">
                <ServerIcon class="w-9 h-9" />
            </div>

            <h2 class="text-3xl font-black tracking-tight text-slate-900 flex items-center justify-center gap-2">
                <span>DeepTouch<span class="text-violet-600">Host</span></span>
                <span class="text-xs uppercase font-extrabold tracking-wider px-2 py-0.5 rounded bg-violet-100 text-violet-700 border border-violet-200">Admin</span>
            </h2>
            <p class="mt-2 text-sm text-slate-500 font-medium">
                Infrastructure & Server Control Portal
            </p>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md px-4 sm:px-0">
            <div class="bg-white py-8 px-6 sm:px-10 shadow-xl shadow-slate-200/60 rounded-3xl border border-slate-200">
                <!-- Status message -->
                <div v-if="status" class="mb-4 font-semibold text-sm text-emerald-800 bg-emerald-50 border border-emerald-200 p-3.5 rounded-xl flex items-center gap-2">
                    <ShieldCheckIcon class="w-5 h-5 text-emerald-600" />
                    <span>{{ status }}</span>
                </div>

                <!-- Error Alert -->
                <div v-if="form.errors.email || form.errors.password" class="mb-6 bg-rose-50 border border-rose-200 text-rose-700 p-4 rounded-xl text-xs sm:text-sm flex items-start gap-2.5">
                    <ExclamationTriangleIcon class="w-5 h-5 text-rose-500 flex-shrink-0 mt-0.5" />
                    <div>
                        <p class="font-bold text-rose-900">Authentication Failed</p>
                        <p class="mt-0.5">{{ form.errors.email || form.errors.password }}</p>
                    </div>
                </div>

                <!-- Form -->
                <form @submit.prevent="submit" class="space-y-5">
                    <!-- Email / Username -->
                    <div>
                        <label for="email" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                            Admin Email / Username
                        </label>
                        <div class="relative rounded-xl shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                <EnvelopeIcon class="h-5 w-5 text-slate-400" />
                            </div>
                            <input
                                id="email"
                                v-model="form.email"
                                type="text"
                                required
                                autofocus
                                autocomplete="username"
                                placeholder="admin@deeptouchhost.com or username"
                                class="block w-full pl-10 pr-3.5 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-violet-600 focus:bg-white focus:border-transparent transition-all"
                            />
                        </div>
                    </div>

                    <!-- Password -->
                    <div>
                        <label for="password" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                            Password
                        </label>
                        <div class="relative rounded-xl shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                <LockClosedIcon class="h-5 w-5 text-slate-400" />
                            </div>
                            <input
                                id="password"
                                v-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                required
                                autocomplete="current-password"
                                placeholder="••••••••••••"
                                class="block w-full pl-10 pr-10 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-violet-600 focus:bg-white focus:border-transparent transition-all"
                            />
                            <button 
                                type="button" 
                                @click="showPassword = !showPassword"
                                class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-700 transition-colors cursor-pointer"
                            >
                                <EyeSlashIcon v-if="showPassword" class="h-5 w-5" />
                                <EyeIcon v-else class="h-5 w-5" />
                            </button>
                        </div>
                    </div>

                    <!-- Remember & Security Tag -->
                    <div class="flex items-center justify-between text-xs pt-1">
                        <label class="flex items-center gap-2 cursor-pointer text-slate-600 hover:text-slate-900 font-medium">
                            <input
                                type="checkbox"
                                v-model="form.remember"
                                class="w-4 h-4 rounded bg-slate-50 border-slate-300 text-violet-600 focus:ring-violet-500"
                            />
                            <span>Remember admin session</span>
                        </label>
                        <span class="text-slate-500 flex items-center gap-1 font-mono text-[11px]">
                            <KeyIcon class="w-3.5 h-3.5 text-violet-600" />
                            256-Bit TLS
                        </span>
                    </div>

                    <!-- Submit Button -->
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full py-3.5 px-4 bg-violet-600 hover:bg-violet-700 text-white font-bold text-sm rounded-xl shadow-lg shadow-violet-600/30 transition-all flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed group cursor-pointer"
                    >
                        <span v-if="!form.processing">Authenticate & Enter Panel</span>
                        <span v-else class="flex items-center gap-2">
                            <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            Verifying Credentials...
                        </span>
                        <ArrowRightIcon v-if="!form.processing" class="w-4 h-4 group-hover:translate-x-1 transition-transform" />
                    </button>
                </form>

                <!-- Security Notice -->
                <div class="mt-6 pt-5 border-t border-slate-100 text-center">
                    <p class="text-[11px] text-slate-500 leading-relaxed">
                        🔒 <strong>Restricted Security Zone:</strong> Unauthorized access attempts are monitored and logged with IP address tracing.
                    </p>
                </div>
            </div>

            <!-- Back to Client Portal / Main Site -->
            <div class="mt-6 text-center text-xs text-slate-500 font-semibold flex items-center justify-center gap-4">
                <Link :href="route('login')" class="hover:text-violet-600 transition-colors">
                    ← Client Portal Login
                </Link>
                <span class="text-slate-300">•</span>
                <Link :href="route('home')" class="hover:text-violet-600 transition-colors">
                    Back to Homepage
                </Link>
            </div>
        </div>
    </div>
</template>
