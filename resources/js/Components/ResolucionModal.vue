<script setup>
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    show: Boolean,
    bug:  Object,
});

const emit = defineEmits(['close']);

// ── Formulario ────────────────────────────────────────────────────────────

const form = useForm({
    tipo:                 'correccion',
    causa_raiz:           '',
    solucion_aplicada:    '',
    archivos_modificados: [],
    commit_ref:           '',
    requiere_deploy:      false,
    notas_qa:             '',
    prevencion_futura:    '',
});

// Reset al abrir el modal
watch(() => props.show, (val) => {
    if (val) form.reset();
});

const submit = () => {
    // Filtrar entradas vacías en archivos_modificados antes de enviar
    form.archivos_modificados = archivos.value.filter(a => a.trim() !== '');

    form.post(route('bugs.storeResolucion', props.bug.id), {
        onSuccess: () => emit('close'),
    });
};

const cancelar = () => {
    form.reset();
    emit('close');
};

// ── Gestión de archivos modificados ──────────────────────────────────────

const archivos = ref(['']);

// Sincronizar cuando el modal se abre
watch(() => props.show, (val) => {
    if (val) archivos.value = [''];
});

const agregarArchivo = () => archivos.value.push('');

const quitarArchivo = (i) => {
    if (archivos.value.length > 1) {
        archivos.value.splice(i, 1);
    } else {
        archivos.value[0] = '';
    }
};

// ── Opciones ──────────────────────────────────────────────────────────────

const tiposResolucion = [
    { value: 'correccion',      label: 'Corrección de código' },
    { value: 'no_reproducible', label: 'No reproducible' },
    { value: 'duplicado',       label: 'Duplicado de otro ticket' },
    { value: 'disenio',         label: 'Comportamiento de diseño' },
    { value: 'rechazado',       label: 'Rechazado / No procede' },
];
</script>

