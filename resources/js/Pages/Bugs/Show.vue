<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ResolucionModal from '@/Components/ResolucionModal.vue';
import { Head, Link, usePage, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    bug:                     Object,
    transicionesDisponibles: Array,
});

const page = usePage();

// ── Configuraciones visuales ───────────────────────────────────────────────

const clasesPrioridad = {
    critica: 'bg-red-100 text-red-800 border border-red-200',
    alta:    'bg-orange-100 text-orange-800 border border-orange-200',
    media:   'bg-yellow-100 text-yellow-800 border border-yellow-200',
    baja:    'bg-slate-800 text-slate-300 border border-slate-800',
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
    asignado:       'bg-cyan-500/10 text-indigo-800',
    en_desarrollo:  'bg-cyan-100 text-cyan-800',
    en_qa:          'bg-yellow-100 text-yellow-800',
    resuelto:       'bg-green-100 text-green-800',
    cerrado:        'bg-slate-800 text-slate-400',
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

const clasesBotonTransicion = {
    en_revision:   'bg-purple-600 hover:bg-purple-700 text-white',
    asignado:      'bg-cyan-500 hover:bg-cyan-400 text-white',
    en_desarrollo: 'bg-cyan-600 hover:bg-cyan-700 text-white',
    en_qa:         'bg-yellow-500 hover:bg-yellow-600 text-white',
    resuelto:      'bg-green-600 hover:bg-green-700 text-white',
    cerrado:       'bg-slate-600 hover:bg-slate-500 text-white',
    rechazado:     'bg-red-600 hover:bg-red-700 text-white',
    reabierto:     'bg-orange-500 hover:bg-orange-600 text-white',
};

const etiquetasEntorno = {
    produccion: 'Producción',
    staging:    'Staging',
    desarrollo: 'Desarrollo',
};

const etiquetasTipoResolucion = {
    correccion:      'Corrección de código',
    no_reproducible: 'No reproducible',
    duplicado:       'Duplicado',
    disenio:         'Comportamiento de diseño',
    rechazado:       'Rechazado',
};

// ── Modal de resolución ────────────────────────────────────────────────────

const mostrarModalResolucion = ref(false);

// ── Acciones de estado (transiciones normales) ─────────────────────────────

const transicionPendiente  = ref(null);

const formEstado = useForm({
    nuevo_estado: '',
    comentario:   '',
});

const seleccionarTransicion = (estado) => {
    // 'resuelto' tiene su propio modal con formulario completo
    if (estado === 'resuelto') {
        mostrarModalResolucion.value = true;
        return;
    }
    transicionPendiente.value   = estado;
    formEstado.nuevo_estado     = estado;
    formEstado.comentario       = '';
};

const cancelarTransicion = () => {
    transicionPendiente.value = null;
    formEstado.reset();
};

const confirmarTransicion = () => {
    formEstado.patch(route('bugs.cambiarEstado', props.bug.id), {
        onSuccess: () => { transicionPendiente.value = null; },
    });
};

const esTransicionDestructiva = (estado) =>
    ['rechazado', 'reabierto'].includes(estado);

// ── Historial ──────────────────────────────────────────────────────────────

const iconosAccion = {
    cambio_estado: '🔄',
    comentario:    '💬',
    asignacion:    '👤',
    adjunto:       '📎',
    resolucion:    '✅',
};

const textoAccion = (entrada) => {
    switch (entrada.accion) {
        case 'cambio_estado':
            return entrada.valor_anterior
                ? `cambió el estado de "${etiquetasEstado[entrada.valor_anterior] ?? entrada.valor_anterior}" a "${etiquetasEstado[entrada.valor_nuevo] ?? entrada.valor_nuevo}"`
                : `reportó el bug con estado "${etiquetasEstado[entrada.valor_nuevo] ?? entrada.valor_nuevo}"`;
        case 'asignacion':
            return entrada.valor_nuevo
                ? 'asignó el bug a un desarrollador'
                : 'removió la asignación';
        case 'comentario':
            return 'comentó';
        case 'resolucion':
            return `registró una resolución (${etiquetasTipoResolucion[entrada.valor_nuevo] ?? entrada.valor_nuevo})`;
        case 'adjunto':
            return 'adjuntó un archivo';
        default:
            return entrada.accion;
    }
};

// ── SLA ────────────────────────────────────────────────────────────────────

const slaInfo = computed(() => {
    const { sla_vence_en, estado } = props.bug;
    if (!sla_vence_en) return null;

    const finalizado = ['cerrado', 'resuelto', 'rechazado'].includes(estado);
    if (finalizado) return { texto: 'Finalizado', clase: 'text-slate-500' };

    const diff = new Date(sla_vence_en) - new Date();
    if (diff < 0) return { texto: 'Vencido', clase: 'text-red-600 font-bold' };

    const h = Math.floor(diff / 3_600_000);
    const m = Math.floor((diff % 3_600_000) / 60_000);
    const texto = h > 0 ? `${h}h ${m}m restantes` : `${m}m restantes`;
    const clase = h < 2 ? 'text-orange-500 font-semibold' : 'text-green-600';
    return { texto, clase };
});

// ── Formato ────────────────────────────────────────────────────────────────

const formatFechaHora = (iso) => {
    if (!iso) return '—';
    return new Date(iso).toLocaleString('es-BO', {
        day: '2-digit', month: 'short', year: 'numeric',
        hour: '2-digit', minute: '2-digit',
    });
};

const formatFecha = (iso) => {
    if (!iso) return '—';
    return new Date(iso).toLocaleDateString('es-BO', {
        day: '2-digit', month: 'short', year: 'numeric',
    });
};
</script>

<template>
    <Head :title="`${bug.ticket_num} — ${bug.titulo}`" />

    <!-- Modal de resolución (solo aparece cuando se hace clic en "Resuelto") -->
    <ResolucionModal
        :show="mostrarModalResolucion"
        :bug="bug"
        @close="mostrarModalResolucion = false"
    />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3 flex-wrap">
                    <Link :href="route('bugs.index')" class="text-slate-500 hover:text-slate-400">
                        ← Bugs
                    </Link>
                    <span class="font-mono text-sm text-slate-500">{{ bug.ticket_num }}</span>
                    <span
                        class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium"
                        :class="clasesEstado[bug.estado]"
                    >
                        {{ etiquetasEstado[bug.estado] }}
                    </span>
                    <span
                        class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold"
                        :class="clasesPrioridad[bug.prioridad]"
                    >
                        {{ etiquetasPrioridad[bug.prioridad] }}
                    </span>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

                    <!-- ── Columna principal ─────────────────────────────── -->
                    <div class="lg:col-span-2 space-y-5">

                        <!-- Título y descripción -->
                        <div class="rounded-lg bg-slate-900 p-6 shadow">
                            <h1 class="text-xl font-bold text-slate-100 mb-3">
                                {{ bug.titulo }}
                            </h1>
                            <p class="text-sm text-slate-300 whitespace-pre-wrap leading-relaxed">
                                {{ bug.descripcion }}
                            </p>
                        </div>

                        <!-- Reproducción -->
                        <div
                            v-if="bug.pasos_reproducir || bug.comportamiento_esperado || bug.comportamiento_actual"
                            class="rounded-lg bg-slate-900 p-6 shadow space-y-4"
                        >
                            <h2 class="text-base font-semibold text-slate-100 border-b border-slate-800 pb-2">
                                Reproducción
                            </h2>

                            <div v-if="bug.pasos_reproducir">
                                <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">
                                    Pasos para reproducir
                                </h3>
                                <p class="text-sm text-slate-300 whitespace-pre-wrap leading-relaxed">
                                    {{ bug.pasos_reproducir }}
                                </p>
                            </div>

                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div v-if="bug.comportamiento_esperado">
                                    <h3 class="text-xs font-semibold uppercase tracking-wider text-green-600 mb-1">
                                        Comportamiento esperado
                                    </h3>
                                    <p class="text-sm text-slate-300 whitespace-pre-wrap">
                                        {{ bug.comportamiento_esperado }}
                                    </p>
                                </div>
                                <div v-if="bug.comportamiento_actual">
                                    <h3 class="text-xs font-semibold uppercase tracking-wider text-red-600 mb-1">
                                        Comportamiento actual
                                    </h3>
                                    <p class="text-sm text-slate-300 whitespace-pre-wrap">
                                        {{ bug.comportamiento_actual }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- ══ Resolución ════════════════════════════════════ -->
                        <div
                            v-if="bug.resolucion"
                            class="rounded-lg bg-green-50 border border-green-200 shadow-sm overflow-hidden"
                        >
                            <!-- Cabecera de resolución -->
                            <div class="flex items-center justify-between px-6 py-4 bg-green-100 border-b border-green-200">
                                <div class="flex items-center gap-3">
                                    <span class="text-xl">✅</span>
                                    <div>
                                        <h2 class="text-base font-semibold text-green-900">
                                            Resolución
                                        </h2>
                                        <p class="text-xs text-green-700">
                                            {{ etiquetasTipoResolucion[bug.resolucion.tipo] ?? bug.resolucion.tipo }}
                                            · Resuelta por <strong>{{ bug.resolucion.resuelto_por?.name ?? '—' }}</strong>
                                            · {{ formatFechaHora(bug.resolucion.created_at) }}
                                        </p>
                                    </div>
                                </div>
                                <!-- Badge deploy -->
                                <span
                                    v-if="bug.resolucion.requiere_deploy"
                                    class="inline-flex items-center gap-1 rounded-full bg-amber-100 border border-amber-300 px-2 py-0.5 text-xs font-medium text-amber-800"
                                >
                                    🚀 Requiere deploy
                                </span>
                            </div>

                            <!-- Contenido de resolución -->
                            <div class="px-6 py-5 space-y-4">

                                <!-- Causa raíz -->
                                <div>
                                    <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">
                                        Causa raíz
                                    </h3>
                                    <p class="text-sm text-slate-100 whitespace-pre-wrap leading-relaxed">
                                        {{ bug.resolucion.causa_raiz }}
                                    </p>
                                </div>

                                <!-- Solución aplicada -->
                                <div>
                                    <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">
                                        Solución aplicada
                                    </h3>
                                    <p class="text-sm text-slate-100 whitespace-pre-wrap leading-relaxed">
                                        {{ bug.resolucion.solucion_aplicada }}
                                    </p>
                                </div>

                                <!-- Archivos modificados + commit ref -->
                                <div
                                    v-if="bug.resolucion.archivos_modificados?.length || bug.resolucion.commit_ref"
                                    class="grid grid-cols-1 gap-4 sm:grid-cols-2"
                                >
                                    <div v-if="bug.resolucion.archivos_modificados?.length">
                                        <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">
                                            Archivos modificados
                                        </h3>
                                        <ul class="space-y-1">
                                            <li
                                                v-for="(archivo, i) in bug.resolucion.archivos_modificados"
                                                :key="i"
                                                class="flex items-center gap-1.5 text-xs font-mono text-slate-300"
                                            >
                                                <span class="text-slate-500">📄</span>
                                                {{ archivo }}
                                            </li>
                                        </ul>
                                    </div>
                                    <div v-if="bug.resolucion.commit_ref">
                                        <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">
                                            Commit
                                        </h3>
                                        <code class="inline-flex items-center gap-1 rounded bg-slate-800 px-2 py-0.5 text-xs text-slate-100">
                                            {{ bug.resolucion.commit_ref }}
                                        </code>
                                    </div>
                                </div>

                                <!-- Notas para QA -->
                                <div v-if="bug.resolucion.notas_qa">
                                    <h3 class="text-xs font-semibold uppercase tracking-wider text-yellow-700 mb-1">
                                        Notas para QA
                                    </h3>
                                    <p class="text-sm text-slate-100 whitespace-pre-wrap leading-relaxed bg-yellow-50 border border-yellow-100 rounded px-3 py-2">
                                        {{ bug.resolucion.notas_qa }}
                                    </p>
                                </div>

                                <!-- Prevención futura -->
                                <div v-if="bug.resolucion.prevencion_futura">
                                    <h3 class="text-xs font-semibold uppercase tracking-wider text-blue-700 mb-1">
                                        Prevención futura
                                    </h3>
                                    <p class="text-sm text-slate-100 whitespace-pre-wrap leading-relaxed bg-blue-50 border border-blue-100 rounded px-3 py-2">
                                        {{ bug.resolucion.prevencion_futura }}
                                    </p>
                                </div>

                            </div>
                        </div>

                        <!-- Historial de actividad -->
                        <div class="rounded-lg bg-slate-900 p-6 shadow">
                            <h2 class="text-base font-semibold text-slate-100 border-b border-slate-800 pb-2 mb-4">
                                Historial de Actividad
                            </h2>

                            <div v-if="bug.historial.length === 0" class="text-sm text-slate-500 text-center py-4">
                                Sin actividad registrada.
                            </div>

                            <ol class="relative border-l border-slate-800 space-y-6 ml-3">
                                <li
                                    v-for="entrada in bug.historial"
                                    :key="entrada.id"
                                    class="ml-6"
                                >
                                    <span class="absolute -left-3 flex h-6 w-6 items-center justify-center rounded-full bg-slate-800 ring-4 ring-white text-xs">
                                        {{ iconosAccion[entrada.accion] ?? '•' }}
                                    </span>

                                    <div class="rounded-md bg-slate-800/50 px-4 py-3">
                                        <p class="text-sm text-slate-100">
                                            <span class="font-medium">{{ entrada.usuario?.name ?? 'Sistema' }}</span>
                                            {{ ' ' }}{{ textoAccion(entrada) }}
                                        </p>

                                        <!-- Mostrar comentario solo cuando no es la descripción de una resolución -->
                                        <p
                                            v-if="entrada.comentario && entrada.accion === 'comentario'"
                                            class="mt-2 text-sm text-slate-400 whitespace-pre-wrap bg-slate-900 rounded border border-slate-800 px-3 py-2"
                                        >
                                            {{ entrada.comentario }}
                                        </p>
                                        <!-- Comentario de cambio de estado (si el usuario agregó uno) -->
                                        <p
                                            v-if="entrada.comentario && entrada.accion === 'cambio_estado'"
                                            class="mt-2 text-sm text-slate-400 whitespace-pre-wrap bg-slate-900 rounded border border-slate-800 px-3 py-2"
                                        >
                                            {{ entrada.comentario }}
                                        </p>

                                        <time class="mt-1 block text-xs text-slate-500">
                                            {{ formatFechaHora(entrada.created_at) }}
                                        </time>
                                    </div>
                                </li>
                            </ol>
                        </div>

                    </div>

                    <!-- ── Sidebar ────────────────────────────────────────── -->
                    <div class="space-y-4">

                        <!-- ══ Panel de Acciones ════════════════════════════ -->
                        <div
                            v-if="transicionesDisponibles.length > 0"
                            class="rounded-lg bg-slate-900 p-5 shadow"
                        >
                            <h2 class="text-sm font-semibold text-slate-300 mb-3 border-b border-slate-800 pb-2">
                                Acciones
                            </h2>

                            <!-- Botones de transición -->
                            <div v-if="!transicionPendiente" class="flex flex-col gap-2">
                                <button
                                    v-for="estado in transicionesDisponibles"
                                    :key="estado"
                                    @click="seleccionarTransicion(estado)"
                                    class="w-full rounded-md px-3 py-2 text-sm font-medium transition-colors text-left"
                                    :class="clasesBotonTransicion[estado] ?? 'bg-slate-800 hover:bg-slate-600 text-white'"
                                >
                                    <span v-if="estado === 'resuelto'" class="flex items-center gap-2">
                                        ✅ Registrar Resolución
                                    </span>
                                    <span v-else>
                                        → {{ etiquetasEstado[estado] }}
                                    </span>
                                </button>
                            </div>

                            <!-- Formulario de confirmación inline (para transiciones no-resuelto) -->
                            <div v-else class="space-y-3">
                                <div class="flex items-center gap-2 rounded-md bg-slate-800/50 px-3 py-2 text-sm">
                                    <span class="text-slate-500">Pasar a:</span>
                                    <span
                                        class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium"
                                        :class="clasesEstado[transicionPendiente]"
                                    >
                                        {{ etiquetasEstado[transicionPendiente] }}
                                    </span>
                                </div>

                                <p
                                    v-if="esTransicionDestructiva(transicionPendiente)"
                                    class="text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded px-2 py-1"
                                >
                                    Esta acción cambiará el flujo del bug. Agrega un comentario si es necesario.
                                </p>

                                <div>
                                    <label class="block text-xs font-medium text-slate-400 mb-1">
                                        Comentario
                                        <span class="font-normal text-slate-500">(opcional)</span>
                                    </label>
                                    <textarea
                                        v-model="formEstado.comentario"
                                        rows="3"
                                        placeholder="Describe el motivo del cambio..."
                                        class="block w-full rounded-md border border-slate-700 px-3 py-2 text-sm focus:border-cyan-500 focus:outline-none focus:ring-1 focus:ring-cyan-500"
                                    />
                                </div>

                                <p v-if="formEstado.errors.nuevo_estado" class="text-xs text-red-600">
                                    {{ formEstado.errors.nuevo_estado }}
                                </p>

                                <div class="flex gap-2">
                                    <button
                                        @click="confirmarTransicion"
                                        :disabled="formEstado.processing"
                                        class="flex-1 rounded-md px-3 py-2 text-sm font-medium transition-colors disabled:opacity-50"
                                        :class="clasesBotonTransicion[transicionPendiente] ?? 'bg-slate-800 hover:bg-slate-600 text-white'"
                                    >
                                        Confirmar
                                    </button>
                                    <button
                                        @click="cancelarTransicion"
                                        :disabled="formEstado.processing"
                                        class="rounded-md border border-slate-700 px-3 py-2 text-sm font-medium text-slate-400 hover:bg-slate-800/50 disabled:opacity-50"
                                    >
                                        Cancelar
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Sin acciones -->
                        <div
                            v-else-if="!['cerrado', 'rechazado'].includes(bug.estado)"
                            class="rounded-lg bg-slate-800/50 border border-slate-800 p-4 text-xs text-slate-500 text-center"
                        >
                            No tienes acciones disponibles para este bug en su estado actual.
                        </div>

                        <!-- Detalles -->
                        <div class="rounded-lg bg-slate-900 p-5 shadow">
                            <h2 class="text-sm font-semibold text-slate-300 mb-3 border-b border-slate-800 pb-2">
                                Detalles
                            </h2>
                            <dl class="space-y-3 text-sm">
                                <div class="flex justify-between">
                                    <dt class="text-slate-500">Proyecto</dt>
                                    <dd class="font-medium text-slate-100 text-right">
                                        <Link
                                            :href="route('proyectos.show', bug.proyecto_id)"
                                            class="text-cyan-400 hover:underline"
                                        >
                                            {{ bug.proyecto?.nombre ?? '—' }}
                                        </Link>
                                    </dd>
                                </div>
                                <div v-if="bug.modulo" class="flex justify-between">
                                    <dt class="text-slate-500">Módulo</dt>
                                    <dd class="font-medium text-slate-100">{{ bug.modulo }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-slate-500">Entorno</dt>
                                    <dd class="font-medium text-slate-100">{{ etiquetasEntorno[bug.entorno] }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-slate-500">Reportado por</dt>
                                    <dd class="font-medium text-slate-100">{{ bug.reportado_por?.name ?? '—' }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-slate-500">Asignado a</dt>
                                    <dd class="font-medium text-slate-100">
                                        {{ bug.asignado_a?.name ?? 'Sin asignar' }}
                                    </dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-slate-500">Creado</dt>
                                    <dd class="text-slate-300">{{ formatFecha(bug.created_at) }}</dd>
                                </div>
                                <div v-if="bug.cerrado_en" class="flex justify-between">
                                    <dt class="text-slate-500">Cerrado</dt>
                                    <dd class="text-slate-300">{{ formatFecha(bug.cerrado_en) }}</dd>
                                </div>
                            </dl>
                        </div>

                        <!-- SLA -->
                        <div v-if="bug.sla_vence_en" class="rounded-lg bg-slate-900 p-5 shadow">
                            <h2 class="text-sm font-semibold text-slate-300 mb-3 border-b border-slate-800 pb-2">
                                SLA
                            </h2>
                            <dl class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <dt class="text-slate-500">Vence el</dt>
                                    <dd class="text-slate-300">{{ formatFechaHora(bug.sla_vence_en) }}</dd>
                                </div>
                                <div v-if="slaInfo" class="flex justify-between">
                                    <dt class="text-slate-500">Estado SLA</dt>
                                    <dd :class="slaInfo.clase">{{ slaInfo.texto }}</dd>
                                </div>
                            </dl>
                        </div>

                    </div>

                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
