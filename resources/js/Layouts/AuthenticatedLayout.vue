<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import { Link, router, usePage } from '@inertiajs/vue3';

const page = usePage();
const showingNavigationDropdown = ref(false);

// ── Notificaciones ────────────────────────────────────────────────────────

const contadorNoLeidas = ref(page.props.notificaciones_no_leidas ?? 0);

router.on('finish', () => {
    contadorNoLeidas.value = page.props.notificaciones_no_leidas ?? 0;
});

// ── Dropdown de notificaciones ────────────────────────────────────────────

const dropdownAbierto     = ref(false);
const notificacionesLista = ref([]);
const cargandoLista       = ref(false);

const toggleDropdown = async () => {
    dropdownAbierto.value = !dropdownAbierto.value;
    if (dropdownAbierto.value) await cargarUltimas();
};

const cerrarDropdown = () => { dropdownAbierto.value = false; };

const cargarUltimas = async () => {
    cargandoLista.value = true;
    try {
        const res = await fetch(route('notificaciones.ultimas'), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });
        notificacionesLista.value = await res.json();
    } catch {
        // silencioso
    } finally {
        cargandoLista.value = false;
    }
};

const csrfToken = () =>
    document.querySelector('meta[name="csrf-token"]')?.content ?? '';

const marcarLeida = async (notificacion) => {
    if (!notificacion.leido) {
        try {
            await fetch(route('notificaciones.update', notificacion.id), {
                method: 'PATCH',
                headers: {
                    'X-CSRF-TOKEN':     csrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type':     'application/json',
                },
            });
            notificacion.leido = true;
            contadorNoLeidas.value = Math.max(0, contadorNoLeidas.value - 1);
        } catch { /* silencioso */ }
    }
    cerrarDropdown();
    if (notificacion.bug_id) router.visit(route('bugs.show', notificacion.bug_id));
};

