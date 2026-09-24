<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { SparklesIcon, CheckCircleIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    selectedPlan: {
        type: Object,
        default: null,
    },
});

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    plan: props.selectedPlan?.slug || null,
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Client Registration - DeepTouch Host" />

        <!-- Selected Plan Header Banner (if coming from Pricing page) -->
        <div v-if="selectedPlan" class="mb-5 p-3.5 rounded-xl bg-blue-50 border border-blue-200 flex items-center justify-between gap-3">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-blue-600 text-white flex items-center justify-center shrink-0">
                    <SparklesIcon class="w-4 h-4" />
                </div>
                <div>
                    <div class="text-xs font-bold text-slate-900">{{ selectedPlan.name }}</div>
                    <div class="text-[11px] text-slate-500 font-medium">Selected Hosting Package</div>
                </div>
            </div>
            <div class="text-right shrink-0">
                <span class="text-xs font-black text-blue-600">৳{{ selectedPlan.price_monthly }}</span>
                <span class="text-[10px] text-slate-500">/mo</span>
            </div>
        </div>

        <form @submit.prevent="submit">
            <div>
                <InputLabel for="name" value="Full Name" />

                <TextInput
                    id="name"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                    placeholder="Your Full Name"
                />

                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div class="mt-4">
                <InputLabel for="email" value="Email Address" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autocomplete="username"
                    placeholder="you@example.com"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="mt-4">
                <InputLabel for="password" value="Password" />

                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password"
                    required
                    autocomplete="new-password"
                    placeholder="Create a strong password"
                />

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="mt-4">
                <InputLabel
                    for="password_confirmation"
                    value="Confirm Password"
                />

                <TextInput
                    id="password_confirmation"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password_confirmation"
                    required
                    autocomplete="new-password"
                    placeholder="Repeat your password"
                />

                <InputError
                    class="mt-2"
                    :message="form.errors.password_confirmation"
                />
            </div>

            <div class="mt-6 flex flex-col gap-3">
                <PrimaryButton
                    class="w-full py-2.5 justify-center font-bold text-xs"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Create Account & Continue
                </PrimaryButton>

                <div class="text-center text-xs text-slate-500 pt-1">
                    Already have a client account?
                    <Link
                        :href="route('login', selectedPlan ? { plan: selectedPlan.slug } : {})"
                        class="text-blue-600 font-bold hover:underline ms-1"
                    >
                        Sign In here
                    </Link>
                </div>
            </div>
        </form>
    </GuestLayout>
</template>
