<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="flex min-h-screen flex-col items-center justify-center bg-slate-950 px-4">

        <!-- Logo / Brand -->
        <div class="mb-8 text-center">
            <Link href="/" class="inline-flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-cyan-400 to-cyan-600 flex items-center justify-center shadow-lg shadow-cyan-500/25">
                    <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="text-left leading-none">
                    <p class="text-xl font-bold text-white tracking-wide">SGDB</p>
                    <p class="text-xs text-slate-500 mt-0.5">Gestión de Bugs y Proyectos</p>
                </div>
            </Link>
        </div>

        <!-- Card del formulario -->
        <div class="w-full sm:max-w-md bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl shadow-black/40 px-8 py-8">
            <slot />
        </div>
    </div>
</template>
