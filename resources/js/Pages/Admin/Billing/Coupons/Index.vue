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
    TicketIcon,
    PercentBadgeIcon,
    BanknotesIcon,
    SparklesIcon,
    TrashIcon,
    PencilSquareIcon,
    ClockIcon,
    DocumentDuplicateIcon,
    BoltIcon,
    ArrowLeftIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    coupons: {
        type: Object,
        default: () => ({ data: [], links: [] }),
    },
    stats: {
        type: Object,
        default: () => ({
            total_coupons: 0,
            active_coupons: 0,
            total_redemptions: 0,
            avg_percent: 0,
        }),
    },
    plans: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({ search: '', type: '', status: '' }),
    },
})

// Flash Feedback Toast
const feedbackMsg = ref('')

// Filter State
const search = ref(props.filters?.search || '')
const currentType = ref(props.filters?.type || 'all')
const currentStatus = ref(props.filters?.status || 'all')

const applyFilters = () => {
    router.get(route('admin.billing.coupons'), {
        search: search.value || undefined,
        type: currentType.value !== 'all' ? currentType.value : undefined,
        status: currentStatus.value !== 'all' ? currentStatus.value : undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    })
}

const selectType = (type) => {
    currentType.value = type
    applyFilters()
}

const resetFilters = () => {
    search.value = ''
    currentType.value = 'all'
    currentStatus.value = 'all'
    applyFilters()
}

// 1. CREATE / EDIT COUPON MODAL
const showModal = ref(false)
const editingCoupon = ref(null)
const couponForm = useForm({
    code: '',
    type: 'percentage',
    value: 20,
    min_spend: 0,
    usage_limit: 100,
    expires_at: '',
    is_active: true,
})

const openCreateModal = () => {
    editingCoupon.value = null
    couponForm.reset()
    couponForm.type = 'percentage'
    couponForm.value = 20
    couponForm.usage_limit = 100
    couponForm.is_active = true
    showModal.value = true
}

const openEditModal = (c) => {
    editingCoupon.value = c
    couponForm.code = c.code
    couponForm.type = c.type || 'percentage'
    couponForm.value = c.value || 0
    couponForm.min_spend = c.min_spend || 0
    couponForm.usage_limit = c.usage_limit || 0
    couponForm.expires_at = c.expires_at ? c.expires_at.split('T')[0] : ''
    couponForm.is_active = c.is_active !== false
    showModal.value = true
}

const submitCoupon = () => {
    if (editingCoupon.value) {
        couponForm.put(route('admin.billing.coupons.update', editingCoupon.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                showModal.value = false
                feedbackMsg.value = 'Coupon updated.'
                setTimeout(() => { feedbackMsg.value = '' }, 4000)
            }
        })
    } else {
        couponForm.post(route('admin.billing.coupons.store'), {
            preserveScroll: true,
            onSuccess: () => {
                showModal.value = false
                feedbackMsg.value = 'New coupon created.'
                setTimeout(() => { feedbackMsg.value = '' }, 4000)
            }
        })
    }
}