<template>
    <Teleport to="body">
        <!-- Backdrop -->
        <Transition
            enter-active-class="transition-opacity duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="show"
                class="fixed inset-0 z-40 bg-black/50"
                @click="cancelar"
            />
        </Transition>

        <!-- Panel del modal -->
        <Transition
            enter-active-class="transition-all duration-200"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition-all duration-150"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div
                v-if="show"
                class="fixed inset-0 z-50 flex items-center justify-center p-4"
                @click.self="cancelar"
            >
                <div class="w-full max-w-2xl rounded-xl bg-white shadow-2xl flex flex-col max-h-[90vh]">

                    <!-- Cabecera -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900">
                                Registrar Resolución
                            </h2>
                            <p class="text-xs text-gray-500 mt-0.5 font-mono">
                                {{ bug.ticket_num }} — {{ bug.titulo }}
                            </p>
                        </div>
                        <button
                            @click="cancelar"
                            class="rounded-md p-1 text-gray-400 hover:text-gray-600 hover:bg-gray-100"
                        >
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Cuerpo (scrollable) -->
                    <div class="overflow-y-auto flex-1 px-6 py-5 space-y-5">

                        <!-- Error general -->
                        <div
                            v-if="form.errors.resolucion || form.errors.estado"
                            class="rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700"
                        >
                            {{ form.errors.resolucion ?? form.errors.estado }}
                        </div>

                        <!-- Tipo de resolución -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Tipo de resolución <span class="text-red-500">*</span>
                            </label>
                            <select
                                v-model="form.tipo"
                                class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                                :class="{ 'border-red-500': form.errors.tipo }"
                            >
                                <option
                                    v-for="t in tiposResolucion"
                                    :key="t.value"
                                    :value="t.value"
                                >
                                    {{ t.label }}
                                </option>
                            </select>
                            <p v-if="form.errors.tipo" class="mt-1 text-xs text-red-600">{{ form.errors.tipo }}</p>
                        </div>

                        <!-- Causa raíz -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Causa raíz <span class="text-red-500">*</span>
                            </label>
                            <textarea
                                v-model="form.causa_raiz"
                                rows="3"
                                placeholder="¿Por qué ocurrió el bug? Descripción técnica del origen..."
                                class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                                :class="{ 'border-red-500': form.errors.causa_raiz }"
                            />
                            <p v-if="form.errors.causa_raiz" class="mt-1 text-xs text-red-600">{{ form.errors.causa_raiz }}</p>
                        </div>

                        <!-- Solución aplicada -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Solución aplicada <span class="text-red-500">*</span>
                            </label>
                            <textarea
                                v-model="form.solucion_aplicada"
                                rows="3"
                                placeholder="¿Qué cambios se hicieron para resolverlo?..."
                                class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                                :class="{ 'border-red-500': form.errors.solucion_aplicada }"
                            />
                            <p v-if="form.errors.solucion_aplicada" class="mt-1 text-xs text-red-600">{{ form.errors.solucion_aplicada }}</p>
                        </div>

                        <!-- Archivos modificados -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Archivos modificados
                                <span class="text-xs font-normal text-gray-400">(opcional)</span>
                            </label>
                            <div class="space-y-2">
                                <div
                                    v-for="(_, i) in archivos"
                                    :key="i"
                                    class="flex items-center gap-2"
                                >
                                    <input
                                        v-model="archivos[i]"
                                        type="text"
                                        placeholder="app/Services/BugService.php"
                                        class="flex-1 rounded-md border border-gray-300 px-3 py-1.5 text-sm font-mono shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                                    />
                                    <button
                                        type="button"
                                        @click="quitarArchivo(i)"
                                        class="rounded p-1 text-gray-400 hover:text-red-500"
                                        title="Eliminar"
                                    >
                                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                            <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <button
                                type="button"
                                @click="agregarArchivo"
                                class="mt-2 text-xs font-medium text-indigo-600 hover:text-indigo-800"
                            >
                                + Agregar archivo
                            </button>
                        </div>

                        <!-- Commit ref / Requiere deploy (en fila) -->
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Referencia de commit
                                    <span class="text-xs font-normal text-gray-400">(opcional)</span>
                                </label>
                                <input
                                    v-model="form.commit_ref"
                                    type="text"
                                    placeholder="a3f9d21"
                                    class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm font-mono shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                                />
                            </div>
                            <div class="flex items-end pb-2">
                                <label class="flex items-center gap-2 cursor-pointer select-none">
                                    <input
                                        v-model="form.requiere_deploy"
                                        type="checkbox"
                                        class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                    />
                                    <span class="text-sm font-medium text-gray-700">Requiere deploy a producción</span>
                                </label>
                            </div>
                        </div>

                        <!-- Notas para QA -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Notas para QA
                                <span class="text-xs font-normal text-gray-400">(opcional)</span>
                            </label>
                            <textarea
                                v-model="form.notas_qa"
                                rows="2"
                                placeholder="Instrucciones específicas para que QA verifique la corrección..."
                                class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                            />
                        </div>

                        <!-- Prevención futura -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Prevención futura
                                <span class="text-xs font-normal text-gray-400">(opcional)</span>
                            </label>
                            <textarea
                                v-model="form.prevencion_futura"
                                rows="2"
                                placeholder="¿Qué se puede hacer para que este tipo de bug no vuelva a ocurrir?..."
                                class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                            />
                        </div>

                    </div>

                    <!-- Pie de página -->
                    <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200 flex-shrink-0">
                        <button
                            type="button"
                            @click="cancelar"
                            :disabled="form.processing"
                            class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50"
                        >
                            Cancelar
                        </button>
                        <button
                            type="button"
                            @click="submit"
                            :disabled="form.processing || !form.causa_raiz || !form.solucion_aplicada"
                            class="rounded-md bg-green-600 px-5 py-2 text-sm font-medium text-white hover:bg-green-700 disabled:opacity-50 flex items-center gap-2"
                        >
                            <svg v-if="form.processing" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                            </svg>
                            {{ form.processing ? 'Guardando...' : 'Guardar y Resolver' }}
                        </button>
                    </div>

                </div>
            </div>
        </Transition>
    </Teleport>
</template>
