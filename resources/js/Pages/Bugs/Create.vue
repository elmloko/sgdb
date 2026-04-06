<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

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
    adjuntos:                [],
});

// ---- Adjuntos ----
const inputArchivos = ref(null);
const archivosPreview = ref([]);
const errorArchivos = ref('');
const MAX_ARCHIVOS = 5;
const MAX_MB = 5;

function seleccionarArchivos(event) {
    const nuevos = Array.from(event.target.files);
    errorArchivos.value = '';

    const combinados = [...archivosPreview.value.map(a => a.file), ...nuevos];

    if (combinados.length > MAX_ARCHIVOS) {
        errorArchivos.value = `Máximo ${MAX_ARCHIVOS} archivos permitidos.`;
        event.target.value = '';
        return;
    }
    for (const archivo of nuevos) {
        if (archivo.size > MAX_MB * 1024 * 1024) {
            errorArchivos.value = `"${archivo.name}" supera los ${MAX_MB} MB permitidos.`;
            event.target.value = '';
            return;
        }
        archivosPreview.value.push({
            file: archivo,
            nombre: archivo.name,
            url: archivo.type.startsWith('image/') ? URL.createObjectURL(archivo) : null,
            esImagen: archivo.type.startsWith('image/'),
        });
    }
    form.adjuntos = archivosPreview.value.map(a => a.file);
    event.target.value = '';
}

function quitarArchivo(index) {
    const item = archivosPreview.value[index];
    if (item.url) URL.revokeObjectURL(item.url);
    archivosPreview.value.splice(index, 1);
    form.adjuntos = archivosPreview.value.map(a => a.file);
}

const submit = () => {
    form.transform(data => {
        const fd = new FormData();
        Object.entries(data).forEach(([key, val]) => {
            if (key === 'adjuntos') {
                val.forEach(file => fd.append('adjuntos[]', file));
            } else {
                fd.append(key, val ?? '');
            }
        });
        return fd;
    }).post(route('bugs.store'));
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

                    <!-- Sección: Adjuntos -->
                    <div class="rounded-lg bg-slate-900 p-6 shadow space-y-4">
                        <h3 class="text-base font-semibold text-slate-100 border-b border-slate-800 pb-2">
                            Capturas de pantalla <span class="text-sm font-normal text-slate-500">(opcional)</span>
                        </h3>

                        <!-- Zona de subida -->
                        <div
                            class="flex flex-col items-center justify-center rounded-lg border-2 border-dashed border-slate-700 px-6 py-8 text-center cursor-pointer hover:border-cyan-600 transition-colors"
                            @click="inputArchivos.click()"
                        >
                            <svg class="mb-3 h-9 w-9 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                      d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/>
                            </svg>
                            <p class="text-sm text-slate-400">Hacé clic para adjuntar imágenes o archivos</p>
                            <p class="mt-1 text-xs text-slate-600">
                                JPG, PNG, GIF, WEBP o PDF — máx. {{ MAX_MB }} MB por archivo, hasta {{ MAX_ARCHIVOS }} archivos
                            </p>
                            <input
                                ref="inputArchivos"
                                type="file"
                                multiple
                                accept="image/jpeg,image/png,image/gif,image/webp,application/pdf"
                                class="hidden"
                                @change="seleccionarArchivos"
                            />
                        </div>

                        <p v-if="errorArchivos" class="text-xs text-red-500">{{ errorArchivos }}</p>
                        <p v-if="form.errors.adjuntos" class="text-xs text-red-500">{{ form.errors.adjuntos }}</p>

                        <!-- Previsualizaciones -->
                        <div v-if="archivosPreview.length" class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                            <div
                                v-for="(item, i) in archivosPreview"
                                :key="i"
                                class="relative group rounded-lg overflow-hidden border border-slate-700 bg-slate-800"
                            >
                                <img
                                    v-if="item.esImagen"
                                    :src="item.url"
                                    :alt="item.nombre"
                                    class="h-24 w-full object-cover"
                                />
                                <div v-else class="flex h-24 items-center justify-center">
                                    <svg class="h-8 w-8 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                              d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                                    </svg>
                                </div>
                                <div class="px-2 py-1">
                                    <p class="truncate text-xs text-slate-400">{{ item.nombre }}</p>
                                </div>
                                <button
                                    type="button"
                                    @click.stop="quitarArchivo(i)"
                                    class="absolute top-1 right-1 rounded-full bg-slate-900/80 p-1 text-slate-400 hover:text-red-400 opacity-0 group-hover:opacity-100 transition-opacity"
                                >
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
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
