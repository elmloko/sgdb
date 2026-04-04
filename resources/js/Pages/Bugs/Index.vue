<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive, watch } from 'vue';

const props = defineProps({
    bugs: Object,      // paginación de Laravel
    proyectos: Array,
    filtros: Object,
});

// Filtros reactivos inicializados desde el servidor
const filtros = reactive({
    estado:      props.filtros.estado      ?? '',
    prioridad:   props.filtros.prioridad   ?? '',
    proyecto_id: props.filtros.proyecto_id ?? '',
});

// Aplicar filtros automáticamente cuando cambian
watch(filtros, (vals) => {
    const params = Object.fromEntries(
        Object.entries(vals).filter(([, v]) => v !== ''),
    );
    router.get(route('bugs.index'), params, { preserveState: true, replace: true });
}, { deep: true });

const limpiarFiltros = () => {
    filtros.estado      = '';
    filtros.prioridad   = '';
    filtros.proyecto_id = '';
};

// Configuraciones de visualización
const clasesPrioridad = {
    critica: 'bg-red-100 text-red-800 border border-red-200',
    alta:    'bg-orange-100 text-orange-800 border border-orange-200',
    media:   'bg-yellow-100 text-yellow-800 border border-yellow-200',
    baja:    'bg-gray-100 text-gray-700 border border-gray-200',
};

const etiquetasPrioridad = {
    critica: 'Crítica',
    alta:    'Alta',
    media:   'Media',
    baja:    'Baja',
};

const clasesEstado = {
    nuevo:          'bg-blue-100 text-blue-800',
    en_revision:    'bg-purple-100 text-purple-800',
    asignado:       'bg-indigo-100 text-indigo-800',
    en_desarrollo:  'bg-cyan-100 text-cyan-800',
    en_qa:          'bg-yellow-100 text-yellow-800',
    resuelto:       'bg-green-100 text-green-800',
    cerrado:        'bg-gray-100 text-gray-600',
    rechazado:      'bg-red-100 text-red-700',
    reabierto:      'bg-orange-100 text-orange-800',
};

const etiquetasEstado = {
    nuevo:         'Nuevo',
    en_revision:   'En Revisión',
    asignado:      'Asignado',
    en_desarrollo: 'En Desarrollo',
    en_qa:         'En QA',
    resuelto:      'Resuelto',
    cerrado:       'Cerrado',
    rechazado:     'Rechazado',
    reabierto:     'Reabierto',
};

const formatFecha = (iso) => {
    if (!iso) return '—';
    return new Date(iso).toLocaleDateString('es-BO', {
        day: '2-digit', month: 'short', year: 'numeric',
    });
};

const slaClase = (bug) => {
    if (!bug.sla_vence_en || ['cerrado', 'resuelto', 'rechazado'].includes(bug.estado)) return '';
    const diff = new Date(bug.sla_vence_en) - new Date();
    if (diff < 0) return 'text-red-600 font-semibold';
    if (diff < 3600_000) return 'text-orange-500 font-semibold'; // menos de 1 hora
    return 'text-gray-500';
};

const slaTexto = (bug) => {
    if (!bug.sla_vence_en) return '—';
    if (['cerrado', 'resuelto', 'rechazado'].includes(bug.estado)) return 'Finalizado';
    const diff = new Date(bug.sla_vence_en) - new Date();
    if (diff < 0) return 'Vencido';
    const h = Math.floor(diff / 3_600_000);
    const m = Math.floor((diff % 3_600_000) / 60_000);
    return h > 0 ? `${h}h ${m}m` : `${m}m`;
};

const hayFiltrosActivos = () =>
    filtros.estado || filtros.prioridad || filtros.proyecto_id;
</script>

