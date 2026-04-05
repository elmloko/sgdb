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
            <h2 class="text-xl font-semibold leading-tight text-slate-100">
                Notificaciones
            </h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <div class="overflow-hidden rounded-lg bg-slate-900 shadow divide-y divide-slate-800/60">

                    <div v-if="notificaciones.data.length === 0" class="px-6 py-12 text-center text-slate-500">
                        No tienes notificaciones.
                    </div>

                    <div
                        v-for="n in notificaciones.data"
                        :key="n.id"
                        class="flex items-start gap-4 px-5 py-4 transition-colors"
                        :class="n.leido ? 'bg-slate-900' : 'bg-blue-50'"
                    >
                        <!-- Ícono del tipo -->
                        <span class="mt-0.5 text-xl flex-shrink-0">
                            {{ iconosTipo[n.tipo] ?? '🔔' }}
                        </span>

                        <!-- Contenido -->
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-slate-100">{{ n.titulo }}</p>
                            <p class="mt-0.5 text-sm text-slate-400 line-clamp-2">{{ n.mensaje }}</p>
                            <div class="mt-1 flex items-center gap-3">
                                <span class="text-xs text-slate-500">{{ tiempoAtras(n.created_at) }}</span>
                                <Link
                                    v-if="n.bug_id"
                                    :href="route('bugs.show', n.bug_id)"
                                    class="text-xs font-medium text-cyan-400 hover:text-indigo-800"
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
                            link.active ? 'bg-cyan-500 text-white' : 'bg-slate-900 border border-slate-700 hover:bg-slate-800/50 text-slate-400',
                            !link.url ? 'opacity-40 pointer-events-none' : '',
                        ]"
                    />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
