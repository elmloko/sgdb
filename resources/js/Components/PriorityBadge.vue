<script setup>
defineProps({
    prioridad: { type: String, required: true },
})

const config = {
    critica: { label: 'Crítica', class: 'bg-rose-500/20 text-rose-400 ring-1 ring-rose-500/40' },
    alta:    { label: 'Alta',    class: 'bg-orange-500/15 text-orange-400 ring-1 ring-orange-500/30' },
    media:   { label: 'Media',   class: 'bg-amber-500/15 text-amber-400 ring-1 ring-amber-500/30' },
    baja:    { label: 'Baja',    class: 'bg-slate-700/60 text-slate-400 ring-1 ring-slate-600' },
}
</script>

<template>
    <span
        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
        :class="config[prioridad]?.class ?? 'bg-slate-700/60 text-slate-400 ring-1 ring-slate-600'"
    >
        {{ config[prioridad]?.label ?? prioridad }}
    </span>
</template>
