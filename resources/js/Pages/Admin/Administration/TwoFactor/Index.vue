<script setup>
import { ref, computed } from 'vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import RefreshButton from '@/Components/UI/RefreshButton.vue'
import InfoCardsGrid from '@/Components/UI/InfoCardsGrid.vue'
import InfoCard from '@/Components/UI/InfoCard.vue'
import DataTableFilter from '@/Components/UI/DataTableFilter.vue'
import FilterSelect from '@/Components/UI/FilterSelect.vue'
import RowActionDropdown from '@/Components/UI/RowActionDropdown.vue'

import {
    PlusIcon,
    ArrowPathIcon,
    CheckIcon,
    XMarkIcon,
    ShieldCheckIcon,
    FingerPrintIcon,
    KeyIcon,
    LockClosedIcon,
    ExclamationTriangleIcon,
    AdjustmentsHorizontalIcon,
    ArrowLeftIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    operators: {
        type: Object,
        default: () => ({ data: [], links: [] }),
    },
    stats: {
        type: Object,
        default: () => ({
            compliance_percentage: 0,
            confirmed_count: 0,
            pending_count: 0,
            disabled_count: 0,
            total_operators: 0,
        }),
    },
    policy: {
        type: Object,
        default: () => ({
            admin_enforcement_policy: 'enforced_all',
            grace_period_days: 3,
            allowed_methods: ['totp_authenticator', 'email_otp', 'webauthn_hardware'],
            remember_device_days: 30,
            client_policy: 'optional',
        }),
    },
    filters: {
        type: Object,
        default: () => ({ search: '', compliance: 'all' }),
    },
})

// Flash Feedback Toast
const feedbackMsg = ref('')

// Filter State
const search = ref(props.filters?.search || '')
const currentCompliance = ref(props.filters?.compliance || 'all')

const applyFilters = () => {
    router.get(route('admin.administration.2fa'), {
        search: search.value || undefined,
        compliance: currentCompliance.value !== 'all' ? currentCompliance.value : undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    })
}

const selectCompliance = (compliance) => {
    currentCompliance.value = compliance
    applyFilters()
}

const resetFilters = () => {
    search.value = ''
    currentCompliance.value = 'all'
    applyFilters()
}

// 1. CONFIGURE POLICY MODAL
const showPolicyModal = ref(false)
const policyForm = useForm({
    admin_enforcement_policy: props.policy?.admin_enforcement_policy || 'enforced_all',
    grace_period_days: props.policy?.grace_period_days || 3,
    allowed_methods: props.policy?.allowed_methods || ['totp_authenticator'],
    remember_device_days: props.policy?.remember_device_days || 30,
})

const openPolicyModal = () => {
    policyForm.admin_enforcement_policy = props.policy?.admin_enforcement_policy || 'enforced_all'
    policyForm.grace_period_days = props.policy?.grace_period_days || 3
    policyForm.allowed_methods = props.policy?.allowed_methods || ['totp_authenticator']
    policyForm.remember_device_days = props.policy?.remember_device_days || 30
    showPolicyModal.value = true
}

