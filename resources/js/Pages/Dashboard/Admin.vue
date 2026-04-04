<script setup>
import { Link } from '@inertiajs/vue3'
import { Head } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import StatusBadge from '@/Components/StatusBadge.vue'
import PriorityBadge from '@/Components/PriorityBadge.vue'

const props = defineProps({
    kpis:                  { type: Object, default: () => ({}) },
    cargaDesarrolladores:  { type: Array,  default: () => [] },
    proyectosActivos:      { type: Array,  default: () => [] },
    bugsCriticos:          { type: Array,  default: () => [] },
})

function formatearFecha(isoString) {
    if (!isoString) return '—'
    return new Date(isoString).toLocaleString('es-BO', {
        day: '2-digit', month: '2-digit', year: 'numeric',
        hour: '2-digit', minute: '2-digit',
    })
}

function tiempoRestante(isoString) {
    if (!isoString) return null
    const minutos = Math.round((new Date(isoString) - Date.now()) / 60000)
    if (minutos < 0) {
        const abs = Math.abs(minutos)
        return abs < 60 ? `vencido hace ${abs}m` : `vencido hace ${Math.floor(abs/60)}h`
    }
    return minutos < 60 ? `${minutos}m` : `${Math.floor(minutos/60)}h ${minutos%60}m`
}

function slaClass(isoString) {
    if (!isoString) return 'text-gray-400'
    const min = Math.round((new Date(isoString) - Date.now()) / 60000)
    if (min < 0)   return 'text-red-600 font-semibold'
    if (min < 120) return 'text-orange-500 font-semibold'
    return 'text-gray-500'
}

// Barra de progreso de bugs cerrados vs total por proyecto
function porcentajeCerrados(p) {
    if (!p.bugs_total) return 0
    return Math.round((p.bugs_cerrados / p.bugs_total) * 100)
}
</script>

