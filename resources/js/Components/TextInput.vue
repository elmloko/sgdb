<script setup>
import { onMounted, ref } from 'vue';

const model = defineModel({ type: String, required: true });
const input = ref(null);

onMounted(() => {
    if (input.value.hasAttribute('autofocus')) input.value.focus();
});

defineExpose({ focus: () => input.value.focus() });
</script>

<template>
    <input
        class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-slate-100 placeholder-slate-500 shadow-sm transition duration-150 focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 focus:outline-none disabled:opacity-50"
        v-model="model"
        ref="input"
    />
</template>
