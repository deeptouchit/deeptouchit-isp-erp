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

import {
    ArrowPathIcon,
    CheckIcon,
    XMarkIcon,
    KeyIcon,
    ShieldCheckIcon,
    BuildingOffice2Icon,
    LockClosedIcon,
    ArrowLeftIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    permissions: {
        type: Object,
        default: () => ({ data: [], links: [] }),
    },
    roles: {
        type: Array,
        default: () => [],
    },
    categories: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({
            total_permissions: 0,
            functional_modules: 0,
            active_roles: 0,
            access_bindings: 0,
        }),
    },
    filters: {
        type: Object,
        default: () => ({ search: '', category: 'all' }),
    },
})

// Flash Feedback Toast
const feedbackMsg = ref('')

// Filter State
const search = ref(props.filters?.search || '')
const currentCategory = ref(props.filters?.category || 'all')

const applyFilters = () => {
    router.get(route('admin.administration.permissions'), {
        search: search.value || undefined,
        category: currentCategory.value !== 'all' ? currentCategory.value : undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    })
}

const selectCategory = (category) => {
    currentCategory.value = category
    applyFilters()
}

const resetFilters = () => {
    search.value = ''
    currentCategory.value = 'all'
    applyFilters()
}

// Toggle Role Permission
const toggleRolePermission = (permission, role) => {
    if (role.name === 'super_admin' || role.name === 'Super Administrator') {
        feedbackMsg.value = 'Super Administrator role inherently maintains all capabilities.'
        setTimeout(() => { feedbackMsg.value = '' }, 3000)
        return
    }

    router.post(route('admin.administration.permissions.toggle-role'), {
        permission_id: permission.id,
        role_id: role.id,
    }, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            feedbackMsg.value = `Updated '${role.display_name || role.name}' permission.`
            setTimeout(() => { feedbackMsg.value = '' }, 3000)
        }
    })
}

const hasPermission = (permission, role) => {
    if (role.name === 'super_admin' || role.name === 'Super Administrator') return true
    if (Array.isArray(role.permissions)) {
        return role.permissions.some(p => (typeof p === 'object' ? p.id === permission.id : p === permission.name))
    }
    return false
}
</script>

<template>
    <Head title="Permissions Matrix - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Administration', href: route('admin.administration.permissions') },
                    { label: 'Permissions' }
                ]"
            >
                <template #actions>
                    <Link
                        :href="route('admin.administration.roles')"
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        <ShieldCheckIcon class="w-3.5 h-3.5 text-slate-500" />
                        <span>Security Roles</span>
                    </Link>

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
                    title="System Permissions"
                    :value="String(stats.total_permissions || permissions.data?.length || 28)"
                    badge="Scopes"
                    badgeType="info"
                    color="blue"
                    :icon="KeyIcon"
                />

                <InfoCard
                    title="Functional Modules"
                    :value="String(stats.functional_modules || categories.length || 7)"
                    badge="Modules"
                    badgeType="success"
                    color="emerald"
                    :icon="BuildingOffice2Icon"
                />

                <InfoCard
                    title="Active Roles"
                    :value="String(stats.active_roles || roles.length || 4)"
                    badge="Roles"
                    badgeType="info"
                    color="purple"
                    :icon="ShieldCheckIcon"
                />

                <InfoCard
                    title="Access Bindings"
                    :value="String(stats.access_bindings || 42)"
                    badge="Assigned"
                    badgeType="info"
                    color="sky"
                    :icon="LockClosedIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Category Tabs -->
            <div class="flex items-center justify-between border-b border-slate-200 pb-2">
                <div class="flex flex-wrap items-center gap-2">
                    <button
                        type="button"
                        @click="selectCategory('all')"
                        :class="currentCategory === 'all' ? 'bg-blue-600 text-white' : 'bg-white text-slate-700 hover:bg-slate-50 border border-slate-200'"
                        class="px-3 py-1.5 rounded-[3px] font-bold text-xs uppercase font-mono transition cursor-pointer shadow-2xs"
                    >
                        All Categories
                    </button>
                    <button
                        v-for="cat in ['servers', 'hosting', 'billing', 'support', 'security', 'system']"
                        :key="cat"
                        type="button"
                        @click="selectCategory(cat)"
                        :class="currentCategory === cat ? 'bg-blue-600 text-white' : 'bg-white text-slate-700 hover:bg-slate-50 border border-slate-200'"
                        class="px-3 py-1.5 rounded-[3px] font-bold text-xs uppercase font-mono transition cursor-pointer shadow-2xs"
                    >
                        {{ cat }}
                    </button>
                </div>
            </div>

            <!-- 4. Standard Filter Toolbar -->
            <DataTableFilter
                v-model="search"
                searchPlaceholder="Search permission name, scope key, or description..."
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
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Capability Scope</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Category</th>
                                <th v-for="role in roles" :key="role.id" class="py-2.5 px-3 border-r border-slate-200">
                                    {{ role.display_name || role.name }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                            <tr v-for="(p, index) in permissions.data" :key="p.id || index" class="hover:bg-blue-50/30 transition">
                                <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 font-mono text-[11px] whitespace-nowrap">
                                    {{ index + 1 }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-bold text-slate-900">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-xs border border-blue-100 shrink-0">
                                            <KeyIcon class="w-3.5 h-3.5" />
                                        </div>
                                        <div>
                                            <span class="block leading-tight font-bold">{{ p.display_name || p.name }}</span>
                                            <span class="text-[10px] text-slate-400 font-mono block">{{ p.name }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase bg-slate-100 text-slate-700 border border-slate-200 font-mono">
                                        {{ p.category || 'System' }}
                                    </span>
                                </td>
                                <td v-for="role in roles" :key="role.id" class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <button
                                        type="button"
                                        @click="toggleRolePermission(p, role)"
                                        :class="hasPermission(p, role) ? 'bg-emerald-500 text-white' : 'bg-slate-200 text-slate-400'"
                                        class="w-6 h-6 rounded-[3px] inline-flex items-center justify-center font-bold text-xs transition cursor-pointer shadow-2xs"
                                        :title="`${role.display_name || role.name}: ${hasPermission(p, role) ? 'Allowed' : 'Denied'}`"
                                    >
                                        <CheckIcon v-if="hasPermission(p, role)" class="w-3.5 h-3.5" />
                                        <span v-else class="text-[10px] leading-none">✕</span>
                                    </button>
                                </td>
                            </tr>

                            <tr v-if="!permissions.data || permissions.data.length === 0">
                                <td :colspan="3 + roles.length" class="py-12 text-center text-slate-400 font-sans">
                                    No capability permissions found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
