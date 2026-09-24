<script setup>
import { ref, computed } from 'vue'
import { Head, useForm, usePage, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import InfoCardsGrid from '@/Components/UI/InfoCardsGrid.vue'
import InfoCard from '@/Components/UI/InfoCard.vue'

import {
    UserCircleIcon,
    KeyIcon,
    ShieldCheckIcon,
    ClockIcon,
    ExclamationTriangleIcon,
    CheckCircleIcon,
    EnvelopeIcon,
    PhoneIcon,
    BuildingOfficeIcon,
    MapPinIcon,
    GlobeAltIcon,
    IdentificationIcon,
    BanknotesIcon,
    ServerIcon,
    LockClosedIcon,
    ArrowPathIcon,
    ArrowTopRightOnSquareIcon,
    DevicePhoneMobileIcon,
    ComputerDesktopIcon,
    TrashIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    mustVerifyEmail: {
        type: Boolean,
        default: false,
    },
    status: {
        type: String,
        default: '',
    },
    profile: {
        type: Object,
        default: () => ({}),
    },
    stats: {
        type: Object,
        default: () => ({
            active_subscriptions: 0,
            active_websites: 0,
            unpaid_invoices: 0,
            open_tickets: 0,
            credit_balance: 0,
        }),
    },
    activityLogs: {
        type: Array,
        default: () => [],
    },
})

// Active Section Tab
const activeTab = ref('personal')

// 1. Personal Info Form
const profileForm = useForm({
    first_name: props.profile.first_name || '',
    last_name: props.profile.last_name || '',
    email: props.profile.email || '',
    phone: props.profile.phone || '',
    company: props.profile.company || '',
    address: props.profile.address || '',
    city: props.profile.city || '',
    state: props.profile.state || '',
    country: props.profile.country || 'Bangladesh',
    zip_code: props.profile.zip_code || '',
})

const updateProfile = () => {
    profileForm.patch(route('profile.update'), {
        preserveScroll: true,
        onSuccess: () => {
            // Updated successfully
        },
    })
}

// 2. Password Security Form
const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
})

const passwordSuccess = ref(false)

const updatePassword = () => {
    passwordForm.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => {
            passwordForm.reset()
            passwordSuccess.value = true
            setTimeout(() => {
                passwordSuccess.value = false
            }, 4000)
        },
    })
}

// 3. Delete Account Form
const confirmingUserDeletion = ref(false)
const deleteForm = useForm({
    password: '',
})

const deleteAccount = () => {
    deleteForm.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => {
            confirmingUserDeletion.value = false
        },
    })
}
</script>

