<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    proyectos: Array,
});

const form = useForm({
    nombre_reportante:        '',
    email_reportante:         '',
    proyecto_id:              '',
    titulo:                   '',
    descripcion:              '',
    modulo:                   '',
    pasos_reproducir:         '',
    comportamiento_esperado:  '',
    comportamiento_actual:    '',
    adjuntos:                 [],
});

// ---- Manejo de archivos adjuntos ----
const inputArchivos = ref(null);
const archivosPreview = ref([]);

const MAX_ARCHIVOS = 5;
const MAX_MB = 5;

const errorArchivos = ref('');

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

// ---- Envío ----
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
    }).post(route('reporte-publico.store'));
};
</script>

<template>
    <Head title="Reportar un Problema" />

    <div class="min-h-screen bg-slate-950 py-10 px-4">

        <!-- Encabezado -->
        <div class="mx-auto max-w-2xl mb-8 text-center">
            <div class="inline-flex items-center gap-3 mb-4">
                <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-cyan-400 to-cyan-600 flex items-center justify-center shadow-lg shadow-cyan-500/25">
                    <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="text-left leading-none">
                    <p class="text-2xl font-bold text-white tracking-wide">SGDB</p>
                    <p class="text-xs text-slate-500 mt-0.5">Sistema de Gestión de Bugs</p>
                </div>
            </div>
            <h1 class="text-2xl font-bold text-white">Reportar un Problema</h1>
            <p class="mt-2 text-sm text-slate-400">
                Completá el formulario para que el equipo de sistemas pueda atender tu reporte.
            </p>
        </div>

        <!-- Formulario -->
        <form @submit.prevent="submit" class="mx-auto max-w-2xl space-y-6">

            <!-- Sección: Tus datos -->
            <div class="rounded-xl bg-slate-900 border border-slate-800 p-6 space-y-4">
                <h2 class="text-sm font-semibold uppercase tracking-wider text-cyan-400 border-b border-slate-800 pb-2">
                    Tus datos
                </h2>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <!-- Nombre -->
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-1">
                            Nombre completo <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="form.nombre_reportante"
                            type="text"
                            placeholder="Tu nombre..."
                            class="block w-full rounded-lg border bg-slate-800 px-3 py-2 text-sm text-slate-100 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-cyan-500"
                            :class="form.errors.nombre_reportante ? 'border-red-500' : 'border-slate-700'"
                        />
                        <p v-if="form.errors.nombre_reportante" class="mt-1 text-xs text-red-500">
                            {{ form.errors.nombre_reportante }}
                        </p>
                    </div>

                    <!-- Email -->
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-1">
                            Correo electrónico <span class="text-slate-500 font-normal">(opcional)</span>
                        </label>
                        <input
                            v-model="form.email_reportante"
                            type="email"
                            placeholder="tu@correo.com"
                            class="block w-full rounded-lg border bg-slate-800 px-3 py-2 text-sm text-slate-100 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-cyan-500"
                            :class="form.errors.email_reportante ? 'border-red-500' : 'border-slate-700'"
                        />
                        <p v-if="form.errors.email_reportante" class="mt-1 text-xs text-red-500">
                            {{ form.errors.email_reportante }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Sección: El problema -->
            <div class="rounded-xl bg-slate-900 border border-slate-800 p-6 space-y-4">
                <h2 class="text-sm font-semibold uppercase tracking-wider text-cyan-400 border-b border-slate-800 pb-2">
                    El problema
                </h2>

                <!-- Sistema afectado -->
                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-1">
                        Sistema afectado <span class="text-red-500">*</span>
                    </label>
                    <select
                        v-model="form.proyecto_id"
                        class="block w-full rounded-lg border bg-slate-800 px-3 py-2 text-sm text-slate-100 focus:outline-none focus:ring-2 focus:ring-cyan-500"
                        :class="form.errors.proyecto_id ? 'border-red-500' : 'border-slate-700'"
                    >
                        <option value="" disabled class="text-slate-500">Seleccioná el sistema...</option>
                        <option v-for="p in proyectos" :key="p.id" :value="p.id">
                            {{ p.nombre }}
                        </option>
                    </select>
                    <p v-if="form.errors.proyecto_id" class="mt-1 text-xs text-red-500">
                        {{ form.errors.proyecto_id }}
                    </p>
                </div>

                <!-- Título -->
                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-1">
                        Título del problema <span class="text-red-500">*</span>
                    </label>
                    <input
                        v-model="form.titulo"
                        type="text"
                        placeholder="Resumen breve del problema..."
                        class="block w-full rounded-lg border bg-slate-800 px-3 py-2 text-sm text-slate-100 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-cyan-500"
                        :class="form.errors.titulo ? 'border-red-500' : 'border-slate-700'"
                    />
                    <p v-if="form.errors.titulo" class="mt-1 text-xs text-red-500">
                        {{ form.errors.titulo }}
                    </p>
                </div>

                <!-- Descripción -->
                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-1">
                        Descripción <span class="text-red-500">*</span>
                    </label>
                    <textarea
                        v-model="form.descripcion"
                        rows="4"
                        placeholder="Describí el problema con el mayor detalle posible..."
                        class="block w-full rounded-lg border bg-slate-800 px-3 py-2 text-sm text-slate-100 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-cyan-500"
                        :class="form.errors.descripcion ? 'border-red-500' : 'border-slate-700'"
                    />
                    <p v-if="form.errors.descripcion" class="mt-1 text-xs text-red-500">
                        {{ form.errors.descripcion }}
                    </p>
                </div>

                <!-- Módulo -->
                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-1">
                        Módulo o sección <span class="text-slate-500 font-normal">(opcional)</span>
                    </label>
                    <input
                        v-model="form.modulo"
                        type="text"
                        placeholder="Ej: Login, Facturación, Reportes..."
                        class="block w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-slate-100 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-cyan-500"
                    />
                </div>
            </div>

            <!-- Sección: Reproducción (opcional) -->
            <div class="rounded-xl bg-slate-900 border border-slate-800 p-6 space-y-4">
                <h2 class="text-sm font-semibold uppercase tracking-wider text-cyan-400 border-b border-slate-800 pb-2">
                    Detalles adicionales
                    <span class="ml-2 text-xs font-normal normal-case text-slate-500">(opcional, pero ayuda mucho)</span>
                </h2>

                <!-- Pasos -->
                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-1">
                        Pasos para reproducir el problema
                    </label>
                    <textarea
                        v-model="form.pasos_reproducir"
                        rows="3"
                        placeholder="1. Ir a la pantalla X&#10;2. Hacer clic en Y&#10;3. Ver el error..."
                        class="block w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-slate-100 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-cyan-500"
                    />
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <!-- Esperado -->
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-1">
                            ¿Qué debería pasar?
                        </label>
                        <textarea
                            v-model="form.comportamiento_esperado"
                            rows="3"
                            placeholder="El resultado esperado..."
                            class="block w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-slate-100 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-cyan-500"
                        />
                    </div>
                    <!-- Actual -->
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-1">
                            ¿Qué está pasando?
                        </label>
                        <textarea
                            v-model="form.comportamiento_actual"
                            rows="3"
                            placeholder="Lo que realmente ocurre..."
                            class="block w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-slate-100 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-cyan-500"
                        />
                    </div>
                </div>
            </div>

            <!-- Sección: Adjuntos -->
            <div class="rounded-xl bg-slate-900 border border-slate-800 p-6 space-y-4">
                <h2 class="text-sm font-semibold uppercase tracking-wider text-cyan-400 border-b border-slate-800 pb-2">
                    Capturas de pantalla
                    <span class="ml-2 text-xs font-normal normal-case text-slate-500">(opcional)</span>
                </h2>

                <!-- Zona de subida -->
                <div
                    class="relative flex flex-col items-center justify-center rounded-lg border-2 border-dashed border-slate-700 px-6 py-8 text-center cursor-pointer hover:border-cyan-600 transition-colors"
                    @click="inputArchivos.click()"
                >
                    <svg class="mb-3 h-10 w-10 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/>
                    </svg>
                    <p class="text-sm text-slate-400">
                        Hacé clic para adjuntar imágenes o archivos
                    </p>
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

                <!-- Error archivos -->
                <p v-if="errorArchivos" class="text-xs text-red-500">{{ errorArchivos }}</p>
                <p v-if="form.errors.adjuntos" class="text-xs text-red-500">{{ form.errors.adjuntos }}</p>

                <!-- Previsualizaciones -->
                <div v-if="archivosPreview.length" class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                    <div
                        v-for="(item, i) in archivosPreview"
                        :key="i"
                        class="relative group rounded-lg overflow-hidden border border-slate-700 bg-slate-800"
                    >
                        <!-- Imagen preview -->
                        <img
                            v-if="item.esImagen"
                            :src="item.url"
                            :alt="item.nombre"
                            class="h-28 w-full object-cover"
                        />
                        <!-- Ícono para no-imágenes -->
                        <div v-else class="flex h-28 items-center justify-center">
                            <svg class="h-10 w-10 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                      d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                            </svg>
                        </div>

                        <!-- Nombre -->
                        <div class="px-2 py-1">
                            <p class="truncate text-xs text-slate-400">{{ item.nombre }}</p>
                        </div>

                        <!-- Botón quitar -->
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

            <!-- Botón enviar -->
            <div class="flex justify-end">
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="flex items-center gap-2 rounded-xl bg-cyan-500 px-8 py-3 text-sm font-semibold text-white shadow-lg shadow-cyan-500/20 hover:bg-cyan-400 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                >
                    <svg v-if="form.processing" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                    </svg>
                    <span>{{ form.processing ? 'Enviando...' : 'Enviar reporte' }}</span>
                </button>
            </div>

        </form>

        <!-- Footer -->
        <p class="mt-10 text-center text-xs text-slate-700">
            ¿Sos parte del equipo?
            <a :href="route('login')" class="text-cyan-700 hover:text-cyan-500">Iniciá sesión</a>
        </p>

    </div>
</template>