<template>
    <Head title="Bugs" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Bugs
                    <span class="ml-2 text-sm font-normal text-gray-400">
                        ({{ bugs.total }} en total)
                    </span>
                </h2>
                <Link
                    :href="route('bugs.create')"
                    class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                >
                    + Reportar Bug
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

                <!-- Filtros -->
                <div class="mb-4 flex flex-wrap items-end gap-3 rounded-lg bg-white p-4 shadow-sm">
                    <!-- Estado -->
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Estado</label>
                        <select
                            v-model="filtros.estado"
                            class="rounded-md border border-gray-300 px-3 py-1.5 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                        >
                            <option value="">Todos</option>
                            <option value="nuevo">Nuevo</option>
                            <option value="en_revision">En Revisión</option>
                            <option value="asignado">Asignado</option>
                            <option value="en_desarrollo">En Desarrollo</option>
                            <option value="en_qa">En QA</option>
                            <option value="resuelto">Resuelto</option>
                            <option value="cerrado">Cerrado</option>
                            <option value="rechazado">Rechazado</option>
                            <option value="reabierto">Reabierto</option>
                        </select>
                    </div>

                    <!-- Prioridad -->
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Prioridad</label>
                        <select
                            v-model="filtros.prioridad"
                            class="rounded-md border border-gray-300 px-3 py-1.5 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                        >
                            <option value="">Todas</option>
                            <option value="critica">Crítica</option>
                            <option value="alta">Alta</option>
                            <option value="media">Media</option>
                            <option value="baja">Baja</option>
                        </select>
                    </div>

                    <!-- Proyecto -->
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Proyecto</label>
                        <select
                            v-model="filtros.proyecto_id"
                            class="rounded-md border border-gray-300 px-3 py-1.5 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                        >
                            <option value="">Todos</option>
                            <option v-for="p in proyectos" :key="p.id" :value="p.id">
                                {{ p.nombre }}
                            </option>
                        </select>
                    </div>

                    <button
                        v-if="hayFiltrosActivos()"
                        @click="limpiarFiltros"
                        class="text-sm text-gray-500 underline hover:text-gray-700"
                    >
                        Limpiar filtros
                    </button>
                </div>

                <!-- Tabla -->
                <div class="overflow-hidden rounded-lg bg-white shadow">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 w-32">
                                    Ticket
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                    Título
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 w-24">
                                    Prioridad
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 w-28">
                                    Estado
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                    Proyecto
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                    Asignado a
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 w-24">
                                    SLA
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <tr v-if="bugs.data.length === 0">
                                <td colspan="7" class="px-4 py-10 text-center text-gray-400">
                                    No hay bugs que coincidan con los filtros.
                                </td>
                            </tr>
                            <tr
                                v-for="bug in bugs.data"
                                :key="bug.id"
                                class="hover:bg-gray-50 cursor-pointer"
                                @click="router.visit(route('bugs.show', bug.id))"
                            >
                                <td class="whitespace-nowrap px-4 py-3 font-mono text-xs text-gray-500">
                                    {{ bug.ticket_num }}
                                </td>
                                <td class="px-4 py-3 font-medium text-gray-900 max-w-xs truncate">
                                    {{ bug.titulo }}
                                    <span
                                        v-if="bug.modulo"
                                        class="ml-1 text-xs text-gray-400"
                                    >
                                        · {{ bug.modulo }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <span
                                        class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold"
                                        :class="clasesPrioridad[bug.prioridad]"
                                    >
                                        {{ etiquetasPrioridad[bug.prioridad] }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <span
                                        class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium"
                                        :class="clasesEstado[bug.estado]"
                                    >
                                        {{ etiquetasEstado[bug.estado] }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-gray-600">
                                    {{ bug.proyecto?.nombre ?? '—' }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-gray-500">
                                    {{ bug.asignado_a ? bug.asignado_a.name : '—' }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-xs" :class="slaClase(bug)">
                                    {{ slaTexto(bug) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                <div v-if="bugs.last_page > 1" class="mt-4 flex items-center justify-between text-sm text-gray-600">
                    <span>
                        Mostrando {{ bugs.from }}–{{ bugs.to }} de {{ bugs.total }}
                    </span>
                    <div class="flex gap-1">
                        <Link
                            v-for="link in bugs.links"
                            :key="link.label"
                            :href="link.url ?? '#'"
                            v-html="link.label"
                            preserve-state
                            class="rounded px-3 py-1 text-xs"
                            :class="[
                                link.active
                                    ? 'bg-indigo-600 text-white'
                                    : 'bg-white border border-gray-300 hover:bg-gray-50',
                                !link.url ? 'opacity-40 pointer-events-none' : '',
                            ]"
                        />
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
