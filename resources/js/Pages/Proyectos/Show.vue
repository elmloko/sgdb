<script setup>
import { ref, computed } from 'vue'
import { Head, Link, useForm, router, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import StatusBadge from '@/Components/StatusBadge.vue'
import PriorityBadge from '@/Components/PriorityBadge.vue'

const props = defineProps({
    proyecto:            { type: Object, required: true },
    bugs:                { type: Array,  default: () => [] },
    resoluciones:        { type: Array,  default: () => [] },
    stats:               { type: Object, default: () => ({}) },
    usuariosDisponibles: { type: Array,  default: () => [] },
})

const page   = usePage()
const esAdmin = computed(() => page.props.auth.user?.rol_global === 'admin')

// ── Tabs ──────────────────────────────────────────────────────────────────
const tabActiva = ref('bugs')
const tabs = [
    { id: 'bugs',        label: 'Bugs' },
    { id: 'resoluciones',label: 'Resoluciones' },
    { id: 'equipo',      label: 'Equipo' },
    { id: 'info',        label: 'Información' },
]

// ── Filtro de bugs ────────────────────────────────────────────────────────
const filtroEstado = ref('')
const bugsFiltrados = computed(() =>
    filtroEstado.value
        ? props.bugs.filter(b => b.estado === filtroEstado.value)
        : props.bugs
)

// ── Equipo ────────────────────────────────────────────────────────────────
const idsEnProyecto = computed(() => props.proyecto.usuarios.map(u => u.id))
const usuariosParaAsignar = computed(() =>
    props.usuariosDisponibles.filter(u => !idsEnProyecto.value.includes(u.id))
)

const formAsignar = useForm({ user_id: '', rol_proyecto: 'reportante' })
const asignarUsuario = () => {
    formAsignar.post(route('proyectos.asignarUsuario', props.proyecto.id), {
        onSuccess: () => formAsignar.reset(),
    })
}
const quitarUsuario = (uid) => {
    if (!confirm('¿Quitar este usuario del proyecto?')) return
    router.delete(route('proyectos.quitarUsuario', [props.proyecto.id, uid]))
}

// ── Helpers ───────────────────────────────────────────────────────────────
const estadoConfig = {
    activo:     { label: 'Activo',     class: 'bg-green-100 text-green-700' },
    pausado:    { label: 'Pausado',    class: 'bg-yellow-100 text-yellow-700' },
    completado: { label: 'Completado', class: 'bg-blue-100 text-blue-700' },
    archivado:  { label: 'Archivado',  class: 'bg-gray-100 text-gray-500' },
}
const rolConfig = {
    desarrollador: { label: 'Desarrollador', class: 'bg-blue-100 text-blue-700' },
    qa:            { label: 'QA',            class: 'bg-purple-100 text-purple-700' },
    reportante:    { label: 'Reportante',    class: 'bg-gray-100 text-gray-600' },
}
const tipoResConfig = {
    correccion:      'Corrección',
    no_reproducible: 'No reproducible',
    duplicado:       'Duplicado',
    disenio:         'Diseño',
    rechazado:       'Rechazado',
}

function fecha(iso) {
    if (!iso) return '—'
    return new Date(iso).toLocaleDateString('es-BO', { day:'2-digit', month:'2-digit', year:'numeric' })
}
function fechaHora(iso) {
    if (!iso) return '—'
    return new Date(iso).toLocaleString('es-BO', { day:'2-digit', month:'2-digit', year:'numeric', hour:'2-digit', minute:'2-digit' })
}
function slaClass(iso) {
    if (!iso) return ''
    const min = (new Date(iso) - Date.now()) / 60000
    if (min < 0)   return 'text-red-600 font-semibold'
    if (min < 120) return 'text-orange-500 font-semibold'
    return ''
}
</script>

<template>
    <Head :title="proyecto.nombre" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <Link :href="route('proyectos.index')" class="text-sm text-gray-400 hover:text-gray-600">
                        Proyectos
                    </Link>
                    <span class="text-gray-300">/</span>
                    <h2 class="text-xl font-semibold text-gray-800">{{ proyecto.nombre }}</h2>
                    <span
                        class="rounded-full px-2.5 py-0.5 text-xs font-medium"
                        :class="estadoConfig[proyecto.estado]?.class"
                    >
                        {{ estadoConfig[proyecto.estado]?.label }}
                    </span>
                </div>
                <Link
                    v-if="esAdmin"
                    :href="route('proyectos.edit', proyecto.id)"
                    class="rounded-md bg-gray-100 px-3 py-1.5 text-sm text-gray-600 hover:bg-gray-200"
                >
                    Editar
                </Link>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">

                <!-- Stats rápidas -->
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-5">
                    <div class="rounded-xl border border-gray-200 bg-white px-4 py-3 text-center shadow-sm">
                        <p class="text-2xl font-bold text-gray-800">{{ stats.total }}</p>
                        <p class="text-xs text-gray-400 mt-0.5">Total bugs</p>
                    </div>
                    <div class="rounded-xl border border-blue-100 bg-blue-50 px-4 py-3 text-center shadow-sm">
                        <p class="text-2xl font-bold text-blue-700">{{ stats.abiertos }}</p>
                        <p class="text-xs text-blue-400 mt-0.5">Abiertos</p>
                    </div>
                    <div class="rounded-xl border border-green-100 bg-green-50 px-4 py-3 text-center shadow-sm">
                        <p class="text-2xl font-bold text-green-700">{{ stats.cerrados }}</p>
                        <p class="text-xs text-green-400 mt-0.5">Cerrados</p>
                    </div>
                    <div class="rounded-xl border border-red-100 bg-red-50 px-4 py-3 text-center shadow-sm">
                        <p class="text-2xl font-bold text-red-700">{{ stats.criticos }}</p>
                        <p class="text-xs text-red-400 mt-0.5">Críticos activos</p>
                    </div>
                    <div class="rounded-xl border border-yellow-100 bg-yellow-50 px-4 py-3 text-center shadow-sm">
                        <p class="text-2xl font-bold text-yellow-700">{{ stats.sin_asignar }}</p>
                        <p class="text-xs text-yellow-400 mt-0.5">Sin asignar</p>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
                    <div class="border-b border-gray-200">
                        <nav class="flex">
                            <button
                                v-for="tab in tabs"
                                :key="tab.id"
                                @click="tabActiva = tab.id"
                                class="px-5 py-3 text-sm font-medium border-b-2 transition-colors"
                                :class="tabActiva === tab.id
                                    ? 'border-indigo-500 text-indigo-600'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                            >
                                {{ tab.label }}
                                <span
                                    v-if="tab.id === 'bugs'"
                                    class="ml-1.5 rounded-full bg-gray-100 px-1.5 py-0.5 text-xs text-gray-600"
                                >
                                    {{ bugs.length }}
                                </span>
                                <span
                                    v-if="tab.id === 'resoluciones'"
                                    class="ml-1.5 rounded-full bg-gray-100 px-1.5 py-0.5 text-xs text-gray-600"
                                >
                                    {{ resoluciones.length }}
                                </span>
                                <span
                                    v-if="tab.id === 'equipo'"
                                    class="ml-1.5 rounded-full bg-gray-100 px-1.5 py-0.5 text-xs text-gray-600"
                                >
                                    {{ proyecto.usuarios.length }}
                                </span>
                            </button>
                        </nav>
                    </div>

                    <!-- ── Tab: Bugs ──────────────────────────────────────── -->
                    <div v-if="tabActiva === 'bugs'" class="p-4">
                        <div class="mb-3 flex items-center justify-between gap-3">
                            <select
                                v-model="filtroEstado"
                                class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">Todos los estados</option>
                                <option value="nuevo">Nuevo</option>
                                <option value="en_revision">En revisión</option>
                                <option value="asignado">Asignado</option>
                                <option value="en_desarrollo">En desarrollo</option>
                                <option value="en_qa">En QA</option>
                                <option value="resuelto">Resuelto</option>
                                <option value="cerrado">Cerrado</option>
                                <option value="rechazado">Rechazado</option>
                                <option value="reabierto">Reabierto</option>
                            </select>
                            <Link
                                :href="route('bugs.create')"
                                class="rounded-md bg-indigo-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-indigo-700"
                            >
                                + Nuevo bug
                            </Link>
                        </div>

                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 py-2 text-left font-medium text-gray-500">Ticket</th>
                                    <th class="px-3 py-2 text-left font-medium text-gray-500">Título</th>
                                    <th class="px-3 py-2 text-left font-medium text-gray-500">Prioridad</th>
                                    <th class="px-3 py-2 text-left font-medium text-gray-500">Estado</th>
                                    <th class="px-3 py-2 text-left font-medium text-gray-500">Asignado a</th>
                                    <th class="px-3 py-2 text-left font-medium text-gray-500">SLA</th>
                                    <th class="px-3 py-2 text-left font-medium text-gray-500">Creado</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="bug in bugsFiltrados" :key="bug.id" class="hover:bg-gray-50">
                                    <td class="px-3 py-2 font-mono text-xs">
                                        <Link :href="route('bugs.show', bug.id)" class="text-indigo-600 hover:underline">
                                            {{ bug.ticket_num }}
                                        </Link>
                                    </td>
                                    <td class="px-3 py-2 max-w-xs">
                                        <Link :href="route('bugs.show', bug.id)" class="hover:underline line-clamp-1 text-gray-800">
                                            {{ bug.titulo }}
                                        </Link>
                                    </td>
                                    <td class="px-3 py-2"><PriorityBadge :prioridad="bug.prioridad" /></td>
                                    <td class="px-3 py-2"><StatusBadge :estado="bug.estado" /></td>
                                    <td class="px-3 py-2 text-gray-500 text-xs">
                                        {{ bug.asignado_a ? bug.asignado_a.name : '—' }}
                                    </td>
                                    <td class="px-3 py-2 text-xs" :class="slaClass(bug.sla_vence_en)">
                                        {{ bug.sla_vence_en ? fechaHora(bug.sla_vence_en) : '—' }}
                                    </td>
                                    <td class="px-3 py-2 text-xs text-gray-400">{{ fecha(bug.created_at) }}</td>
                                </tr>
                                <tr v-if="bugsFiltrados.length === 0">
                                    <td colspan="7" class="px-3 py-8 text-center text-gray-400">
                                        No hay bugs con ese filtro.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- ── Tab: Resoluciones ──────────────────────────────── -->
                    <div v-if="tabActiva === 'resoluciones'" class="p-4">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 py-2 text-left font-medium text-gray-500">Ticket</th>
                                    <th class="px-3 py-2 text-left font-medium text-gray-500">Título</th>
                                    <th class="px-3 py-2 text-left font-medium text-gray-500">Tipo</th>
                                    <th class="px-3 py-2 text-left font-medium text-gray-500">Resuelto por</th>
                                    <th class="px-3 py-2 text-left font-medium text-gray-500">Commit</th>
                                    <th class="px-3 py-2 text-left font-medium text-gray-500">Deploy</th>
                                    <th class="px-3 py-2 text-left font-medium text-gray-500">Fecha</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="r in resoluciones" :key="r.ticket_num" class="hover:bg-gray-50">
                                    <td class="px-3 py-2 font-mono text-xs text-indigo-600">{{ r.ticket_num }}</td>
                                    <td class="px-3 py-2 max-w-xs text-gray-800 line-clamp-1">{{ r.titulo }}</td>
                                    <td class="px-3 py-2 text-xs text-gray-600">
                                        {{ tipoResConfig[r.tipo] ?? r.tipo }}
                                    </td>
                                    <td class="px-3 py-2 text-xs text-gray-600">{{ r.resuelto_por }}</td>
                                    <td class="px-3 py-2 font-mono text-xs text-gray-500">{{ r.commit_ref ?? '—' }}</td>
                                    <td class="px-3 py-2 text-xs">
                                        <span
                                            class="rounded-full px-2 py-0.5"
                                            :class="r.requiere_deploy ? 'bg-orange-100 text-orange-700' : 'bg-gray-100 text-gray-500'"
                                        >
                                            {{ r.requiere_deploy ? 'Sí' : 'No' }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2 text-xs text-gray-400">{{ fecha(r.resuelto_en) }}</td>
                                </tr>
                                <tr v-if="resoluciones.length === 0">
                                    <td colspan="7" class="px-3 py-8 text-center text-gray-400">
                                        No hay resoluciones registradas aún.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- ── Tab: Equipo ────────────────────────────────────── -->
                    <div v-if="tabActiva === 'equipo'" class="p-4 space-y-5">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 py-2 text-left font-medium text-gray-500">Nombre</th>
                                    <th class="px-3 py-2 text-left font-medium text-gray-500">Email</th>
                                    <th class="px-3 py-2 text-left font-medium text-gray-500">Rol en proyecto</th>
                                    <th v-if="esAdmin" class="px-3 py-2"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="u in proyecto.usuarios" :key="u.id" class="hover:bg-gray-50">
                                    <td class="px-3 py-2 font-medium text-gray-800">{{ u.name }}</td>
                                    <td class="px-3 py-2 text-gray-500">{{ u.email }}</td>
                                    <td class="px-3 py-2">
                                        <span
                                            class="rounded-full px-2.5 py-0.5 text-xs font-medium"
                                            :class="rolConfig[u.pivot.rol_proyecto]?.class"
                                        >
                                            {{ rolConfig[u.pivot.rol_proyecto]?.label }}
                                        </span>
                                    </td>
                                    <td v-if="esAdmin" class="px-3 py-2 text-right">
                                        <button @click="quitarUsuario(u.id)" class="text-xs text-red-600 hover:text-red-800">
                                            Quitar
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="proyecto.usuarios.length === 0">
                                    <td :colspan="esAdmin ? 4 : 3" class="px-3 py-8 text-center text-gray-400">
                                        Sin miembros asignados.
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <!-- Formulario asignar (solo admin) -->
                        <div v-if="esAdmin" class="border-t border-gray-100 pt-4">
                            <h4 class="mb-3 text-sm font-semibold text-gray-700">Asignar usuario</h4>
                            <form @submit.prevent="asignarUsuario" class="flex flex-wrap items-end gap-3">
                                <div class="flex-1 min-w-48">
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Usuario</label>
                                    <select
                                        v-model="formAsignar.user_id"
                                        class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    >
                                        <option value="" disabled>Seleccionar...</option>
                                        <option v-for="u in usuariosParaAsignar" :key="u.id" :value="u.id">
                                            {{ u.name }} ({{ u.email }})
                                        </option>
                                    </select>
                                </div>
                                <div class="min-w-36">
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Rol</label>
                                    <select
                                        v-model="formAsignar.rol_proyecto"
                                        class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    >
                                        <option value="desarrollador">Desarrollador</option>
                                        <option value="qa">QA</option>
                                        <option value="reportante">Reportante</option>
                                    </select>
                                </div>
                                <button
                                    type="submit"
                                    :disabled="formAsignar.processing || !formAsignar.user_id"
                                    class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                                >
                                    Asignar
                                </button>
                            </form>
                            <p v-if="usuariosParaAsignar.length === 0" class="mt-2 text-xs text-gray-400">
                                Todos los usuarios ya están en este proyecto.
                            </p>
                        </div>
                    </div>

                    <!-- ── Tab: Información ───────────────────────────────── -->
                    <div v-if="tabActiva === 'info'" class="p-6">
                        <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2 text-sm">
                            <div>
                                <dt class="font-medium text-gray-500">Descripción</dt>
                                <dd class="mt-1 text-gray-800">{{ proyecto.descripcion || '—' }}</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-gray-500">Creado por</dt>
                                <dd class="mt-1 text-gray-800">{{ proyecto.creado_por?.name ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-gray-500">Fecha de inicio</dt>
                                <dd class="mt-1 text-gray-800">{{ fecha(proyecto.fecha_inicio) }}</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-gray-500">Fecha fin estimada</dt>
                                <dd class="mt-1 text-gray-800">{{ fecha(proyecto.fecha_fin_estimada) }}</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-gray-500">Estado</dt>
                                <dd class="mt-1">
                                    <span
                                        class="rounded-full px-2.5 py-0.5 text-xs font-medium"
                                        :class="estadoConfig[proyecto.estado]?.class"
                                    >
                                        {{ estadoConfig[proyecto.estado]?.label }}
                                    </span>
                                </dd>
                            </div>
                        </dl>
                    </div>

                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
