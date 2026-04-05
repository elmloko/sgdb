<script setup>
import { ref } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import { Head } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

defineProps({
    proyectos: { type: Array, default: () => [] },
    esAdmin:   { type: Boolean, default: false },
})

const estadoConfig = {
    activo:     { label: 'Activo',      class: 'bg-green-100 text-green-700' },
    pausado:    { label: 'Pausado',     class: 'bg-yellow-100 text-yellow-700' },
    completado: { label: 'Completado',  class: 'bg-blue-100 text-blue-700' },
    archivado:  { label: 'Archivado',   class: 'bg-slate-800 text-slate-500' },
}

function formatFecha(fecha) {
    if (!fecha) return '—'
    return new Date(fecha).toLocaleDateString('es-BO', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

// ── Cambiar estado rápido ─────────────────────────────────────────────────
const menuAbierto = ref(null) // id del proyecto con menú abierto
const formEstado  = useForm({ estado: '' })

const estadosSiguientes = {
    activo:     [{ value: 'pausado', label: 'Pausar' }, { value: 'archivado', label: 'Archivar' }],
    pausado:    [{ value: 'activo', label: 'Reactivar' }, { value: 'archivado', label: 'Archivar' }],
    completado: [{ value: 'archivado', label: 'Archivar' }],
    archivado:  [{ value: 'activo', label: 'Reactivar' }],
}

function cambiarEstado(proyecto, nuevoEstado) {
    menuAbierto.value = null
    const confirmar = {
        archivado:  `¿Archivar "${proyecto.nombre}"?`,
        pausado:    `¿Pausar "${proyecto.nombre}"?`,
        completado: `¿Marcar "${proyecto.nombre}" como completado?`,
        activo:     `¿Reactivar "${proyecto.nombre}"?`,
    }
    if (!confirm(confirmar[nuevoEstado])) return
    formEstado.estado = nuevoEstado
    formEstado.patch(route('proyectos.cambiarEstado', proyecto.id))
}
</script>

<template>
    <Head title="Proyectos" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-slate-100">Proyectos</h2>
                <Link
                    v-if="esAdmin"
                    :href="route('proyectos.create')"
                    class="rounded-md bg-cyan-500 px-4 py-2 text-sm font-medium text-white hover:bg-cyan-400"
                >
                    + Nuevo proyecto
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

                <div v-if="proyectos.length === 0" class="rounded-xl border border-slate-800 bg-slate-900 px-6 py-16 text-center text-slate-500 shadow-sm">
                    No hay proyectos disponibles.
                </div>

                <div v-else class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="p in proyectos"
                        :key="p.id"
                        class="flex flex-col rounded-xl border border-slate-800 bg-slate-900 p-5 shadow-sm hover:shadow-md transition-shadow"
                        :class="{ 'opacity-60': p.estado === 'archivado' }"
                    >
                        <div class="flex items-start justify-between gap-2">
                            <h3 class="font-semibold text-slate-100 leading-snug">{{ p.nombre }}</h3>
                            <span
                                class="shrink-0 rounded-full px-2.5 py-0.5 text-xs font-medium"
                                :class="estadoConfig[p.estado]?.class ?? 'bg-slate-800 text-slate-400'"
                            >
                                {{ estadoConfig[p.estado]?.label ?? p.estado }}
                            </span>
                        </div>

                        <p v-if="p.descripcion" class="mt-2 text-sm text-slate-500 line-clamp-2">
                            {{ p.descripcion }}
                        </p>

                        <div class="mt-3 flex gap-4 text-xs text-slate-500">
                            <span>Inicio: {{ formatFecha(p.fecha_inicio) }}</span>
                            <span>Fin est.: {{ formatFecha(p.fecha_fin_estimada) }}</span>
                        </div>

                        <div class="mt-3 text-xs text-slate-500">
                            <span class="font-medium text-slate-300">{{ p.bugs_count }}</span> bug(s) registrado(s)
                        </div>

                        <div class="mt-4 flex gap-2 border-t border-slate-800 pt-3">
                            <Link
                                :href="route('proyectos.show', p.id)"
                                class="flex-1 rounded-md bg-indigo-50 px-3 py-1.5 text-center text-xs font-medium text-cyan-400 hover:bg-cyan-500/10"
                            >
                                Ver detalle
                            </Link>
                            <Link
                                v-if="esAdmin"
                                :href="route('proyectos.edit', p.id)"
                                class="rounded-md bg-slate-800 px-3 py-1.5 text-xs font-medium text-slate-400 hover:bg-slate-700"
                            >
                                Editar
                            </Link>

                            <!-- Menú de estado rápido (solo admin) -->
                            <div v-if="esAdmin" class="relative">
                                <button
                                    @click.stop="menuAbierto = menuAbierto === p.id ? null : p.id"
                                    class="rounded-md border border-slate-800 px-2 py-1.5 text-slate-500 hover:bg-slate-800"
                                    title="Cambiar estado"
                                >
                                    <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20">
                                        <circle cx="10" cy="4"  r="1.5"/>
                                        <circle cx="10" cy="10" r="1.5"/>
                                        <circle cx="10" cy="16" r="1.5"/>
                                    </svg>
                                </button>

                                <div
                                    v-if="menuAbierto === p.id"
                                    class="absolute right-0 bottom-full mb-1 w-44 rounded-md border border-slate-800 bg-slate-900 shadow-lg z-10"
                                >
                                    <button
                                        v-for="opcion in estadosSiguientes[p.estado]"
                                        :key="opcion.value"
                                        @click="cambiarEstado(p, opcion.value)"
                                        class="block w-full px-4 py-2 text-left text-xs hover:bg-slate-800/50"
                                        :class="opcion.value === 'archivado' ? 'text-red-600 hover:bg-red-50' : 'text-slate-300'"
                                    >
                                        {{ opcion.label }}
                                    </button>
                                </div>

                                <!-- Cerrar al hacer clic fuera -->
                                <div
                                    v-if="menuAbierto === p.id"
                                    class="fixed inset-0 z-0"
                                    @click="menuAbierto = null"
                                />
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