<template>
    <Head title="Account Profile & Security" />

    <AuthenticatedLayout>
        <div class="space-y-4 max-w-7xl mx-auto pb-10">
            <!-- 1. Page Header with Client Identification -->
            <PageHeader
                title="Client Account & Security Profile"
                subtitle="Manage personal contact data, authentication credentials, and security preferences."
                :badge="'ID #' + (profile.id || 1)"
                badge-color="blue"
            >
                <template #actions>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-[3px] text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            <span>{{ (profile.status || 'Active').toUpperCase() }}</span>
                        </span>

                        <span class="hidden sm:inline-flex items-center gap-1.5 px-2.5 py-1 rounded-[3px] text-xs font-mono font-bold bg-slate-100 text-slate-700 border border-slate-200">
                            <span>Balance: ৳{{ Number(profile.credit_balance || 0).toFixed(2) }}</span>
                        </span>
                    </div>
                </template>
            </PageHeader>

            <!-- Status Alert Toast -->
            <div 
                v-if="status" 
                class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-2.5 rounded-[4px] text-xs font-bold flex items-center justify-between shadow-2xs animate-in fade-in"
            >
                <div class="flex items-center gap-2">
                    <CheckCircleIcon class="w-4 h-4 text-emerald-600" />
                    <span>{{ status }}</span>
                </div>
            </div>

            <!-- 2. 4-Column Compact KPI Overview (56px) -->
            <InfoCardsGrid :cols="4">
                <InfoCard
                    title="Account Verified"
                    :value="profile.email_verified ? 'Verified' : 'Pending'"
                    :badge="profile.email_verified ? 'Email Confirmed' : 'Action Required'"
                    :badgeType="profile.email_verified ? 'success' : 'warning'"
                    color="emerald"
                    :icon="ShieldCheckIcon"
                />

                <InfoCard
                    title="Hosting Services"
                    :value="String(stats.active_subscriptions || 0) + ' Plans'"
                    badge="Active"
                    badgeType="info"
                    color="blue"
                    :icon="ServerIcon"
                />

                <InfoCard
                    title="Credit Balance"
                    :value="'৳' + Number(profile.credit_balance || 0).toFixed(2)"
                    badge="Wallet"
                    badgeType="info"
                    color="indigo"
                    :icon="BanknotesIcon"
                />

                <InfoCard
                    title="Two-Factor Auth"
                    :value="profile.two_factor_enabled ? 'Active' : 'Disabled'"
                    :badge="profile.two_factor_enabled ? 'Protected' : 'Recommended'"
                    :badgeType="profile.two_factor_enabled ? 'success' : 'neutral'"
                    color="purple"
                    :icon="LockClosedIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Navigation Tabs -->
            <div class="flex items-center gap-1.5 border-b border-slate-200 pb-2 overflow-x-auto">
                <button
                    type="button"
                    @click="activeTab = 'personal'"
                    :class="activeTab === 'personal' ? 'bg-blue-600 text-white shadow-2xs' : 'bg-white text-slate-700 hover:bg-slate-50 border border-slate-200'"
                    class="px-3 py-1.5 rounded-[3px] font-bold text-xs font-mono transition cursor-pointer flex items-center gap-1.5 shrink-0"
                >
                    <UserCircleIcon class="w-3.5 h-3.5" />
                    <span>PERSONAL & BILLING</span>
                </button>

                <button
                    type="button"
                    @click="activeTab = 'security'"
                    :class="activeTab === 'security' ? 'bg-blue-600 text-white shadow-2xs' : 'bg-white text-slate-700 hover:bg-slate-50 border border-slate-200'"
                    class="px-3 py-1.5 rounded-[3px] font-bold text-xs font-mono transition cursor-pointer flex items-center gap-1.5 shrink-0"
                >
                    <KeyIcon class="w-3.5 h-3.5" />
                    <span>PASSWORD & SECURITY</span>
                </button>

                <button
                    type="button"
                    @click="activeTab = '2fa'"
                    :class="activeTab === '2fa' ? 'bg-blue-600 text-white shadow-2xs' : 'bg-white text-slate-700 hover:bg-slate-50 border border-slate-200'"
                    class="px-3 py-1.5 rounded-[3px] font-bold text-xs font-mono transition cursor-pointer flex items-center gap-1.5 shrink-0"
                >
                    <ShieldCheckIcon class="w-3.5 h-3.5" />
                    <span>TWO-FACTOR AUTH (2FA)</span>
                </button>

                <button
                    type="button"
                    @click="activeTab = 'sessions'"
                    :class="activeTab === 'sessions' ? 'bg-blue-600 text-white shadow-2xs' : 'bg-white text-slate-700 hover:bg-slate-50 border border-slate-200'"
                    class="px-3 py-1.5 rounded-[3px] font-bold text-xs font-mono transition cursor-pointer flex items-center gap-1.5 shrink-0"
                >
                    <ClockIcon class="w-3.5 h-3.5" />
                    <span>LOGIN & AUDIT LOGS</span>
                </button>

                <button
                    type="button"
                    @click="activeTab = 'danger'"
                    :class="activeTab === 'danger' ? 'bg-rose-600 text-white shadow-2xs' : 'bg-white text-rose-700 hover:bg-rose-50 border border-rose-200'"
                    class="px-3 py-1.5 rounded-[3px] font-bold text-xs font-mono transition cursor-pointer flex items-center gap-1.5 shrink-0 ml-auto"
                >
                    <ExclamationTriangleIcon class="w-3.5 h-3.5" />
                    <span>ACCOUNT DANGER ZONE</span>
                </button>
            </div>

            <!-- ========================================================= -->
            <!-- TAB 1: PERSONAL & BILLING INFORMATION                     -->
            <!-- ========================================================= -->
            <div v-if="activeTab === 'personal'" class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <!-- Left 2 Cols: Form -->
                <div class="lg:col-span-2 bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs p-5 space-y-5">
                    <div class="border-b border-slate-100 pb-3">
                        <h3 class="text-sm font-bold text-slate-900">Personal & Company Information</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Keep your account details up to date for official tax invoices and domain contact verification.</p>
                    </div>

                    <form @submit.prevent="updateProfile" class="space-y-4">
                        <!-- Names Grid -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">First Name <span class="text-rose-600">*</span></label>
                                <input
                                    type="text"
                                    v-model="profileForm.first_name"
                                    required
                                    class="w-full bg-[#F8FAFC] focus:bg-white border border-[#E2E8F0] focus:border-blue-600 rounded-[3px] px-3 py-1.5 text-xs text-slate-800 transition"
                                />
                                <span v-if="profileForm.errors.first_name" class="text-[11px] text-rose-600 mt-1 block">{{ profileForm.errors.first_name }}</span>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Last Name</label>
                                <input
                                    type="text"
                                    v-model="profileForm.last_name"
                                    class="w-full bg-[#F8FAFC] focus:bg-white border border-[#E2E8F0] focus:border-blue-600 rounded-[3px] px-3 py-1.5 text-xs text-slate-800 transition"
                                />
                                <span v-if="profileForm.errors.last_name" class="text-[11px] text-rose-600 mt-1 block">{{ profileForm.errors.last_name }}</span>
                            </div>
                        </div>

                        <!-- Email & Username Grid -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Email Address <span class="text-rose-600">*</span></label>
                                <input
                                    type="email"
                                    v-model="profileForm.email"
                                    required
                                    class="w-full bg-[#F8FAFC] focus:bg-white border border-[#E2E8F0] focus:border-blue-600 rounded-[3px] px-3 py-1.5 text-xs text-slate-800 transition"
                                />
                                <span v-if="profileForm.errors.email" class="text-[11px] text-rose-600 mt-1 block">{{ profileForm.errors.email }}</span>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Account Username (System Locked)</label>
                                <input
                                    type="text"
                                    :value="profile.username"
                                    disabled
                                    class="w-full bg-slate-100 border border-slate-200 text-slate-500 rounded-[3px] px-3 py-1.5 text-xs font-mono cursor-not-allowed"
                                />
                            </div>
                        </div>

                        <!-- Phone & Company Grid -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Phone / WhatsApp Number</label>
                                <input
                                    type="text"
                                    v-model="profileForm.phone"
                                    placeholder="+8801XXXXXXXXX"
                                    class="w-full bg-[#F8FAFC] focus:bg-white border border-[#E2E8F0] focus:border-blue-600 rounded-[3px] px-3 py-1.5 text-xs text-slate-800 transition"
                                />
                                <span v-if="profileForm.errors.phone" class="text-[11px] text-rose-600 mt-1 block">{{ profileForm.errors.phone }}</span>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Company / Organization</label>
                                <input
                                    type="text"
                                    v-model="profileForm.company"
                                    placeholder="e.g. DeepTouch IT Limited"
                                    class="w-full bg-[#F8FAFC] focus:bg-white border border-[#E2E8F0] focus:border-blue-600 rounded-[3px] px-3 py-1.5 text-xs text-slate-800 transition"
                                />
                                <span v-if="profileForm.errors.company" class="text-[11px] text-rose-600 mt-1 block">{{ profileForm.errors.company }}</span>
                            </div>
                        </div>

                        <!-- Street Address -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Street Address</label>
                            <input
                                type="text"
                                v-model="profileForm.address"
                                placeholder="House / Road / Area Details"
                                class="w-full bg-[#F8FAFC] focus:bg-white border border-[#E2E8F0] focus:border-blue-600 rounded-[3px] px-3 py-1.5 text-xs text-slate-800 transition"
                            />
                            <span v-if="profileForm.errors.address" class="text-[11px] text-rose-600 mt-1 block">{{ profileForm.errors.address }}</span>
                        </div>

                        <!-- City, State, Country, Zip -->
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">City</label>
                                <input
                                    type="text"
                                    v-model="profileForm.city"
                                    placeholder="Dhaka"
                                    class="w-full bg-[#F8FAFC] focus:bg-white border border-[#E2E8F0] focus:border-blue-600 rounded-[3px] px-3 py-1.5 text-xs text-slate-800 transition"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">State / Div</label>
                                <input
                                    type="text"
                                    v-model="profileForm.state"
                                    placeholder="Dhaka"
                                    class="w-full bg-[#F8FAFC] focus:bg-white border border-[#E2E8F0] focus:border-blue-600 rounded-[3px] px-3 py-1.5 text-xs text-slate-800 transition"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Postal Code</label>
                                <input
                                    type="text"
                                    v-model="profileForm.zip_code"
                                    placeholder="1207"
                                    class="w-full bg-[#F8FAFC] focus:bg-white border border-[#E2E8F0] focus:border-blue-600 rounded-[3px] px-3 py-1.5 text-xs text-slate-800 transition"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Country</label>
                                <input
                                    type="text"
                                    v-model="profileForm.country"
                                    placeholder="Bangladesh"
                                    class="w-full bg-[#F8FAFC] focus:bg-white border border-[#E2E8F0] focus:border-blue-600 rounded-[3px] px-3 py-1.5 text-xs text-slate-800 transition"
                                />
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                            <span v-if="profileForm.recentlySuccessful" class="text-xs font-bold text-emerald-600 flex items-center gap-1">
                                <CheckCircleIcon class="w-4 h-4" />
                                <span>Changes saved successfully.</span>
                            </span>
                            <span v-else></span>

                            <button
                                type="submit"
                                :disabled="profileForm.processing"
                                class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold transition shadow-2xs cursor-pointer flex items-center gap-1.5 disabled:opacity-50"
                            >
                                <ArrowPathIcon v-if="profileForm.processing" class="w-3.5 h-3.5 animate-spin" />
                                <span>Save Changes</span>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Right 1 Col: Account Metadata Card -->
                <div class="space-y-4">
                    <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs p-5 space-y-4">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-[4px] bg-[#2563EB] text-white flex items-center justify-center font-bold text-lg shadow-2xs">
                                {{ (profile.first_name || profile.username || 'U')[0].toUpperCase() }}
                            </div>
                            <div>
                                <h4 class="text-sm font-bold text-slate-900">{{ profile.name }}</h4>
                                <p class="text-xs text-slate-500 font-mono">{{ profile.email }}</p>
                                <span class="inline-block mt-1 text-[9px] font-mono font-bold uppercase tracking-wider px-1.5 py-0.2 bg-blue-50 text-blue-700 border border-blue-200 rounded-[2px]">
                                    Client Member
                                </span>
                            </div>
                        </div>

                        <div class="divide-y divide-slate-100 text-xs">
                            <div class="py-2 flex items-center justify-between">
                                <span class="text-slate-500">Account ID:</span>
                                <span class="font-mono font-bold text-slate-900">#{{ profile.id }}</span>
                            </div>
                            <div class="py-2 flex items-center justify-between">
                                <span class="text-slate-500">Member Since:</span>
                                <span class="font-bold text-slate-800">{{ profile.member_since }}</span>
                            </div>
                            <div class="py-2 flex items-center justify-between">
                                <span class="text-slate-500">Last Sign-in:</span>
                                <span class="font-bold text-slate-800">{{ profile.last_login_at }}</span>
                            </div>
                            <div class="py-2 flex items-center justify-between">
                                <span class="text-slate-500">Sign-in IP:</span>
                                <span class="font-mono font-bold text-slate-700">{{ profile.last_login_ip }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Links Card -->
                    <div class="bg-[#F8FAFC] rounded-[4px] border border-[#E2E8F0] p-4 text-xs space-y-2">
                        <p class="font-bold text-slate-800">Quick Shortcuts</p>
                        <div class="space-y-1">
                            <Link :href="route('billing.invoices')" class="flex items-center justify-between py-1 text-slate-600 hover:text-blue-600">
                                <span>Billing Invoices</span>
                                <ArrowTopRightOnSquareIcon class="w-3.5 h-3.5" />
                            </Link>
                            <Link :href="route('tickets.index')" class="flex items-center justify-between py-1 text-slate-600 hover:text-blue-600">
                                <span>Support Tickets</span>
                                <ArrowTopRightOnSquareIcon class="w-3.5 h-3.5" />
                            </Link>
                            <Link :href="route('websites.index')" class="flex items-center justify-between py-1 text-slate-600 hover:text-blue-600">
                                <span>VirtualHost Domains</span>
                                <ArrowTopRightOnSquareIcon class="w-3.5 h-3.5" />
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========================================================= -->
            <!-- TAB 2: PASSWORD & AUTHENTICATION CREDENTIALS              -->
            <!-- ========================================================= -->
            <div v-if="activeTab === 'security'" class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs p-5 max-w-2xl space-y-5">
                <div class="border-b border-slate-100 pb-3">
                    <h3 class="text-sm font-bold text-slate-900">Update Password Credentials</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Ensure your account is using a long, random password to stay secure against brute-force attacks.</p>
                </div>

                <form @submit.prevent="updatePassword" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Current Password <span class="text-rose-600">*</span></label>
                        <input
                            type="password"
                            v-model="passwordForm.current_password"
                            required
                            autocomplete="current-password"
                            class="w-full bg-[#F8FAFC] focus:bg-white border border-[#E2E8F0] focus:border-blue-600 rounded-[3px] px-3 py-1.5 text-xs text-slate-800 transition"
                        />
                        <span v-if="passwordForm.errors.current_password" class="text-[11px] text-rose-600 mt-1 block">{{ passwordForm.errors.current_password }}</span>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">New Password <span class="text-rose-600">*</span></label>
                        <input
                            type="password"
                            v-model="passwordForm.password"
                            required
                            autocomplete="new-password"
                            class="w-full bg-[#F8FAFC] focus:bg-white border border-[#E2E8F0] focus:border-blue-600 rounded-[3px] px-3 py-1.5 text-xs text-slate-800 transition"
                        />
                        <span v-if="passwordForm.errors.password" class="text-[11px] text-rose-600 mt-1 block">{{ passwordForm.errors.password }}</span>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Confirm New Password <span class="text-rose-600">*</span></label>
                        <input
                            type="password"
                            v-model="passwordForm.password_confirmation"
                            required
                            autocomplete="new-password"
                            class="w-full bg-[#F8FAFC] focus:bg-white border border-[#E2E8F0] focus:border-blue-600 rounded-[3px] px-3 py-1.5 text-xs text-slate-800 transition"
                        />
                        <span v-if="passwordForm.errors.password_confirmation" class="text-[11px] text-rose-600 mt-1 block">{{ passwordForm.errors.password_confirmation }}</span>
                    </div>

                    <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                        <span v-if="passwordSuccess" class="text-xs font-bold text-emerald-600 flex items-center gap-1">
                            <CheckCircleIcon class="w-4 h-4" />
                            <span>Password updated successfully.</span>
                        </span>
                        <span v-else></span>

                        <button
                            type="submit"
                            :disabled="passwordForm.processing"
                            class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold transition shadow-2xs cursor-pointer flex items-center gap-1.5 disabled:opacity-50"
                        >
                            <ArrowPathIcon v-if="passwordForm.processing" class="w-3.5 h-3.5 animate-spin" />
                            <span>Update Password</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- ========================================================= -->
            <!-- TAB 3: TWO-FACTOR AUTHENTICATION (2FA)                    -->
            <!-- ========================================================= -->
            <div v-if="activeTab === '2fa'" class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs p-5 max-w-2xl space-y-5">
                <div class="border-b border-slate-100 pb-3">
                    <h3 class="text-sm font-bold text-slate-900">Two-Factor Authentication (TOTP)</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Add additional security to your hosting account using Google Authenticator, Authy, or 1Password.</p>
                </div>

                <div class="p-4 rounded-[4px] border border-slate-200 bg-[#F8FAFC] flex items-start gap-3">
                    <div class="w-9 h-9 rounded-[3px] bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center shrink-0">
                        <DevicePhoneMobileIcon class="w-5 h-5" />
                    </div>
                    <div class="space-y-1 flex-1">
                        <p class="text-xs font-bold text-slate-900">
                            Status: 
                            <span :class="profile.two_factor_enabled ? 'text-emerald-600' : 'text-slate-500'">
                                {{ profile.two_factor_enabled ? 'Enabled & Confirmed' : 'Not Configured' }}
                            </span>
                        </p>
                        <p class="text-xs text-slate-500">
                            When two-factor authentication is enabled, you will be prompted for a secure, random 6-digit token during authentication.
                        </p>
                    </div>
                </div>

                <div class="p-4 rounded-[4px] bg-slate-50 border border-slate-200 text-xs text-slate-600 space-y-2">
                    <p class="font-bold text-slate-800">Supported Authentication Apps:</p>
                    <ul class="list-disc list-inside space-y-1 text-slate-500">
                        <li>Google Authenticator (Android & iOS)</li>
                        <li>Microsoft Authenticator</li>
                        <li>Authy 2-Factor Authentication</li>
                    </ul>
                </div>
            </div>

            <!-- ========================================================= -->
            <!-- TAB 4: ACTIVE SESSIONS & SECURITY AUDIT LOG               -->
            <!-- ========================================================= -->
            <div v-if="activeTab === 'sessions'" class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs overflow-hidden">
                <div class="p-4 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Account Security & Activity Audit Log</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Real-time log of security events, password modifications, and authentication attempts.</p>
                    </div>
                    <Link :href="route('advanced.activity-log')" class="text-xs font-bold text-blue-600 hover:underline flex items-center gap-1">
                        <span>View Full Log</span>
                        <ArrowTopRightOnSquareIcon class="w-3.5 h-3.5" />
                    </Link>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-700">
                        <thead class="bg-[#F8FAFC] border-b border-[#E2E8F0] text-[11px] uppercase font-mono text-slate-500">
                            <tr>
                                <th class="px-4 py-2.5">Event Action</th>
                                <th class="px-4 py-2.5">Description</th>
                                <th class="px-4 py-2.5">Origin IP</th>
                                <th class="px-4 py-2.5">Timestamp</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-if="activityLogs.length === 0">
                                <td colspan="4" class="px-4 py-8 text-center text-slate-400">
                                    No recent security logs recorded.
                                </td>
                            </tr>
                            <tr v-for="log in activityLogs" :key="log.id" class="hover:bg-slate-50 transition">
                                <td class="px-4 py-2.5 font-mono font-bold text-slate-900">
                                    <span class="px-1.5 py-0.2 bg-blue-50 text-blue-700 border border-blue-200 rounded-[2px] text-[10px]">
                                        {{ log.action }}
                                    </span>
                                </td>
                                <td class="px-4 py-2.5 text-slate-600 max-w-xs truncate">{{ log.description }}</td>
                                <td class="px-4 py-2.5 font-mono text-slate-600">{{ log.ip_address || '127.0.0.1' }}</td>
                                <td class="px-4 py-2.5 text-slate-400 font-mono">{{ log.created_at }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ========================================================= -->
            <!-- TAB 5: ACCOUNT DANGER ZONE                                -->
            <!-- ========================================================= -->
            <div v-if="activeTab === 'danger'" class="bg-white rounded-[4px] border border-rose-200 shadow-2xs p-5 max-w-2xl space-y-5">
                <div class="border-b border-rose-100 pb-3">
                    <h3 class="text-sm font-bold text-rose-700 flex items-center gap-1.5">
                        <ExclamationTriangleIcon class="w-4 h-4" />
                        <span>Delete Client Account</span>
                    </h3>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Once your account is deleted, all of its resources, virtual host databases, and backups will be permanently wiped.
                    </p>
                </div>

                <div v-if="!confirmingUserDeletion">
                    <button
                        type="button"
                        @click="confirmingUserDeletion = true"
                        class="px-4 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-[3px] text-xs font-bold transition shadow-2xs cursor-pointer"
                    >
                        Request Account Deletion
                    </button>
                </div>

                <div v-else class="p-4 bg-rose-50 border border-rose-200 rounded-[4px] space-y-3">
                    <p class="text-xs font-bold text-rose-900">Are you sure you want to delete your hosting account?</p>
                    <p class="text-xs text-rose-700">Please enter your password to confirm permanent account deletion.</p>

                    <form @submit.prevent="deleteAccount" class="space-y-3">
                        <div>
                            <input
                                type="password"
                                v-model="deleteForm.password"
                                placeholder="Enter your current password"
                                required
                                class="w-full bg-white border border-rose-300 focus:border-rose-600 rounded-[3px] px-3 py-1.5 text-xs text-slate-800"
                            />
                            <span v-if="deleteForm.errors.password" class="text-[11px] text-rose-600 mt-1 block">{{ deleteForm.errors.password }}</span>
                        </div>

                        <div class="flex items-center gap-2">
                            <button
                                type="submit"
                                :disabled="deleteForm.processing"
                                class="px-4 py-1.5 bg-rose-700 hover:bg-rose-800 text-white rounded-[3px] text-xs font-bold transition cursor-pointer"
                            >
                                Confirm Permanent Deletion
                            </button>

                            <button
                                type="button"
                                @click="confirmingUserDeletion = false"
                                class="px-3 py-1.5 bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 rounded-[3px] text-xs font-bold transition cursor-pointer"
                            >
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
