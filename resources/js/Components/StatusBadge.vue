<script setup>
defineProps({
    estado: { type: String, required: true },
})

const config = {
    nuevo:          { label: 'Nuevo',          class: 'bg-gray-100 text-gray-700' },
    en_revision:    { label: 'En revisión',     class: 'bg-blue-100 text-blue-700' },
    asignado:       { label: 'Asignado',        class: 'bg-indigo-100 text-indigo-700' },
    en_desarrollo:  { label: 'En desarrollo',   class: 'bg-yellow-100 text-yellow-700' },
    en_qa:          { label: 'En QA',           class: 'bg-purple-100 text-purple-700' },
    resuelto:       { label: 'Resuelto',        class: 'bg-green-100 text-green-700' },
    cerrado:        { label: 'Cerrado',         class: 'bg-green-200 text-green-800' },
    rechazado:      { label: 'Rechazado',       class: 'bg-red-100 text-red-700' },
    reabierto:      { label: 'Reabierto',       class: 'bg-orange-100 text-orange-700' },
}
</script>

<template>
    <span
        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
        :class="config[estado]?.class ?? 'bg-gray-100 text-gray-600'"
    >
        {{ config[estado]?.label ?? estado }}
    </span>
</template>
