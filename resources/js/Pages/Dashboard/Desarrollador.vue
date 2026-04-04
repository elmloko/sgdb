<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import StatusBadge from '@/Components/StatusBadge.vue'
import PriorityBadge from '@/Components/PriorityBadge.vue'
import { Head } from '@inertiajs/vue3'

const props = defineProps({
    bugsAsignados:  { type: Array, default: () => [] },
    resueltosMes:   { type: Number, default: 0 },
    totalResueltos: { type: Number, default: 0 },
    tasaReapertura: { type: Number, default: 0 },
    bugsSinAsignar: { type: Array, default: () => [] },
})

// Clasifica el riesgo de SLA de un bug para el resaltado visual
function nivelSla(bug) {
    if (!bug.sla_vence_en) return 'sin_sla'
    const minutosRestantes = (new Date(bug.sla_vence_en) - Date.now()) / 60000
    if (minutosRestantes < 0)    return 'vencido'
    if (minutosRestantes < 120)  return 'critico'   // < 2 horas
    if (minutosRestantes < 480)  return 'proximo'   // < 8 horas
    return 'ok'
}

function formatearFecha(isoString) {
    if (!isoString) return '—'
    return new Date(isoString).toLocaleString('es-BO', {
        day:    '2-digit',
        month:  '2-digit',
        year:   'numeric',
        hour:   '2-digit',
        minute: '2-digit',
    })
}

function tiempoRestante(isoString) {
    if (!isoString) return null
    const minutos = Math.round((new Date(isoString) - Date.now()) / 60000)
    if (minutos < 0) {
        const abs = Math.abs(minutos)
        if (abs < 60) return `vencido hace ${abs} min`
        return `vencido hace ${Math.floor(abs / 60)}h ${abs % 60}min`
    }
    if (minutos < 60) return `${minutos} min`
    const h = Math.floor(minutos / 60)
    const m = minutos % 60
    return m > 0 ? `${h}h ${m}min` : `${h}h`
}

const filaSlaClass = (bug) => ({
    'bg-red-50   border-l-4 border-red-500':    nivelSla(bug) === 'vencido',
    'bg-orange-50 border-l-4 border-orange-400': nivelSla(bug) === 'critico',
    'bg-yellow-50 border-l-4 border-yellow-300': nivelSla(bug) === 'proximo',
})

const badgeSlaClass = (bug) => {
    switch (nivelSla(bug)) {
        case 'vencido': return 'bg-red-100 text-red-800 font-semibold'
        case 'critico': return 'bg-orange-100 text-orange-800 font-semibold'
        case 'proximo': return 'bg-yellow-100 text-yellow-800'
        default:        return 'bg-gray-100 text-gray-600'
    }
}

// Color de la tasa de reapertura
const colorTasa = computed(() => {
    if (props.tasaReapertura === 0)   return 'text-green-600'
    if (props.tasaReapertura <= 10)   return 'text-yellow-600'
    return 'text-red-600'
})
</script>

