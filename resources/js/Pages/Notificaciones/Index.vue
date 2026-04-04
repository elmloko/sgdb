<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    notificaciones: Object, // paginación Laravel
});

const iconosTipo = {
    asignacion:  '👤',
    sla_vence:   '⏰',
    escalamiento:'🔴',
    resolucion:  '✅',
    comentario:  '💬',
    critico:     '🚨',
};

const tiempoAtras = (iso) => {
    const diff = Date.now() - new Date(iso);
    const min  = Math.floor(diff / 60_000);
    const h    = Math.floor(diff / 3_600_000);
    const d    = Math.floor(diff / 86_400_000);
    if (min < 1)  return 'ahora mismo';
    if (min < 60) return `hace ${min} min`;
    if (h   < 24) return `hace ${h}h`;
    return `hace ${d}d`;
};
</script>

<template>
    <Head title="Notificaciones" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Notificaciones
            </h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <div class="overflow-hidden rounded-lg bg-white shadow divide-y divide-gray-100">

                    <div v-if="notificaciones.data.length === 0" class="px-6 py-12 text-center text-gray-400">
                        No tienes notificaciones.
                    </div>

                    <div
                        v-for="n in notificaciones.data"
                        :key="n.id"
                        class="flex items-start gap-4 px-5 py-4 transition-colors"
                        :class="n.leido ? 'bg-white' : 'bg-blue-50'"
                    >
                        <!-- Ícono del tipo -->
                        <span class="mt-0.5 text-xl flex-shrink-0">
                            {{ iconosTipo[n.tipo] ?? '🔔' }}
                        </span>

                        <!-- Contenido -->
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900">{{ n.titulo }}</p>
                            <p class="mt-0.5 text-sm text-gray-600 line-clamp-2">{{ n.mensaje }}</p>
                            <div class="mt-1 flex items-center gap-3">
                                <span class="text-xs text-gray-400">{{ tiempoAtras(n.created_at) }}</span>
                                <Link
                                    v-if="n.bug_id"
                                    :href="route('bugs.show', n.bug_id)"
                                    class="text-xs font-medium text-indigo-600 hover:text-indigo-800"
                                >
                                    Ver bug →
                                </Link>
                            </div>
                        </div>

                        <!-- Indicador no leído -->
                        <span
                            v-if="!n.leido"
                            class="mt-2 h-2 w-2 flex-shrink-0 rounded-full bg-indigo-500"
                        />
                    </div>
                </div>

                <!-- Paginación -->
                <div v-if="notificaciones.last_page > 1" class="mt-4 flex justify-center gap-1 text-sm">
                    <Link
                        v-for="link in notificaciones.links"
                        :key="link.label"
                        :href="link.url ?? '#'"
                        v-html="link.label"
                        class="rounded px-3 py-1"
                        :class="[
                            link.active ? 'bg-indigo-600 text-white' : 'bg-white border border-gray-300 hover:bg-gray-50 text-gray-600',
                            !link.url ? 'opacity-40 pointer-events-none' : '',
                        ]"
                    />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
