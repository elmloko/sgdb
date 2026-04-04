<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { Link, router, usePage } from '@inertiajs/vue3';

const page = usePage();
const showingNavigationDropdown = ref(false);

// ── Notificaciones ────────────────────────────────────────────────────────

// Contador inicial desde shared props de Inertia (se actualiza por polling)
const contadorNoLeidas = ref(page.props.notificaciones_no_leidas ?? 0);

// Sincronizar con navegaciones Inertia (cada vez que cambia la página)
router.on('finish', () => {
    contadorNoLeidas.value = page.props.notificaciones_no_leidas ?? 0;
});

// ── Dropdown de notificaciones ────────────────────────────────────────────

const dropdownAbierto     = ref(false);
const notificacionesLista = ref([]);
const cargandoLista       = ref(false);

const toggleDropdown = async () => {
    dropdownAbierto.value = !dropdownAbierto.value;

    if (dropdownAbierto.value) {
        await cargarUltimas();
    }
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
                    'X-CSRF-TOKEN':    csrfToken(),
                    'X-Requested-With':'XMLHttpRequest',
                    'Content-Type':    'application/json',
                },
            });
            notificacion.leido = true;
            contadorNoLeidas.value = Math.max(0, contadorNoLeidas.value - 1);
        } catch { /* silencioso */ }
    }

    cerrarDropdown();

    if (notificacion.bug_id) {
        router.visit(route('bugs.show', notificacion.bug_id));
    }
};