<template>
    <Head title="Dashboard Admin" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Dashboard — Administrador
            </h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">

                <!-- ── KPI Cards ─────────────────────────────────────────── -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">

                    <!-- Bugs críticos activos -->
                    <div class="rounded-xl border border-red-200 bg-red-50 p-5 shadow-sm">
                        <p class="text-sm font-medium text-red-600">Bugs críticos activos</p>
                        <p class="mt-1 text-4xl font-bold text-red-700">{{ kpis.bugs_criticos_activos }}</p>
                        <p class="mt-1 text-xs text-red-400">prioridad crítica, sin cerrar</p>
                    </div>

                    <!-- Total bugs activos -->
                    <div class="rounded-xl border border-blue-200 bg-blue-50 p-5 shadow-sm">
                        <p class="text-sm font-medium text-blue-600">Total bugs activos</p>
                        <p class="mt-1 text-4xl font-bold text-blue-700">{{ kpis.total_bugs_activos }}</p>
                        <p class="mt-1 text-xs text-blue-400">no cerrados ni rechazados</p>
                    </div>

                    <!-- Tasa de resolución del mes -->
                    <div class="rounded-xl border border-green-200 bg-green-50 p-5 shadow-sm">
                        <p class="text-sm font-medium text-green-600">Tasa de resolución</p>
                        <p class="mt-1 text-4xl font-bold text-green-700">{{ kpis.tasa_resolucion_mes }}%</p>
                        <p class="mt-1 text-xs text-green-400">bugs cerrados este mes</p>
                    </div>

                    <!-- Tiempo promedio de resolución -->
                    <div class="rounded-xl border border-purple-200 bg-purple-50 p-5 shadow-sm">
                        <p class="text-sm font-medium text-purple-600">Tiempo promedio</p>
                        <p class="mt-1 text-4xl font-bold text-purple-700">{{ kpis.tiempo_promedio_resolucion }}h</p>
                        <p class="mt-1 text-xs text-purple-400">promedio de resolución</p>
                    </div>

                </div>

                <!-- ── Fila: Carga devs + Proyectos ──────────────────────── -->
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

                    <!-- Carga por desarrollador -->
                    <section>
                        <h3 class="mb-3 text-base font-semibold text-gray-700">Carga por desarrollador</h3>
                        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left font-medium text-gray-500">Nombre</th>
                                        <th class="px-4 py-3 text-left font-medium text-gray-500">Rol</th>
                                        <th class="px-4 py-3 text-center font-medium text-gray-500">Asignados</th>
                                        <th class="px-4 py-3 text-center font-medium text-gray-500">Resueltos semana</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <tr
                                        v-for="dev in cargaDesarrolladores"
                                        :key="dev.id"
                                        class="hover:bg-gray-50"
                                    >
                                        <td class="px-4 py-3 font-medium text-gray-800">{{ dev.name }}</td>
                                        <td class="px-4 py-3 capitalize text-gray-500 text-xs">{{ dev.rol_global }}</td>
                                        <td class="px-4 py-3 text-center">
                                            <span
                                                class="inline-block min-w-[2rem] rounded-full px-2 py-0.5 text-xs font-semibold"
                                                :class="dev.bugs_asignados > 5
                                                    ? 'bg-red-100 text-red-700'
                                                    : dev.bugs_asignados > 2
                                                        ? 'bg-yellow-100 text-yellow-700'
                                                        : 'bg-green-100 text-green-700'"
                                            >
                                                {{ dev.bugs_asignados }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-center text-gray-700">{{ dev.resueltos_semana }}</td>
                                    </tr>
                                    <tr v-if="cargaDesarrolladores.length === 0">
                                        <td colspan="4" class="px-4 py-8 text-center text-gray-400">
                                            Sin desarrolladores registrados.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <!-- Estado de proyectos activos -->
                    <section>
                        <h3 class="mb-3 text-base font-semibold text-gray-700">Proyectos activos</h3>
                        <div class="space-y-3">
                            <div
                                v-for="p in proyectosActivos"
                                :key="p.id"
                                class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm"
                            >
                                <div class="flex items-start justify-between">
                                    <div>
                                        <Link
                                            :href="route('proyectos.show', p.id)"
                                            class="font-medium text-gray-800 hover:underline"
                                        >
                                            {{ p.nombre }}
                                        </Link>
                                        <p v-if="p.fecha_fin_estimada" class="text-xs text-gray-400 mt-0.5">
                                            Fin estimado: {{ p.fecha_fin_estimada }}
                                        </p>
                                    </div>
                                    <div class="flex gap-3 text-xs text-right">
                                        <div>
                                            <span class="block font-semibold text-gray-800">{{ p.bugs_activos }}</span>
                                            <span class="text-gray-400">activos</span>
                                        </div>
                                        <div v-if="p.bugs_criticos > 0">
                                            <span class="block font-semibold text-red-600">{{ p.bugs_criticos }}</span>
                                            <span class="text-red-400">críticos</span>
                                        </div>
                                    </div>
                                </div>
                                <!-- Barra de progreso -->
                                <div class="mt-3">
                                    <div class="flex justify-between text-xs text-gray-400 mb-1">
                                        <span>Progreso</span>
                                        <span>{{ porcentajeCerrados(p) }}% cerrados</span>
                                    </div>
                                    <div class="h-1.5 w-full rounded-full bg-gray-200">
                                        <div
                                            class="h-1.5 rounded-full bg-green-500 transition-all"
                                            :style="{ width: porcentajeCerrados(p) + '%' }"
                                        />
                                    </div>
                                </div>
                            </div>
                            <div v-if="proyectosActivos.length === 0" class="rounded-xl border border-gray-200 bg-white px-6 py-8 text-center text-sm text-gray-400 shadow-sm">
                                No hay proyectos activos.
                            </div>
                        </div>
                    </section>

                </div>

                <!-- ── Bugs críticos sin atender ──────────────────────────── -->
                <section>
                    <h3 class="mb-3 text-base font-semibold text-gray-700">
                        Bugs críticos sin atender
                        <span
                            class="ml-2 rounded-full px-2 py-0.5 text-xs font-medium"
                            :class="bugsCriticos.length > 0 ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-600'"
                        >
                            {{ bugsCriticos.length }}
                        </span>
                    </h3>

                    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                        <table v-if="bugsCriticos.length > 0" class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left font-medium text-gray-500">Ticket</th>
                                    <th class="px-4 py-3 text-left font-medium text-gray-500">Título</th>
                                    <th class="px-4 py-3 text-left font-medium text-gray-500">Estado</th>
                                    <th class="px-4 py-3 text-left font-medium text-gray-500">Proyecto</th>
                                    <th class="px-4 py-3 text-left font-medium text-gray-500">Asignado a</th>
                                    <th class="px-4 py-3 text-left font-medium text-gray-500">SLA</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr
                                    v-for="bug in bugsCriticos"
                                    :key="bug.id"
                                    class="hover:bg-red-50"
                                >
                                    <td class="px-4 py-3 font-mono text-xs">
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
                                        <StatusBadge :estado="bug.estado" />
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-600">{{ bug.proyecto ?? '—' }}</td>
                                    <td class="px-4 py-3 text-xs text-gray-600">
                                        <span v-if="bug.asignado_a">{{ bug.asignado_a }}</span>
                                        <span v-else class="italic text-gray-400">Sin asignar</span>
                                    </td>
                                    <td class="px-4 py-3 text-xs" :class="slaClass(bug.sla_vence_en)">
                                        {{ bug.sla_vence_en ? tiempoRestante(bug.sla_vence_en) : '—' }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <div v-else class="px-6 py-10 text-center text-sm text-gray-500">
                            No hay bugs críticos sin atender.
                        </div>
                    </div>
                </section>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