const submitPolicy = () => {
    policyForm.post(route('admin.administration.2fa.update-policy'), {
        preserveScroll: true,
        onSuccess: () => {
            showPolicyModal.value = false
            feedbackMsg.value = 'Global 2FA security policy updated.'
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// 2. TOGGLE ENFORCE
const toggleEnforce = (user) => {
    router.post(route('admin.administration.2fa.toggle-enforce', user.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            feedbackMsg.value = `2FA enforcement toggled for ${user.name}.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// 3. RESET 2FA
const reset2fa = (user) => {
    if (confirm(`Reset 2FA keys for ${user.name}? The operator must register a new authenticator token on next login.`)) {
        router.post(route('admin.administration.2fa.reset', user.id), {}, {
            preserveScroll: true,
            onSuccess: () => {
                feedbackMsg.value = `2FA secret reset for ${user.name}.`
                setTimeout(() => { feedbackMsg.value = '' }, 4000)
            }
        })
    }
}
</script>

<template>
    <Head title="Two-Factor Security - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Administration', href: route('admin.administration.2fa') },
                    { label: 'Two-Factor Auth' }
                ]"
            >
                <template #actions>
                    <button 
                        type="button" 
                        @click="openPolicyModal"
                        class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold flex items-center gap-1.5 shadow-2xs transition cursor-pointer"
                    >
                        <AdjustmentsHorizontalIcon class="w-3.5 h-3.5" />
                        <span>Configure Global Policy</span>
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
                    title="Compliance Rate"
                    :value="`${stats.compliance_percentage || 100}%`"
                    badge="Adherence"
                    badgeType="success"
                    color="blue"
                    :icon="FingerPrintIcon"
                />

                <InfoCard
                    title="2FA Confirmed Live"
                    :value="String(stats.confirmed_count || operators.data?.filter(o => o.has_2fa).length || 0)"
                    badge="Enrolled"
                    badgeType="success"
                    color="emerald"
                    :icon="ShieldCheckIcon"
                />

                <InfoCard
                    title="Pending Setup Enforced"
                    :value="String(stats.pending_count || 0)"
                    badge="Pending"
                    badgeType="warning"
                    color="purple"
                    :icon="KeyIcon"
                />

                <InfoCard
                    title="Unprotected Operators"
                    :value="String(stats.disabled_count || 0)"
                    badge="Unsecured"
                    badgeType="danger"
                    color="rose"
                    :icon="ExclamationTriangleIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Compliance Tabs -->
            <div class="flex items-center justify-between border-b border-slate-200 pb-2">
                <div class="flex flex-wrap items-center gap-2">
                    <button
                        v-for="c in ['all', 'confirmed', 'pending', 'disabled']"
                        :key="c"
                        type="button"
                        @click="selectCompliance(c)"
                        :class="currentCompliance === c ? 'bg-blue-600 text-white' : 'bg-white text-slate-700 hover:bg-slate-50 border border-slate-200'"
                        class="px-3 py-1.5 rounded-[3px] font-bold text-xs uppercase font-mono transition cursor-pointer shadow-2xs"
                    >
                        {{ c }}
                    </button>
                </div>
            </div>

            <!-- 4. Standard Filter Toolbar -->
            <DataTableFilter
                v-model="search"
                searchPlaceholder="Search operator name, email address, role..."
                @search="applyFilters"
                @filter="applyFilters"
                @reset="resetFilters"
            />

            <!-- 5. Standard Data Table -->
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-center border-collapse text-xs whitespace-nowrap">
                        <thead>
                            <tr class="bg-slate-50/90 border-b border-slate-200 text-[10.5px] font-bold uppercase tracking-wider text-slate-600 whitespace-nowrap">
                                <th class="py-2.5 px-3 border-r border-slate-200 w-12">#</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Operator Account</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Role Privilege</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">2FA Status</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Mandatory Policy</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Enrolled Method</th>
                                <th class="py-2.5 px-3 w-14 text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                            <tr v-for="(op, index) in operators.data" :key="op.id || index" class="hover:bg-blue-50/30 transition">
                                <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 font-mono text-[11px] whitespace-nowrap">
                                    {{ index + 1 }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-bold text-slate-900">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-xs border border-blue-100 shrink-0">
                                            <FingerPrintIcon class="w-3.5 h-3.5" />
                                        </div>
                                        <div>
                                            <span class="block leading-tight font-bold">{{ op.name }}</span>
                                            <span class="text-[10px] text-slate-400 font-mono block">{{ op.email }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase bg-slate-100 text-slate-700 border border-slate-200 font-mono">
                                        {{ op.role || 'Super Admin' }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span 
                                        :class="op.has_2fa ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200'"
                                        class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase border font-mono"
                                    >
                                        {{ op.has_2fa ? 'Confirmed' : 'Not Setup' }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span 
                                        :class="op.two_factor_enforced ? 'bg-purple-50 text-purple-700 border-purple-200' : 'bg-slate-100 text-slate-500 border-slate-200'"
                                        class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase border font-mono"
                                    >
                                        {{ op.two_factor_enforced ? 'Mandatory' : 'Optional' }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono text-[11px] text-slate-700">
                                    {{ op.two_factor_type || (op.has_2fa ? 'TOTP Authenticator' : 'None') }}
                                </td>
                                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                    <RowActionDropdown>
                                        <button
                                            type="button"
                                            @click="toggleEnforce(op)"
                                            class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 font-medium cursor-pointer"
                                        >
                                            <LockClosedIcon class="w-3.5 h-3.5 text-slate-500" />
                                            <span>{{ op.two_factor_enforced ? 'Make Optional' : 'Force Mandatory' }}</span>
                                        </button>

                                        <button
                                            v-if="op.has_2fa"
                                            type="button"
                                            @click="reset2fa(op)"
                                            class="w-full text-left px-3 py-1.5 text-xs text-rose-600 hover:bg-rose-50 flex items-center gap-2 font-medium cursor-pointer"
                                        >
                                            <KeyIcon class="w-3.5 h-3.5" />
                                            <span>Reset 2FA Keys</span>
                                        </button>
                                    </RowActionDropdown>
                                </td>
                            </tr>

                            <tr v-if="!operators.data || operators.data.length === 0">
                                <td colspan="7" class="py-12 text-center text-slate-400 font-sans">
                                    No operator accounts found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- CONFIGURE POLICY MODAL -->
        <div v-if="showPolicyModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold border border-blue-100">
                            <AdjustmentsHorizontalIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Configure Global 2FA Policy
                        </h3>
                    </div>
                    <button @click="showPolicyModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitPolicy" class="p-4 space-y-3 text-xs">
                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Administrator Enforcement Level</label>
                        <select v-model="policyForm.admin_enforcement_policy" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer">
                            <option value="enforced_all">Enforce On All Administrators & Staff</option>
                            <option value="enforced_super_only">Enforce On Super Administrators Only</option>
                            <option value="optional">Optional for All Operators</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Grace Period (Days)</label>
                            <input v-model.number="policyForm.grace_period_days" type="number" min="0" max="30" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                        </div>

                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Remember Device (Days)</label>
                            <input v-model.number="policyForm.remember_device_days" type="number" min="0" max="90" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                        </div>
                    </div>

                    <div class="px-4 py-2.5 -mx-4 -mb-4 mt-4 bg-slate-50/70 border-t border-slate-100 flex items-center justify-end gap-2 text-xs">
                        <button
                            type="button"
                            @click="showPolicyModal = false"
                            class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] border border-slate-200 shadow-2xs transition cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="policyForm.processing"
                            class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-[3px] transition shadow-2xs cursor-pointer disabled:opacity-50"
                        >
                            {{ policyForm.processing ? 'Saving...' : 'Save Policy' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
