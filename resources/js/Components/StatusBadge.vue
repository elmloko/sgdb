<script setup>
defineProps({
    estado: { type: String, required: true },
})

const config = {
    nuevo:         { label: 'Nuevo',          class: 'bg-slate-700/60 text-slate-300 ring-1 ring-slate-600' },
    en_revision:   { label: 'En revisión',    class: 'bg-blue-500/15 text-blue-400 ring-1 ring-blue-500/30' },
    asignado:      { label: 'Asignado',       class: 'bg-violet-500/15 text-violet-400 ring-1 ring-violet-500/30' },
    en_desarrollo: { label: 'En desarrollo',  class: 'bg-amber-500/15 text-amber-400 ring-1 ring-amber-500/30' },
    en_qa:         { label: 'En QA',          class: 'bg-purple-500/15 text-purple-400 ring-1 ring-purple-500/30' },
    resuelto:      { label: 'Resuelto',       class: 'bg-emerald-500/15 text-emerald-400 ring-1 ring-emerald-500/30' },
    cerrado:       { label: 'Cerrado',        class: 'bg-emerald-500/25 text-emerald-300 ring-1 ring-emerald-500/40' },
    rechazado:     { label: 'Rechazado',      class: 'bg-rose-500/15 text-rose-400 ring-1 ring-rose-500/30' },
    reabierto:     { label: 'Reabierto',      class: 'bg-orange-500/15 text-orange-400 ring-1 ring-orange-500/30' },
}
</script>

<template>
    <span
        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
        :class="config[estado]?.class ?? 'bg-slate-700/60 text-slate-400 ring-1 ring-slate-600'"
    >
        {{ config[estado]?.label ?? estado }}
    </span>
</template>
