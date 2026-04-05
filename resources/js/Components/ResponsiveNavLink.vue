<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    href:   { type: String,  required: true },
    active: { type: Boolean, default: false },
});

const classes = computed(() =>
    props.active
        ? 'flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium text-cyan-400 bg-cyan-500/10 border border-cyan-500/20 transition-colors'
        : 'flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium text-slate-400 hover:text-slate-100 hover:bg-slate-800/70 border border-transparent transition-colors',
);
</script>

<template>
    <Link :href="href" :class="classes">
        <slot />
    </Link>
</template>
