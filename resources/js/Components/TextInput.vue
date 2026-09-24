<script setup>
import { onMounted, ref } from 'vue';

const model = defineModel({
    type: String,
    required: true,
});

const input = ref(null);

onMounted(() => {
    if (input.value.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value.focus() });
</script>

<template>
    <input
        class="rounded-xl border-slate-200 bg-slate-50 text-slate-900 shadow-xs focus:bg-white focus:border-violet-600 focus:ring-2 focus:ring-violet-600 px-4 py-2.5 transition-all text-sm"
        v-model="model"
        ref="input"
    />
</template>