const marcarTodasLeidas = async () => {
    try {
        await fetch(route('notificaciones.leerTodas'), {
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN':    csrfToken(),
                'X-Requested-With':'XMLHttpRequest',
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

// Cerrar dropdown al hacer clic fuera de él
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

// ── Helpers de visualización ──────────────────────────────────────────────

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
</script>

<template>
    <div>
        <div class="min-h-screen bg-gray-100">
            <nav class="border-b border-gray-100 bg-white">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="flex h-16 justify-between">
                        <div class="flex">
                            <!-- Logo -->
                            <div class="flex shrink-0 items-center">
                                <Link :href="$page.props.auth.user.rol_global === 'admin'
                                    ? route('dashboard')
                                    : $page.props.auth.user.rol_global === 'desarrollador'
                                        ? route('mi-panel')
                                        : route('bugs.index')">
                                    <ApplicationLogo
                                        class="block h-9 w-auto fill-current text-gray-800"
                                    />
                                </Link>
                            </div>

                            <!-- Links de navegación -->
                            <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                                <NavLink
                                    v-if="$page.props.auth.user.rol_global === 'admin'"
                                    :href="route('dashboard')"
                                    :active="route().current('dashboard')"
                                >
                                    Dashboard
                                </NavLink>
                                <NavLink
                                    v-if="$page.props.auth.user.rol_global === 'desarrollador'"
                                    :href="route('mi-panel')"
                                    :active="route().current('mi-panel')"
                                >
                                    Mi Panel
                                </NavLink>
                                <NavLink
                                    :href="route('proyectos.index')"
                                    :active="route().current('proyectos.*')"
                                >
                                    Proyectos
                                </NavLink>
                                <NavLink
                                    :href="route('bugs.index')"
                                    :active="route().current('bugs.*')"
                                >
                                    Bugs
                                </NavLink>
                            </div>
                        </div>

                        <!-- Controles derechos -->
                        <div class="hidden sm:ms-6 sm:flex sm:items-center gap-2">

                            <!-- ══ Campana de notificaciones ══════════════════ -->
                            <div class="relative" ref="campanaRef">
                                <button
                                    @click="toggleDropdown"
                                    class="relative rounded-md p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-700 focus:outline-none transition-colors"
                                    :class="{ 'bg-gray-100': dropdownAbierto }"
                                    aria-label="Notificaciones"
                                >
                                    <!-- Ícono campana -->
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                                    </svg>

                                    <!-- Badge contador -->
                                    <span
                                        v-if="hayNoLeidas"
                                        class="absolute -right-0.5 -top-0.5 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-xs font-bold text-white leading-none"
                                    >
                                        {{ contadorNoLeidas > 9 ? '9+' : contadorNoLeidas }}
                                    </span>
                                </button>

                                <!-- Dropdown de notificaciones -->
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
                                        class="absolute right-0 top-full mt-2 w-80 rounded-xl bg-white shadow-xl border border-gray-200 z-50 overflow-hidden"
                                    >
                                        <!-- Cabecera dropdown -->
                                        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
                                            <span class="text-sm font-semibold text-gray-800">
                                                Notificaciones
                                                <span
                                                    v-if="hayNoLeidas"
                                                    class="ml-1.5 inline-flex h-5 w-5 items-center justify-center rounded-full bg-red-100 text-xs font-bold text-red-600"
                                                >
                                                    {{ contadorNoLeidas }}
                                                </span>
                                            </span>
                                            <button
                                                v-if="hayNoLeidas"
                                                @click="marcarTodasLeidas"
                                                class="text-xs text-indigo-600 hover:text-indigo-800 font-medium"
                                            >
                                                Marcar todas leídas
                                            </button>
                                        </div>

                                        <!-- Lista de notificaciones -->
                                        <div class="max-h-80 overflow-y-auto divide-y divide-gray-50">
                                            <!-- Cargando -->
                                            <div v-if="cargandoLista" class="px-4 py-6 text-center text-sm text-gray-400">
                                                Cargando...
                                            </div>

                                            <!-- Sin notificaciones -->
                                            <div
                                                v-else-if="notificacionesLista.length === 0"
                                                class="px-4 py-6 text-center text-sm text-gray-400"
                                            >
                                                Sin notificaciones recientes
                                            </div>

                                            <!-- Items -->
                                            <button
                                                v-else
                                                v-for="n in notificacionesLista"
                                                :key="n.id"
                                                @click="marcarLeida(n)"
                                                class="w-full flex items-start gap-3 px-4 py-3 text-left transition-colors hover:bg-gray-50"
                                                :class="{ 'bg-indigo-50': !n.leido }"
                                            >
                                                <!-- Ícono tipo -->
                                                <span class="text-lg flex-shrink-0 mt-0.5">
                                                    {{ iconosTipo[n.tipo] ?? '🔔' }}
                                                </span>

                                                <!-- Texto -->
                                                <div class="flex-1 min-w-0">
                                                    <p class="text-xs font-semibold text-gray-800 truncate">
                                                        {{ n.titulo }}
                                                    </p>
                                                    <p class="text-xs text-gray-500 line-clamp-2 mt-0.5">
                                                        {{ n.mensaje }}
                                                    </p>
                                                    <span class="text-xs text-gray-400 mt-1 block">
                                                        {{ tiempoAtras(n.created_at) }}
                                                    </span>
                                                </div>

                                                <!-- Punto no leída -->
                                                <span
                                                    v-if="!n.leido"
                                                    class="mt-1.5 h-2 w-2 flex-shrink-0 rounded-full bg-indigo-500"
                                                />
                                            </button>
                                        </div>

                                        <!-- Pie del dropdown -->
                                        <div class="border-t border-gray-100 px-4 py-2.5">
                                            <Link
                                                :href="route('notificaciones.index')"
                                                @click="cerrarDropdown"
                                                class="block text-center text-xs font-medium text-indigo-600 hover:text-indigo-800"
                                            >
                                                Ver todas las notificaciones →
                                            </Link>
                                        </div>
                                    </div>
                                </Transition>
                            </div>
                            <!-- ════════════════════════════════════════════════ -->

                            <!-- Dropdown de usuario -->
                            <Dropdown align="right" width="48">
                                <template #trigger>
                                    <span class="inline-flex rounded-md">
                                        <button
                                            type="button"
                                            class="inline-flex items-center rounded-md border border-transparent bg-white px-3 py-2 text-sm font-medium leading-4 text-gray-500 transition duration-150 ease-in-out hover:text-gray-700 focus:outline-none"
                                        >
                                            {{ $page.props.auth.user.name }}
                                            <svg class="-me-0.5 ms-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </span>
                                </template>
                                <template #content>
                                    <DropdownLink :href="route('profile.edit')">
                                        Perfil
                                    </DropdownLink>
                                    <DropdownLink
                                        v-if="$page.props.auth.user.rol_global === 'admin'"
                                        :href="route('usuarios.index')"
                                    >
                                        Usuarios
                                    </DropdownLink>
                                    <DropdownLink
                                        :href="route('logout')"
                                        method="post"
                                        as="button"
                                    >
                                        Cerrar Sesión
                                    </DropdownLink>
                                </template>
                            </Dropdown>
                        </div>

                        <!-- Hamburger (móvil) -->
                        <div class="-me-2 flex items-center sm:hidden">
                            <button
                                @click="showingNavigationDropdown = !showingNavigationDropdown"
                                class="inline-flex items-center justify-center rounded-md p-2 text-gray-400 transition duration-150 ease-in-out hover:bg-gray-100 hover:text-gray-500 focus:bg-gray-100 focus:text-gray-500 focus:outline-none"
                            >
                                <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                    <path
                                        :class="{ hidden: showingNavigationDropdown, 'inline-flex': !showingNavigationDropdown }"
                                        stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 6h16M4 12h16M4 18h16"
                                    />
                                    <path
                                        :class="{ hidden: !showingNavigationDropdown, 'inline-flex': showingNavigationDropdown }"
                                        stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Menú móvil -->
                <div :class="{ block: showingNavigationDropdown, hidden: !showingNavigationDropdown }" class="sm:hidden">
                    <div class="space-y-1 pb-3 pt-2">
                        <ResponsiveNavLink
                            v-if="$page.props.auth.user.rol_global === 'admin'"
                            :href="route('dashboard')"
                            :active="route().current('dashboard')"
                        >
                            Dashboard
                        </ResponsiveNavLink>
                        <ResponsiveNavLink
                            v-if="$page.props.auth.user.rol_global === 'desarrollador'"
                            :href="route('mi-panel')"
                            :active="route().current('mi-panel')"
                        >
                            Mi Panel
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('proyectos.index')" :active="route().current('proyectos.*')">
                            Proyectos
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('bugs.index')" :active="route().current('bugs.*')">
                            Bugs
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('notificaciones.index')" :active="route().current('notificaciones.*')">
                            Notificaciones
                            <span v-if="hayNoLeidas" class="ml-1.5 rounded-full bg-red-500 px-1.5 py-0.5 text-xs text-white">
                                {{ contadorNoLeidas }}
                            </span>
                        </ResponsiveNavLink>
                    </div>

                    <div class="border-t border-gray-200 pb-1 pt-4">
                        <div class="px-4">
                            <div class="text-base font-medium text-gray-800">
                                {{ $page.props.auth.user.name }}
                            </div>
                            <div class="text-sm font-medium text-gray-500">
                                {{ $page.props.auth.user.email }}
                            </div>
                        </div>
                        <div class="mt-3 space-y-1">
                            <ResponsiveNavLink :href="route('profile.edit')">Perfil</ResponsiveNavLink>
                            <ResponsiveNavLink
                                v-if="$page.props.auth.user.rol_global === 'admin'"
                                :href="route('usuarios.index')"
                            >
                                Usuarios
                            </ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('logout')" method="post" as="button">
                                Cerrar Sesión
                            </ResponsiveNavLink>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Encabezado de página -->
            <header class="bg-white shadow" v-if="$slots.header">
                <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <!-- Contenido principal -->
            <main>
                <slot />
            </main>
        </div>
    </div>
</template>