// 2. TOGGLE COUPON
const toggleCoupon = (c) => {
    router.post(route('admin.billing.coupons.toggle', c.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            feedbackMsg.value = 'Coupon status updated.'
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// 3. DELETE MODAL
const showDeleteModal = ref(false)
const couponToDelete = ref(null)
const deleteForm = useForm({})

const openDeleteModal = (c) => {
    couponToDelete.value = c
    showDeleteModal.value = true
}

const submitDelete = () => {
    if (!couponToDelete.value) return
    deleteForm.delete(route('admin.billing.coupons.destroy', couponToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false
            feedbackMsg.value = 'Coupon deleted.'
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

const copyCode = (code) => {
    navigator.clipboard.writeText(code)
    feedbackMsg.value = `Coupon code '${code}' copied.`
    setTimeout(() => { feedbackMsg.value = '' }, 2500)
}
</script>

<template>
    <Head title="Discount Coupons - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Billing', href: '#' },
                    { label: 'Billing & Finance', href: route('admin.billing.invoices') },
                    { label: 'Promotional Coupons & Vouchers' }
                ]"
            >
                <template #actions>
                    <Link
                        :href="route('admin.billing.invoices')"
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        <ArrowLeftIcon class="w-3.5 h-3.5 text-slate-500" />
                        <span>Invoices</span>
                    </Link>

                    <button 
                        type="button" 
                        @click="openCreateModal"
                        class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold flex items-center gap-1.5 shadow-2xs transition cursor-pointer"
                    >
                        <PlusIcon class="w-3.5 h-3.5" />
                        <span>Create Promo Coupon</span>
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
                    title="Configured Coupons"
                    :value="String(stats.total_coupons || coupons.data?.length || 0)"
                    badge="Vouchers"
                    badgeType="info"
                    color="blue"
                    :icon="TicketIcon"
                />

                <InfoCard
                    title="Active Valid Vouchers"
                    :value="String(stats.active_coupons || coupons.data?.filter(c => c.is_active).length || 0)"
                    badge="Redeemable"
                    badgeType="success"
                    color="emerald"
                    :icon="CheckIcon"
                />

                <InfoCard
                    title="Total Redemptions"
                    :value="String(stats.total_redemptions || 0)"
                    badge="Claimed"
                    badgeType="info"
                    color="purple"
                    :icon="SparklesIcon"
                />

                <InfoCard
                    title="Average Discount"
                    :value="`${stats.avg_percent || 20}%`"
                    badge="Savings"
                    badgeType="info"
                    color="sky"
                    :icon="PercentBadgeIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Type Tabs -->
            <div class="flex items-center justify-between border-b border-slate-200 pb-2">
                <div class="flex flex-wrap items-center gap-2">
                    <button
                        v-for="t in ['all', 'percentage', 'fixed']"
                        :key="t"
                        type="button"
                        @click="selectType(t)"
                        :class="currentType === t ? 'bg-blue-600 text-white' : 'bg-white text-slate-700 hover:bg-slate-50 border border-slate-200'"
                        class="px-3 py-1.5 rounded-[3px] font-bold text-xs uppercase font-mono transition cursor-pointer shadow-2xs"
                    >
                        {{ t }}
                    </button>
                </div>
            </div>

            <!-- 4. Standard Filter Toolbar -->
            <DataTableFilter
                v-model="search"
                searchPlaceholder="Search coupon code, discount rate, or description..."
                @search="applyFilters"
                @filter="applyFilters"
                @reset="resetFilters"
            >
                <FilterSelect
                    v-model="currentStatus"
                    label="Coupon Status"
                    :options="[
                        { label: 'All Statuses', value: 'all' },
                        { label: 'Active & Valid', value: 'active' },
                        { label: 'Disabled / Inactive', value: 'inactive' }
                    ]"
                    placeholder="All Statuses"
                />
            </DataTableFilter>

            <!-- 5. Standard Data Table -->
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-center border-collapse text-xs whitespace-nowrap">
                        <thead>
                            <tr class="bg-slate-50/90 border-b border-slate-200 text-[10.5px] font-bold uppercase tracking-wider text-slate-600 whitespace-nowrap">
                                <th class="py-2.5 px-3 border-r border-slate-200 w-12">#</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left w-36">Coupon Promo Code</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Discount Type</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Discount Value</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Usage Count / Limit</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Expiry Date</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Status</th>
                                <th class="py-2.5 px-3 w-28">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                            <tr v-for="(c, index) in coupons.data" :key="c.id || index" class="hover:bg-blue-50/30 transition">
                                <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 font-mono text-[11px] whitespace-nowrap">
                                    {{ index + 1 }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-mono font-bold text-blue-700">
                                    <div class="flex items-center gap-1.5">
                                        <TicketIcon class="w-3.5 h-3.5 text-blue-600" />
                                        <span>{{ c.code }}</span>
                                        <button type="button" @click="copyCode(c.code)" class="text-slate-400 hover:text-slate-600 cursor-pointer" title="Copy Code">
                                            <DocumentDuplicateIcon class="w-3.5 h-3.5" />
                                        </button>
                                    </div>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase bg-slate-100 text-slate-700 border border-slate-200 font-mono">
                                        {{ c.type || 'percentage' }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono font-bold text-slate-900 text-[11px]">
                                    {{ c.type === 'percentage' ? c.value + '%' : '$' + c.value }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono text-[11px] text-slate-600">
                                    {{ c.usage_count || 0 }} / {{ c.usage_limit ? c.usage_limit : '∞' }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono text-[10.5px] text-slate-500">
                                    {{ c.expires_at ? new Date(c.expires_at).toLocaleDateString() : 'Never' }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span 
                                        :class="c.is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-500 border-slate-200'"
                                        class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase border font-mono"
                                    >
                                        {{ c.is_active ? 'Active' : 'Disabled' }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1">
                                        <RowActionDropdown>
                                            <button
                                                type="button"
                                                @click="openEditModal(c)"
                                                class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 font-medium cursor-pointer"
                                            >
                                                <PencilSquareIcon class="w-3.5 h-3.5 text-slate-500" />
                                                <span>Edit Coupon</span>
                                            </button>

                                            <button
                                                type="button"
                                                @click="toggleCoupon(c)"
                                                class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 font-medium cursor-pointer"
                                            >
                                                <BoltIcon class="w-3.5 h-3.5 text-slate-500" />
                                                <span>{{ c.is_active ? 'Disable Coupon' : 'Activate Coupon' }}</span>
                                            </button>

                                            <button
                                                type="button"
                                                @click="openDeleteModal(c)"
                                                class="w-full text-left px-3 py-1.5 text-xs text-rose-600 hover:bg-rose-50 flex items-center gap-2 font-medium cursor-pointer"
                                            >
                                                <TrashIcon class="w-3.5 h-3.5" />
                                                <span>Delete Coupon</span>
                                            </button>
                                        </RowActionDropdown>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="!coupons.data || coupons.data.length === 0">
                                <td colspan="8" class="py-12 text-center text-slate-400 font-sans">
                                    No promotional coupons found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- CREATE / EDIT MODAL -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold border border-blue-100">
                            <TicketIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            {{ editingCoupon ? 'Edit Promo Coupon' : 'Create Promo Coupon' }}
                        </h3>
                    </div>
                    <button @click="showModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitCoupon" class="p-4 space-y-3 text-xs">
                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Coupon Promo Code <span class="text-rose-500">*</span></label>
                        <input v-model="couponForm.code" type="text" required placeholder="SUMMER2026" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono uppercase focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Discount Type</label>
                            <select v-model="couponForm.type" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer">
                                <option value="percentage">Percentage (%)</option>
                                <option value="fixed">Fixed Amount ($)</option>
                            </select>
                        </div>

                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Discount Value <span class="text-rose-500">*</span></label>
                            <input v-model.number="couponForm.value" type="number" step="0.01" min="1" required class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Total Usage Limit</label>
                            <input v-model.number="couponForm.usage_limit" type="number" min="0" placeholder="100" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                        </div>

                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Expiry Date</label>
                            <input v-model="couponForm.expires_at" type="date" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                        </div>
                    </div>

                    <div class="flex items-center gap-2 pt-1">
                        <input v-model="couponForm.is_active" id="coupon_active" type="checkbox" class="rounded-[2px] text-blue-600 focus:ring-blue-500 cursor-pointer" />
                        <label for="coupon_active" class="text-xs text-slate-700 font-medium cursor-pointer">Active and available for checkout redemption</label>
                    </div>

                    <div class="px-4 py-2.5 -mx-4 -mb-4 mt-4 bg-slate-50/70 border-t border-slate-100 flex items-center justify-end gap-2 text-xs">
                        <button
                            type="button"
                            @click="showModal = false"
                            class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] border border-slate-200 shadow-2xs transition cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="couponForm.processing"
                            class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-[3px] transition shadow-2xs cursor-pointer disabled:opacity-50"
                        >
                            {{ couponForm.processing ? 'Saving...' : (editingCoupon ? 'Update Coupon' : 'Create Coupon') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- DELETE MODAL -->
        <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-rose-50 text-rose-600 flex items-center justify-center font-bold border border-rose-100">
                            <TrashIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Delete Promo Coupon
                        </h3>
                    </div>
                    <button @click="showDeleteModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <div class="p-4 space-y-2.5 text-xs">
                    <p class="text-slate-600">
                        Are you sure you want to delete coupon <strong class="text-slate-900 font-mono">[{{ couponToDelete?.code }}]</strong>?
                    </p>
                </div>

                <div class="px-4 py-2.5 bg-slate-50/70 border-t border-slate-100 flex items-center justify-end gap-2 text-xs">
                    <button
                        type="button"
                        @click="showDeleteModal = false"
                        class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        @click="submitDelete"
                        :disabled="deleteForm.processing"
                        class="px-3.5 py-1.5 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-[3px] transition shadow-2xs cursor-pointer disabled:opacity-50"
                    >
                        {{ deleteForm.processing ? 'Deleting...' : 'Delete Coupon' }}
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
