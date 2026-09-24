<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import { ArrowLeftIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
    resellers: {
        type: Array,
        default: () => [],
    },
})

const form = useForm({
    first_name: props.user.first_name || '',
    last_name: props.user.last_name || '',
    username: props.user.username || '',
    email: props.user.email || '',
    password: '',
    role: props.user.role || 'client',
    status: props.user.status || 'active',
    reseller_id: props.user.reseller_id || '',
    company: props.user.company || '',
    phone: props.user.phone || '',
    address: props.user.address || '',
    city: props.user.city || '',
    state: props.user.state || '',
    country: props.user.country || '',
    zip_code: props.user.zip_code || '',
})

const submit = () => {
    form.put(route('admin.users.update', props.user.id))
}
</script>

<template>
    <Head :title="`Edit Customer: ${user.name} - DeepTouchHost`" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Customers', href: '#' },
                    { label: 'All Customers', href: route('admin.users.index') },
                    { label: `Edit: ${user.name}` }
                ]"
            >
                <template #actions>
                    <Link
                        :href="route('admin.users.index')"
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        <ArrowLeftIcon class="w-3.5 h-3.5 text-slate-500" />
                        <span>Back to Directory</span>
                    </Link>
                </template>
            </PageHeader>

            <!-- 2. Full-Width Form -->
            <form @submit.prevent="submit" class="space-y-3.5 text-xs">
                <!-- Section 1: Account Identity & Credentials -->
                <div class="bg-white rounded-[4px] border border-[#E2E8F0] p-4 shadow-2xs space-y-3">
                    <div class="flex items-center gap-2 pb-2 border-b border-slate-100">
                        <span class="w-1.5 h-1.5 rounded-full bg-blue-600"></span>
                        <h2 class="text-xs font-bold uppercase tracking-wider text-slate-900">Personal Identity & Authentication</h2>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">First Name <span class="text-rose-500">*</span></label>
                            <input 
                                v-model="form.first_name" 
                                type="text" 
                                required 
                                class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 transition-all shadow-2xs"
                            />
                            <span v-if="form.errors.first_name" class="text-rose-600 text-[10.5px] font-semibold block">{{ form.errors.first_name }}</span>
                        </div>

                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Last Name</label>
                            <input 
                                v-model="form.last_name" 
                                type="text" 
                                class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 transition-all shadow-2xs"
                            />
                            <span v-if="form.errors.last_name" class="text-rose-600 text-[10.5px] font-semibold block">{{ form.errors.last_name }}</span>
                        </div>

                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Email Address <span class="text-rose-500">*</span></label>
                            <input 
                                v-model="form.email" 
                                type="email" 
                                required 
                                class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 transition-all shadow-2xs"
                            />
                            <span v-if="form.errors.email" class="text-rose-600 text-[10.5px] font-semibold block">{{ form.errors.email }}</span>
                        </div>

                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Username Identifier <span class="text-rose-500">*</span></label>
                            <input 
                                v-model="form.username" 
                                type="text" 
                                required 
                                class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 transition-all shadow-2xs font-mono"
                            />
                            <span v-if="form.errors.username" class="text-rose-600 text-[10.5px] font-semibold block">{{ form.errors.username }}</span>
                        </div>

                        <div class="space-y-1 sm:col-span-2">
                            <label class="block font-bold text-slate-700">Change Password (Leave blank to keep existing)</label>
                            <input 
                                v-model="form.password" 
                                type="password" 
                                placeholder="Enter new password if updating" 
                                class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 transition-all shadow-2xs"
                            />
                            <span v-if="form.errors.password" class="text-rose-600 text-[10.5px] font-semibold block">{{ form.errors.password }}</span>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Role & Access Governance -->
                <div class="bg-white rounded-[4px] border border-[#E2E8F0] p-4 shadow-2xs space-y-3">
                    <div class="flex items-center gap-2 pb-2 border-b border-slate-100">
                        <span class="w-1.5 h-1.5 rounded-full bg-blue-600"></span>
                        <h2 class="text-xs font-bold uppercase tracking-wider text-slate-900">Role & Access Governance</h2>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Account Role <span class="text-rose-500">*</span></label>
                            <select 
                                v-model="form.role" 
                                class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer"
                            >
                                <option value="client">Client (Hosting Customer)</option>
                                <option value="reseller">Reseller (Multi-Tenant)</option>
                                <option value="admin">Administrator (Full Access)</option>
                            </select>
                        </div>

                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Lifecycle Status <span class="text-rose-500">*</span></label>
                            <select 
                                v-model="form.status" 
                                class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer"
                            >
                                <option value="active">Active (Full Access)</option>
                                <option value="suspended">Suspended (Locked Out)</option>
                            </select>
                        </div>

                        <div v-if="form.role === 'client' && resellers && resellers.length > 0" class="space-y-1">
                            <label class="block font-bold text-slate-700">Parent Reseller</label>
                            <select 
                                v-model="form.reseller_id" 
                                class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer"
                            >
                                <option value="">None (Direct Platform Customer)</option>
                                <option v-for="r in resellers" :key="r.id" :value="r.id">
                                    {{ r.name }} ({{ r.email }})
                                </option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Section 3: Company & Contact Information -->
                <div class="bg-white rounded-[4px] border border-[#E2E8F0] p-4 shadow-2xs space-y-3">
                    <div class="flex items-center gap-2 pb-2 border-b border-slate-100">
                        <span class="w-1.5 h-1.5 rounded-full bg-blue-600"></span>
                        <h2 class="text-xs font-bold uppercase tracking-wider text-slate-900">Organization & Contact Coordinates</h2>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Company Name</label>
                            <input 
                                v-model="form.company" 
                                type="text" 
                                class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 transition-all shadow-2xs"
                            />
                        </div>

                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Contact Phone</label>
                            <input 
                                v-model="form.phone" 
                                type="tel" 
                                class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 transition-all shadow-2xs"
                            />
                        </div>

                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Street Address</label>
                            <input 
                                v-model="form.address" 
                                type="text" 
                                class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 transition-all shadow-2xs"
                            />
                        </div>

                        <div class="grid grid-cols-3 gap-2">
                            <div class="space-y-1">
                                <label class="block font-bold text-slate-700">City</label>
                                <input 
                                    v-model="form.city" 
                                    type="text" 
                                    class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 transition-all shadow-2xs"
                                />
                            </div>

                            <div class="space-y-1">
                                <label class="block font-bold text-slate-700">Postal Code</label>
                                <input 
                                    v-model="form.zip_code" 
                                    type="text" 
                                    class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 transition-all shadow-2xs font-mono"
                                />
                            </div>

                            <div class="space-y-1">
                                <label class="block font-bold text-slate-700">Country</label>
                                <input 
                                    v-model="form.country" 
                                    type="text" 
                                    class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 transition-all shadow-2xs"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Submit Footer -->
                <div class="flex items-center justify-end gap-2 pt-2">
                    <Link
                        :href="route('admin.users.index')"
                        class="px-3.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        Cancel
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-[3px] shadow-2xs transition cursor-pointer disabled:opacity-50"
                    >
                        {{ form.processing ? 'Saving...' : 'Update Customer' }}
                    </button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
