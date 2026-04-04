<script setup>
defineProps({
    prioridad: { type: String, required: true },
})

const config = {
    critica: { label: 'Crítica', class: 'bg-red-100 text-red-700 ring-1 ring-red-300' },
    alta:    { label: 'Alta',    class: 'bg-orange-100 text-orange-700' },
    media:   { label: 'Media',   class: 'bg-yellow-100 text-yellow-700' },
    baja:    { label: 'Baja',    class: 'bg-gray-100 text-gray-600' },
}
</script>

<template>
    <span
        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
        :class="config[prioridad]?.class ?? 'bg-gray-100 text-gray-600'"
    >
        {{ config[prioridad]?.label ?? prioridad }}
    </span>
</template>
