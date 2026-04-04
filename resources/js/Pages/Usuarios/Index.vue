<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    usuarios: Array,
});

const busqueda = ref('');

const usuariosFiltrados = computed(() => {
    if (!busqueda.value) return props.usuarios;
    const q = busqueda.value.toLowerCase();
    return props.usuarios.filter(
        (u) =>
            u.name.toLowerCase().includes(q) ||
            u.email.toLowerCase().includes(q) ||
            u.rol_global.toLowerCase().includes(q),
    );
});

const etiquetaRol = {
    admin: 'Administrador',
    desarrollador: 'Desarrollador',
    qa: 'QA',
    reportante: 'Reportante',
};

const claseRol = {
    admin: 'bg-purple-100 text-purple-800',
    desarrollador: 'bg-blue-100 text-blue-800',
    qa: 'bg-yellow-100 text-yellow-800',
    reportante: 'bg-gray-100 text-gray-800',
};
</script>

<template>
    <Head title="Usuarios" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Gestión de Usuarios
                </h2>
                <Link
                    :href="route('usuarios.create')"
                    class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                >
                    + Nuevo Usuario
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <!-- Buscador -->
                <div class="mb-4">
                    <input
                        v-model="busqueda"
                        type="text"
                        placeholder="Buscar por nombre, correo o rol..."
                        class="w-full rounded-md border border-gray-300 px-4 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 sm:w-80"
                    />
                </div>

                <!-- Tabla -->
                <div class="overflow-hidden rounded-lg bg-white shadow">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                    Nombre
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                    Correo
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                    Rol Global
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                    Estado
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                    Miembro desde
                                </th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <tr v-if="usuariosFiltrados.length === 0">
                                <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500">
                                    No se encontraron usuarios.
                                </td>
                            </tr>
                            <tr
                                v-for="usuario in usuariosFiltrados"
                                :key="usuario.id"
                                class="hover:bg-gray-50"
                            >
                                <td class="whitespace-nowrap px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ usuario.name }}
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                    {{ usuario.email }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    <span
                                        class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold"
                                        :class="claseRol[usuario.rol_global]"
                                    >
                                        {{ etiquetaRol[usuario.rol_global] }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    <span
                                        class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold"
                                        :class="usuario.activo
                                            ? 'bg-green-100 text-green-800'
                                            : 'bg-red-100 text-red-800'"
                                    >
                                        {{ usuario.activo ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                    {{ new Date(usuario.created_at).toLocaleDateString('es-BO') }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                    <Link
                                        :href="route('usuarios.edit', usuario.id)"
                                        class="font-medium text-indigo-600 hover:text-indigo-900"
                                    >
                                        Editar
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <p class="mt-2 text-xs text-gray-400">
                    {{ usuariosFiltrados.length }} usuario(s) encontrado(s)
                </p>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
