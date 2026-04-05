<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    proyectos: Array,
});

const form = useForm({
    titulo:                  '',
    descripcion:             '',
    prioridad:               'media',
    proyecto_id:             '',
    modulo:                  '',
    entorno:                 'desarrollo',
    pasos_reproducir:        '',
    comportamiento_esperado: '',
    comportamiento_actual:   '',
});

const submit = () => {
    form.post(route('bugs.store'));
};

// Descripción del SLA según prioridad seleccionada
const slaDescripcion = {
    critica: '⚡ SLA: 4 horas — atención inmediata requerida',
    alta:    '🔴 SLA: 8 horas',
    media:   '🟡 SLA: 48 horas',
    baja:    '🟢 Sin SLA estricto — próximo sprint',
};
</script>

<template>
    <Head title="Reportar Bug" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('bugs.index')" class="text-slate-500 hover:text-slate-400">
                    ← Volver
                </Link>
                <h2 class="text-xl font-semibold leading-tight text-slate-100">
                    Reportar Bug
                </h2>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <form @submit.prevent="submit" class="space-y-6">

                    <!-- Sección: Información Principal -->
                    <div class="rounded-lg bg-slate-900 p-6 shadow space-y-5">
                        <h3 class="text-base font-semibold text-slate-100 border-b border-slate-800 pb-2">
                            Información Principal
                        </h3>

                        <!-- Título -->
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-1">
                                Título <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="form.titulo"
                                type="text"
                                placeholder="Resumen breve y descriptivo del bug..."
                                class="block w-full rounded-md border border-slate-700 px-3 py-2 text-sm shadow-sm focus:border-cyan-500 focus:outline-none focus:ring-1 focus:ring-cyan-500"
                                :class="{ 'border-red-500': form.errors.titulo }"
                            />
                            <p v-if="form.errors.titulo" class="mt-1 text-xs text-red-600">{{ form.errors.titulo }}</p>
                        </div>

                        <!-- Descripción -->
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-1">
                                Descripción <span class="text-red-500">*</span>
                            </label>
                            <textarea
                                v-model="form.descripcion"
                                rows="3"
                                placeholder="Describe el problema en detalle..."
                                class="block w-full rounded-md border border-slate-700 px-3 py-2 text-sm shadow-sm focus:border-cyan-500 focus:outline-none focus:ring-1 focus:ring-cyan-500"
                                :class="{ 'border-red-500': form.errors.descripcion }"
                            />
                            <p v-if="form.errors.descripcion" class="mt-1 text-xs text-red-600">{{ form.errors.descripcion }}</p>
                        </div>

                        <!-- Proyecto / Módulo / Entorno / Prioridad en grid -->
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <!-- Proyecto -->
                            <div>
                                <label class="block text-sm font-medium text-slate-300 mb-1">
                                    Proyecto <span class="text-red-500">*</span>
                                </label>
                                <select
                                    v-model="form.proyecto_id"
                                    class="block w-full rounded-md border border-slate-700 px-3 py-2 text-sm shadow-sm focus:border-cyan-500 focus:outline-none focus:ring-1 focus:ring-cyan-500"
                                    :class="{ 'border-red-500': form.errors.proyecto_id }"
                                >
                                    <option value="" disabled>Seleccionar proyecto...</option>
                                    <option v-for="p in proyectos" :key="p.id" :value="p.id">
                                        {{ p.nombre }}
                                    </option>
                                </select>
                                <p v-if="form.errors.proyecto_id" class="mt-1 text-xs text-red-600">{{ form.errors.proyecto_id }}</p>
                            </div>

                            <!-- Módulo -->
                            <div>
                                <label class="block text-sm font-medium text-slate-300 mb-1">
                                    Módulo / Componente
                                </label>
                                <input
                                    v-model="form.modulo"
                                    type="text"
                                    placeholder="Ej: Autenticación, Facturación..."
                                    class="block w-full rounded-md border border-slate-700 px-3 py-2 text-sm shadow-sm focus:border-cyan-500 focus:outline-none focus:ring-1 focus:ring-cyan-500"
                                />
                            </div>

                            <!-- Prioridad -->
                            <div>
                                <label class="block text-sm font-medium text-slate-300 mb-1">
                                    Prioridad <span class="text-red-500">*</span>
                                </label>
                                <select
                                    v-model="form.prioridad"
                                    class="block w-full rounded-md border border-slate-700 px-3 py-2 text-sm shadow-sm focus:border-cyan-500 focus:outline-none focus:ring-1 focus:ring-cyan-500"
                                >
                                    <option value="critica">Crítica</option>
                                    <option value="alta">Alta</option>
                                    <option value="media">Media</option>
                                    <option value="baja">Baja</option>
                                </select>
                                <p class="mt-1 text-xs text-slate-500">{{ slaDescripcion[form.prioridad] }}</p>
                            </div>

                            <!-- Entorno -->
                            <div>
                                <label class="block text-sm font-medium text-slate-300 mb-1">
                                    Entorno <span class="text-red-500">*</span>
                                </label>
                                <select
                                    v-model="form.entorno"
                                    class="block w-full rounded-md border border-slate-700 px-3 py-2 text-sm shadow-sm focus:border-cyan-500 focus:outline-none focus:ring-1 focus:ring-cyan-500"
                                >
                                    <option value="produccion">Producción</option>
                                    <option value="staging">Staging</option>
                                    <option value="desarrollo">Desarrollo</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Sección: Reproducción -->
                    <div class="rounded-lg bg-slate-900 p-6 shadow space-y-5">
                        <h3 class="text-base font-semibold text-slate-100 border-b border-slate-800 pb-2">
                            Reproducción del Bug
                        </h3>

                        <!-- Pasos para reproducir -->
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-1">
                                Pasos para reproducir
                            </label>
                            <textarea
                                v-model="form.pasos_reproducir"
                                rows="4"
                                placeholder="1. Ir a la página X&#10;2. Hacer clic en Y&#10;3. Observar el error..."
                                class="block w-full rounded-md border border-slate-700 px-3 py-2 text-sm shadow-sm focus:border-cyan-500 focus:outline-none focus:ring-1 focus:ring-cyan-500"
                            />
                        </div>

                        <!-- Comportamiento esperado / actual -->
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-slate-300 mb-1">
                                    Comportamiento esperado
                                </label>
                                <textarea
                                    v-model="form.comportamiento_esperado"
                                    rows="3"
                                    placeholder="¿Qué debería pasar?"
                                    class="block w-full rounded-md border border-slate-700 px-3 py-2 text-sm shadow-sm focus:border-cyan-500 focus:outline-none focus:ring-1 focus:ring-cyan-500"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-300 mb-1">
                                    Comportamiento actual
                                </label>
                                <textarea
                                    v-model="form.comportamiento_actual"
                                    rows="3"
                                    placeholder="¿Qué está pasando realmente?"
                                    class="block w-full rounded-md border border-slate-700 px-3 py-2 text-sm shadow-sm focus:border-cyan-500 focus:outline-none focus:ring-1 focus:ring-cyan-500"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Acciones -->
                    <div class="flex justify-end gap-3">
                        <Link
                            :href="route('bugs.index')"
                            class="rounded-md border border-slate-700 px-4 py-2 text-sm font-medium text-slate-300 hover:bg-slate-800/50"
                        >
                            Cancelar
                        </Link>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="rounded-md bg-cyan-500 px-5 py-2 text-sm font-medium text-white hover:bg-cyan-400 disabled:opacity-50"
                        >
                            Reportar Bug
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
