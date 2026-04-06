<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    ticket: String,
});
</script>

<template>
    <Head title="Reporte enviado" />

    <div class="min-h-screen bg-slate-950 flex flex-col items-center justify-center px-4">

        <!-- Ícono de éxito -->
        <div class="mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-emerald-500/10 ring-1 ring-emerald-500/30">
            <svg class="h-10 w-10 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
            </svg>
        </div>

        <h1 class="text-2xl font-bold text-white mb-2">¡Reporte enviado!</h1>

        <p class="text-sm text-slate-400 mb-6 text-center max-w-sm">
            El equipo de sistemas recibió tu reporte y lo revisará a la brevedad.
        </p>

        <!-- Ticket -->
        <div v-if="ticket" class="mb-8 rounded-xl border border-slate-800 bg-slate-900 px-6 py-4 text-center">
            <p class="text-xs text-slate-500 mb-1">Tu número de ticket</p>
            <p class="text-2xl font-mono font-bold text-cyan-400 tracking-widest">{{ ticket }}</p>
            <p class="text-xs text-slate-600 mt-1">Guardalo para hacer seguimiento</p>
        </div>

        <Link
            :href="route('reporte-publico.create')"
            class="rounded-xl border border-slate-700 px-6 py-2 text-sm text-slate-400 hover:text-white hover:border-slate-600 transition-colors"
        >
            Reportar otro problema
        </Link>

    </div>
</template>
