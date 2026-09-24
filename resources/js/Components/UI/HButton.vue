<script setup>
defineProps({
    variant: {
        type: String,
        default: 'primary' // 'primary', 'secondary', 'ghost', 'danger', 'success', 'purple-soft'
    },
    size: {
        type: String,
        default: 'md' // 'xs', 'sm', 'md', 'lg'
    },
    loading: {
        type: Boolean,
        default: false
    },
    disabled: {
        type: Boolean,
        default: false
    },
    type: {
        type: String,
        default: 'button'
    }
})

const variantClasses = {
    primary: 'bg-brand-600 hover:bg-brand-700 text-white shadow-hbtn-primary font-bold border border-brand-600/30',
    secondary: 'bg-white hover:bg-hslate-50 text-hslate-700 border border-hslate-200 shadow-xs font-semibold hover:border-hslate-300',
    ghost: 'bg-transparent hover:bg-hslate-100 text-hslate-600 hover:text-hslate-900 font-semibold',
    danger: 'bg-rose-600 hover:bg-rose-700 text-white shadow-xs font-bold border border-rose-600/30',
    success: 'bg-emerald-600 hover:bg-emerald-700 text-white shadow-xs font-bold border border-emerald-600/30',
    'purple-soft': 'bg-brand-50 hover:bg-brand-100 text-brand-700 border border-brand-200 font-bold',
}

const sizeClasses = {
    xs: 'px-2.5 py-1 text-[11px] rounded-lg gap-1',
    sm: 'px-3 py-1.5 text-xs rounded-xl gap-1.5',
    md: 'px-4 py-2.5 text-xs rounded-xl gap-2',
    lg: 'px-5 py-3 text-sm rounded-2xl gap-2.5',
}
</script>

<template>
    <button
        :type="type"
        :disabled="disabled || loading"
        :class="[
            'inline-flex items-center justify-center transition-all duration-150 cursor-pointer select-none active:scale-[0.98] disabled:opacity-50 disabled:cursor-not-allowed disabled:active:scale-100',
            variantClasses[variant] || variantClasses.primary,
            sizeClasses[size] || sizeClasses.md
        ]"
    >
        <svg 
            v-if="loading" 
            class="animate-spin -ml-1 mr-2 h-4 w-4 text-current" 
            xmlns="http://www.w3.org/2000/svg" 
            fill="none" 
            viewBox="0 0 24 24"
        >
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>

        <slot />
    </button>
</template>