<template>
    <Head title="Mi Panel" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Mi Panel
            </h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">

                <!-- ── KPIs personales ──────────────────────────────────── -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <!-- Bugs en cola -->
                    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                        <p class="text-sm font-medium text-gray-500">Bugs asignados activos</p>
                        <p class="mt-1 text-3xl font-bold text-gray-900">{{ bugsAsignados.length }}</p>
                        <p class="mt-1 text-xs text-gray-400">no cerrados ni rechazados</p>
                    </div>

                    <!-- Resueltos este mes -->
                    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                        <p class="text-sm font-medium text-gray-500">Resueltos este mes</p>
                        <p class="mt-1 text-3xl font-bold text-green-600">{{ resueltosMes }}</p>
                        <p class="mt-1 text-xs text-gray-400">de {{ totalResueltos }} en total</p>
                    </div>

                    <!-- Tasa de reapertura -->
                    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                        <p class="text-sm font-medium text-gray-500">Tasa de reapertura</p>
                        <p class="mt-1 text-3xl font-bold" :class="colorTasa">{{ tasaReapertura }}%</p>
                        <p class="mt-1 text-xs text-gray-400">sobre {{ totalResueltos }} resoluciones</p>
                    </div>
                </div>

                <!-- ── Cola de trabajo ──────────────────────────────────── -->
                <section>
                    <h3 class="mb-3 text-base font-semibold text-gray-700">
                        Cola de trabajo
                        <span class="ml-2 rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700">
                            {{ bugsAsignados.length }}
                        </span>
                    </h3>

                    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                        <table v-if="bugsAsignados.length > 0" class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left font-medium text-gray-500">Ticket</th>
                                    <th class="px-4 py-3 text-left font-medium text-gray-500">Título</th>
                                    <th class="px-4 py-3 text-left font-medium text-gray-500">Prioridad</th>
                                    <th class="px-4 py-3 text-left font-medium text-gray-500">Estado</th>
                                    <th class="px-4 py-3 text-left font-medium text-gray-500">Proyecto</th>
                                    <th class="px-4 py-3 text-left font-medium text-gray-500">SLA vence</th>
                                    <th class="px-4 py-3 text-left font-medium text-gray-500">Restante</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr
                                    v-for="bug in bugsAsignados"
                                    :key="bug.id"
                                    :class="filaSlaClass(bug)"
                                >
                                    <td class="px-4 py-3 font-mono text-xs text-gray-700">
                                        <Link
                                            :href="route('bugs.show', bug.id)"
                                            class="text-blue-600 hover:underline"
                                        >
                                            {{ bug.ticket_num }}
                                        </Link>
                                    </td>
                                    <td class="max-w-xs px-4 py-3 text-gray-900">
                                        <Link
                                            :href="route('bugs.show', bug.id)"
                                            class="hover:underline line-clamp-1"
                                        >
                                            {{ bug.titulo }}
                                        </Link>
                                    </td>
                                    <td class="px-4 py-3">
                                        <PriorityBadge :prioridad="bug.prioridad" />
                                    </td>
                                    <td class="px-4 py-3">
                                        <StatusBadge :estado="bug.estado" />
                                    </td>
                                    <td class="px-4 py-3 text-gray-600 text-xs">
                                        {{ bug.proyecto?.nombre ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-500">
                                        {{ formatearFecha(bug.sla_vence_en) }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <span
                                            v-if="bug.sla_vence_en"
                                            class="rounded-full px-2 py-0.5 text-xs"
                                            :class="badgeSlaClass(bug)"
                                        >
                                            {{ tiempoRestante(bug.sla_vence_en) }}
                                        </span>
                                        <span v-else class="text-xs text-gray-400">Sin SLA</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <div v-else class="px-6 py-10 text-center text-sm text-gray-500">
                            No tienes bugs asignados actualmente.
                        </div>
                    </div>
                </section>

                <!-- ── Bugs sin asignar en tus proyectos ───────────────── -->
                <section>
                    <h3 class="mb-3 text-base font-semibold text-gray-700">
                        Bugs sin asignar en tus proyectos
                        <span
                            class="ml-2 rounded-full px-2 py-0.5 text-xs font-medium"
                            :class="bugsSinAsignar.length > 0
                                ? 'bg-red-100 text-red-700'
                                : 'bg-gray-100 text-gray-600'"
                        >
                            {{ bugsSinAsignar.length }}
                        </span>
                    </h3>

                    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                        <table v-if="bugsSinAsignar.length > 0" class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left font-medium text-gray-500">Ticket</th>
                                    <th class="px-4 py-3 text-left font-medium text-gray-500">Título</th>
                                    <th class="px-4 py-3 text-left font-medium text-gray-500">Prioridad</th>
                                    <th class="px-4 py-3 text-left font-medium text-gray-500">Estado</th>
                                    <th class="px-4 py-3 text-left font-medium text-gray-500">Proyecto</th>
                                    <th class="px-4 py-3 text-left font-medium text-gray-500">Creado</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr
                                    v-for="bug in bugsSinAsignar"
                                    :key="bug.id"
                                    class="hover:bg-gray-50"
                                >
                                    <td class="px-4 py-3 font-mono text-xs text-gray-700">
                                        <Link
                                            :href="route('bugs.show', bug.id)"
                                            class="text-blue-600 hover:underline"
                                        >
                                            {{ bug.ticket_num }}
                                        </Link>
                                    </td>
                                    <td class="max-w-xs px-4 py-3 text-gray-900">
                                        <Link
                                            :href="route('bugs.show', bug.id)"
                                            class="hover:underline line-clamp-1"
                                        >
                                            {{ bug.titulo }}
                                        </Link>
                                    </td>
                                    <td class="px-4 py-3">
                                        <PriorityBadge :prioridad="bug.prioridad" />
                                    </td>
                                    <td class="px-4 py-3">
                                        <StatusBadge :estado="bug.estado" />
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-600">
                                        {{ bug.proyecto?.nombre ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-500">
                                        {{ formatearFecha(bug.created_at) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <div v-else class="px-6 py-10 text-center text-sm text-gray-500">
                            No hay bugs sin asignar en tus proyectos.
                        </div>
                    </div>
                </section>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
