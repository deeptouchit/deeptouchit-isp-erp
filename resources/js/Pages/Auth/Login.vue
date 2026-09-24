<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { SparklesIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
    selectedPlan: {
        type: Object,
        default: null,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Client Login - DeepTouch Host" />

        <!-- Selected Plan Header Banner (if ordering) -->
        <div v-if="selectedPlan" class="mb-5 p-3.5 rounded-xl bg-blue-50 border border-blue-200 flex items-center justify-between gap-3">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-blue-600 text-white flex items-center justify-center shrink-0">
                    <SparklesIcon class="w-4 h-4" />
                </div>
                <div>
                    <div class="text-xs font-bold text-slate-900">{{ selectedPlan.name }}</div>
                    <div class="text-[11px] text-slate-500 font-medium">Selected Package Order</div>
                </div>
            </div>
            <div class="text-right shrink-0">
                <span class="text-xs font-black text-blue-600">৳{{ selectedPlan.price_monthly }}</span>
                <span class="text-[10px] text-slate-500">/mo</span>
            </div>
        </div>

        <div v-if="status" class="mb-4 text-xs font-medium text-emerald-700 bg-emerald-50 p-3 rounded-lg border border-emerald-200">
            {{ status }}
        </div>

        <form @submit.prevent="submit">
            <div>
                <InputLabel for="email" value="Client Email Address" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                    placeholder="you@example.com"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="mt-4">
                <div class="flex items-center justify-between">
                    <InputLabel for="password" value="Password" />
                    <Link
                        v-if="canResetPassword"
                        :href="route('password.request')"
                        class="text-xs text-blue-600 hover:underline"
                    >
                        Forgot password?
                    </Link>
                </div>

                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                    placeholder="Enter your password"
                />

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="mt-4 block">
                <label class="flex items-center">
                    <Checkbox name="remember" v-model:checked="form.remember" />
                    <span class="ms-2 text-xs text-slate-600">Remember my session</span>
                </label>
            </div>

            <div class="mt-6 flex flex-col gap-3">
                <PrimaryButton
                    class="w-full py-2.5 justify-center font-bold text-xs"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Log In to Client Portal
                </PrimaryButton>

                <div class="text-center text-xs text-slate-500 pt-1">
                    Don't have an account yet?
                    <Link
                        :href="route('register', selectedPlan ? { plan: selectedPlan.slug } : {})"
                        class="text-blue-600 font-bold hover:underline ms-1"
                    >
                        Register new account
                    </Link>
                </div>
            </div>
        </form>
    </GuestLayout>
</template>