const marcarTodasLeidas = async () => {
    try {
        await fetch(route('notificaciones.leerTodas'), {
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN':     csrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        notificacionesLista.value.forEach(n => { n.leido = true; });
        contadorNoLeidas.value = 0;
    } catch { /* silencioso */ }
};

// ── Polling cada 30 segundos ──────────────────────────────────────────────

let pollingTimer = null;

const iniciarPolling = () => {
    pollingTimer = setInterval(async () => {
        try {
            const res  = await fetch(route('notificaciones.conteo'), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
            const data = await res.json();
            contadorNoLeidas.value = data.count ?? 0;
        } catch { /* silencioso */ }
    }, 30_000);
};

const campanaRef = ref(null);
const handleClickFuera = (e) => {
    if (dropdownAbierto.value && campanaRef.value && !campanaRef.value.contains(e.target)) {
        cerrarDropdown();
    }
};

onMounted(() => {
    iniciarPolling();
    document.addEventListener('click', handleClickFuera);
});

onUnmounted(() => {
    clearInterval(pollingTimer);
    document.removeEventListener('click', handleClickFuera);
});

// ── Helpers ───────────────────────────────────────────────────────────────

const iconosTipo = {
    asignacion:   '👤',
    sla_vence:    '⏰',
    escalamiento: '🔴',
    resolucion:   '✅',
    comentario:   '💬',
    critico:      '🚨',
};

const tiempoAtras = (iso) => {
    const diff = Date.now() - new Date(iso);
    const min  = Math.floor(diff / 60_000);
    const h    = Math.floor(diff / 3_600_000);
    const d    = Math.floor(diff / 86_400_000);
    if (min < 1)  return 'ahora';
    if (min < 60) return `${min}m`;
    if (h   < 24) return `${h}h`;
    return `${d}d`;
};

const hayNoLeidas = computed(() => contadorNoLeidas.value > 0);

const inicial = computed(() =>
    (page.props.auth?.user?.name ?? 'U').charAt(0).toUpperCase()
);

const homeRoute = computed(() => {
    const rol = page.props.auth?.user?.rol_global;
    if (rol === 'admin')        return route('dashboard');
    if (rol === 'desarrollador') return route('mi-panel');
    return route('bugs.index');
});
</script>

<template>
    <div class="flex h-screen bg-slate-950 overflow-hidden">

        <!-- ══ SIDEBAR ════════════════════════════════════════════════════════════ -->
        <aside class="hidden sm:flex w-60 flex-col bg-slate-900 border-r border-slate-800 flex-shrink-0">

            <!-- Brand -->
            <div class="h-14 flex items-center px-4 border-b border-slate-800 flex-shrink-0">
                <Link :href="homeRoute" class="flex items-center gap-3 w-full">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-cyan-400 to-cyan-600 flex items-center justify-center shadow-lg shadow-cyan-500/25 flex-shrink-0">
                        <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="leading-none">
                        <p class="text-sm font-bold text-white tracking-wide">SGDB</p>
                        <p class="text-xs text-slate-500 mt-0.5">Gestión de Bugs</p>
                    </div>
                </Link>
            </div>

            <!-- Navegación -->
            <nav class="flex-1 px-2 py-4 space-y-0.5 overflow-y-auto">

                <p class="px-3 mb-2 text-xs font-semibold text-slate-600 uppercase tracking-wider">General</p>

                <NavLink
                    v-if="$page.props.auth.user.rol_global === 'admin'"
                    :href="route('dashboard')"
                    :active="route().current('dashboard')"
                >
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    Dashboard
                </NavLink>

                <NavLink
                    v-if="$page.props.auth.user.rol_global === 'desarrollador'"
                    :href="route('mi-panel')"
                    :active="route().current('mi-panel')"
                >
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    Mi Panel
                </NavLink>

                <NavLink
                    :href="route('proyectos.index')"
                    :active="route().current('proyectos.*')"
                >
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                    </svg>
                    Proyectos
                </NavLink>

                <NavLink
                    :href="route('bugs.index')"
                    :active="route().current('bugs.*')"
                >
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4M20 12a8 8 0 11-16 0 8 8 0 0116 0zm-8-4v1m0 6v1m-4-4h1m6 0h1"/>
                    </svg>
                    Bugs
                </NavLink>

                <NavLink
                    :href="route('notificaciones.index')"
                    :active="route().current('notificaciones.*')"
                >
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                    <span class="flex-1">Notificaciones</span>
                    <span
                        v-if="hayNoLeidas"
                        class="flex-shrink-0 h-4 min-w-[1rem] px-1 flex items-center justify-center rounded-full bg-rose-500 text-xs font-bold text-white leading-none"
                    >
                        {{ contadorNoLeidas > 9 ? '9+' : contadorNoLeidas }}
                    </span>
                </NavLink>

                <!-- Sección Admin -->
                <template v-if="$page.props.auth.user.rol_global === 'admin'">
                    <p class="px-3 pt-4 mb-2 text-xs font-semibold text-slate-600 uppercase tracking-wider">Administración</p>
                    <NavLink
                        :href="route('usuarios.index')"
                        :active="route().current('usuarios.*')"
                    >
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                        Usuarios
                    </NavLink>
                </template>

            </nav>

            <!-- Usuario en el fondo del sidebar -->
            <div class="px-3 py-3 border-t border-slate-800 flex-shrink-0">
                <div class="flex items-center gap-2.5 px-2 py-2 rounded-lg hover:bg-slate-800/60 transition-colors">
                    <div class="w-7 h-7 rounded-full bg-gradient-to-br from-cyan-400 to-cyan-600 flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                        {{ inicial }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-semibold text-slate-200 truncate leading-none">{{ $page.props.auth.user.name }}</p>
                        <p class="text-xs text-slate-500 truncate mt-0.5 leading-none capitalize">{{ $page.props.auth.user.rol_global }}</p>
                    </div>
                    <Link
                        :href="route('logout')"
                        method="post"
                        as="button"
                        title="Cerrar sesión"
                        class="p-1 text-slate-600 hover:text-rose-400 transition-colors rounded flex-shrink-0"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                    </Link>
                </div>
            </div>
        </aside>

        <!-- ══ ÁREA PRINCIPAL ═════════════════════════════════════════════════════ -->
        <div class="flex-1 flex flex-col overflow-hidden min-w-0">

            <!-- Barra superior -->
            <header class="h-14 bg-slate-900 border-b border-slate-800 flex items-center justify-between px-5 flex-shrink-0 z-10">

                <!-- Izquierda: hamburger (móvil) + título de página -->
                <div class="flex items-center gap-3 min-w-0">
                    <button
                        @click="showingNavigationDropdown = !showingNavigationDropdown"
                        class="sm:hidden p-1.5 rounded-md text-slate-400 hover:text-slate-100 hover:bg-slate-800 transition-colors"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>

                    <div v-if="$slots.header" class="min-w-0 truncate">
                        <slot name="header" />
                    </div>
                </div>

                <!-- Derecha: campana + usuario -->
                <div class="flex items-center gap-1.5 flex-shrink-0">

                    <!-- Campana de notificaciones -->
                    <div class="relative" ref="campanaRef">
                        <button
                            @click="toggleDropdown"
                            class="relative p-2 rounded-lg text-slate-400 hover:text-slate-100 hover:bg-slate-800 transition-colors"
                            :class="{ 'bg-slate-800 text-slate-100': dropdownAbierto }"
                            aria-label="Notificaciones"
                        >
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                            <span
                                v-if="hayNoLeidas"
                                class="absolute -right-0.5 -top-0.5 flex h-4 w-4 items-center justify-center rounded-full bg-rose-500 text-xs font-bold text-white leading-none"
                            >
                                {{ contadorNoLeidas > 9 ? '9+' : contadorNoLeidas }}
                            </span>
                        </button>

                        <!-- Dropdown notificaciones -->
                        <Transition
                            enter-active-class="transition-all duration-150"
                            enter-from-class="opacity-0 scale-95 translate-y-1"
                            enter-to-class="opacity-100 scale-100 translate-y-0"
                            leave-active-class="transition-all duration-100"
                            leave-from-class="opacity-100 scale-100 translate-y-0"
                            leave-to-class="opacity-0 scale-95 translate-y-1"
                        >
                            <div
                                v-if="dropdownAbierto"
                                class="absolute right-0 top-full mt-2 w-80 rounded-xl bg-slate-900 shadow-2xl shadow-black/50 border border-slate-700 z-50 overflow-hidden"
                            >
                                <div class="flex items-center justify-between px-4 py-3 border-b border-slate-800">
                                    <span class="text-sm font-semibold text-slate-100">
                                        Notificaciones
                                        <span
                                            v-if="hayNoLeidas"
                                            class="ml-1.5 inline-flex h-5 w-5 items-center justify-center rounded-full bg-rose-500/20 text-xs font-bold text-rose-400"
                                        >
                                            {{ contadorNoLeidas }}
                                        </span>
                                    </span>
                                    <button
                                        v-if="hayNoLeidas"
                                        @click="marcarTodasLeidas"
                                        class="text-xs text-cyan-400 hover:text-cyan-300 font-medium transition-colors"
                                    >
                                        Marcar todas leídas
                                    </button>
                                </div>

                                <div class="max-h-80 overflow-y-auto divide-y divide-slate-800/60">
                                    <div v-if="cargandoLista" class="px-4 py-6 text-center text-sm text-slate-500">
                                        Cargando...
                                    </div>
                                    <div
                                        v-else-if="notificacionesLista.length === 0"
                                        class="px-4 py-6 text-center text-sm text-slate-500"
                                    >
                                        Sin notificaciones recientes
                                    </div>
                                    <button
                                        v-else
                                        v-for="n in notificacionesLista"
                                        :key="n.id"
                                        @click="marcarLeida(n)"
                                        class="w-full flex items-start gap-3 px-4 py-3 text-left transition-colors hover:bg-slate-800/70"
                                        :class="{ 'bg-cyan-500/5': !n.leido }"
                                    >
                                        <span class="text-base flex-shrink-0 mt-0.5">{{ iconosTipo[n.tipo] ?? '🔔' }}</span>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-xs font-semibold text-slate-200 truncate">{{ n.titulo }}</p>
                                            <p class="text-xs text-slate-500 line-clamp-2 mt-0.5">{{ n.mensaje }}</p>
                                            <span class="text-xs text-slate-600 mt-1 block">{{ tiempoAtras(n.created_at) }}</span>
                                        </div>
                                        <span v-if="!n.leido" class="mt-1.5 h-2 w-2 flex-shrink-0 rounded-full bg-cyan-400"/>
                                    </button>
                                </div>

                                <div class="border-t border-slate-800 px-4 py-2.5">
                                    <Link
                                        :href="route('notificaciones.index')"
                                        @click="cerrarDropdown"
                                        class="block text-center text-xs font-medium text-cyan-400 hover:text-cyan-300 transition-colors"
                                    >
                                        Ver todas las notificaciones →
                                    </Link>
                                </div>
                            </div>
                        </Transition>
                    </div>

                    <!-- Dropdown de perfil -->
                    <Dropdown align="right" width="48" contentClasses="py-1 bg-slate-900 border border-slate-700 rounded-xl shadow-2xl shadow-black/40">
                        <template #trigger>
                            <button
                                type="button"
                                class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-sm text-slate-400 hover:text-slate-100 hover:bg-slate-800 transition-colors"
                            >
                                <div class="w-6 h-6 rounded-full bg-gradient-to-br from-cyan-400 to-cyan-600 flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                                    {{ inicial }}
                                </div>
                                <span class="hidden md:block font-medium text-slate-300 text-sm">{{ $page.props.auth.user.name }}</span>
                                <svg class="w-3.5 h-3.5 text-slate-500" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                </svg>
                            </button>
                        </template>
                        <template #content>
                            <DropdownLink :href="route('profile.edit')">Perfil</DropdownLink>
                            <DropdownLink
                                v-if="$page.props.auth.user.rol_global === 'admin'"
                                :href="route('usuarios.index')"
                            >
                                Usuarios
                            </DropdownLink>
                            <DropdownLink :href="route('logout')" method="post" as="button">
                                Cerrar Sesión
                            </DropdownLink>
                        </template>
                    </Dropdown>
                </div>
            </header>

            <!-- Contenido principal -->
            <main class="flex-1 overflow-y-auto bg-slate-950">
                <slot />
            </main>
        </div>

        <!-- ══ MENÚ MÓVIL ═════════════════════════════════════════════════════════ -->
        <Transition
            enter-active-class="transition-opacity duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="showingNavigationDropdown"
                class="fixed inset-0 z-30 bg-black/70 sm:hidden"
                @click="showingNavigationDropdown = false"
            />
        </Transition>

        <Transition
            enter-active-class="transition-transform duration-200 ease-out"
            enter-from-class="-translate-x-full"
            enter-to-class="translate-x-0"
            leave-active-class="transition-transform duration-150 ease-in"
            leave-from-class="translate-x-0"
            leave-to-class="-translate-x-full"
        >
            <aside
                v-if="showingNavigationDropdown"
                class="fixed inset-y-0 left-0 z-40 w-60 bg-slate-900 border-r border-slate-800 flex flex-col sm:hidden"
            >
                <!-- Brand (móvil) -->
                <div class="h-14 flex items-center justify-between px-4 border-b border-slate-800">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-cyan-400 to-cyan-600 flex items-center justify-center shadow-lg shadow-cyan-500/25">
                            <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <span class="text-sm font-bold text-white">SGDB</span>
                    </div>
                    <button @click="showingNavigationDropdown = false" class="p-1.5 rounded-md text-slate-400 hover:text-slate-100 hover:bg-slate-800">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <nav class="flex-1 px-2 py-4 space-y-0.5 overflow-y-auto">
                    <NavLink
                        v-if="$page.props.auth.user.rol_global === 'admin'"
                        :href="route('dashboard')"
                        :active="route().current('dashboard')"
                        @click="showingNavigationDropdown = false"
                    >
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                        Dashboard
                    </NavLink>
                    <NavLink
                        v-if="$page.props.auth.user.rol_global === 'desarrollador'"
                        :href="route('mi-panel')"
                        :active="route().current('mi-panel')"
                        @click="showingNavigationDropdown = false"
                    >
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        Mi Panel
                    </NavLink>
                    <NavLink
                        :href="route('proyectos.index')"
                        :active="route().current('proyectos.*')"
                        @click="showingNavigationDropdown = false"
                    >
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                        </svg>
                        Proyectos
                    </NavLink>
                    <NavLink
                        :href="route('bugs.index')"
                        :active="route().current('bugs.*')"
                        @click="showingNavigationDropdown = false"
                    >
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4M20 12a8 8 0 11-16 0 8 8 0 0116 0zm-8-4v1m0 6v1m-4-4h1m6 0h1"/>
                        </svg>
                        Bugs
                    </NavLink>
                    <NavLink
                        :href="route('notificaciones.index')"
                        :active="route().current('notificaciones.*')"
                        @click="showingNavigationDropdown = false"
                    >
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                        <span class="flex-1">Notificaciones</span>
                        <span v-if="hayNoLeidas" class="h-4 min-w-[1rem] px-1 flex items-center justify-center rounded-full bg-rose-500 text-xs font-bold text-white">
                            {{ contadorNoLeidas > 9 ? '9+' : contadorNoLeidas }}
                        </span>
                    </NavLink>

                    <template v-if="$page.props.auth.user.rol_global === 'admin'">
                        <p class="px-3 pt-4 mb-2 text-xs font-semibold text-slate-600 uppercase tracking-wider">Administración</p>
                        <NavLink
                            :href="route('usuarios.index')"
                            :active="route().current('usuarios.*')"
                            @click="showingNavigationDropdown = false"
                        >
                            <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
                            Usuarios
                        </NavLink>
                    </template>
                </nav>

                <div class="px-3 py-3 border-t border-slate-800">
                    <div class="flex items-center gap-2.5 px-2 py-2">
                        <div class="w-7 h-7 rounded-full bg-gradient-to-br from-cyan-400 to-cyan-600 flex items-center justify-center text-white text-xs font-bold">
                            {{ inicial }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-semibold text-slate-200 truncate">{{ $page.props.auth.user.name }}</p>
                            <p class="text-xs text-slate-500 truncate capitalize">{{ $page.props.auth.user.rol_global }}</p>
                        </div>
                    </div>
                </div>
            </aside>
        </Transition>

    </div>
</template>
