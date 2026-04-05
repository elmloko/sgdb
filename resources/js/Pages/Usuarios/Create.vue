<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    rol_global: 'reportante',
    activo: true,
});

const submit = () => {
    form.post(route('usuarios.store'));
};
</script>

<template>
    <Head title="Nuevo Usuario" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('usuarios.index')" class="text-slate-500 hover:text-slate-400">
                    ← Volver
                </Link>
                <h2 class="text-xl font-semibold leading-tight text-slate-100">
                    Nuevo Usuario
                </h2>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-xl px-4 sm:px-6 lg:px-8">
                <div class="rounded-lg bg-slate-900 p-6 shadow">
                    <form @submit.prevent="submit" class="space-y-5">
                        <!-- Nombre -->
                        <div>
                            <label class="block text-sm font-medium text-slate-300">Nombre</label>
                            <input
                                v-model="form.name"
                                type="text"
                                class="mt-1 block w-full rounded-md border border-slate-700 px-3 py-2 text-sm shadow-sm focus:border-cyan-500 focus:outline-none focus:ring-1 focus:ring-cyan-500"
                                :class="{ 'border-red-500': form.errors.name }"
                            />
                            <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</p>
                        </div>

                        <!-- Email -->
                        <div>
                            <label class="block text-sm font-medium text-slate-300">Correo electrónico</label>
                            <input
                                v-model="form.email"
                                type="email"
                                class="mt-1 block w-full rounded-md border border-slate-700 px-3 py-2 text-sm shadow-sm focus:border-cyan-500 focus:outline-none focus:ring-1 focus:ring-cyan-500"
                                :class="{ 'border-red-500': form.errors.email }"
                            />
                            <p v-if="form.errors.email" class="mt-1 text-xs text-red-600">{{ form.errors.email }}</p>
                        </div>

                        <!-- Contraseña -->
                        <div>
                            <label class="block text-sm font-medium text-slate-300">Contraseña</label>
                            <input
                                v-model="form.password"
                                type="password"
                                class="mt-1 block w-full rounded-md border border-slate-700 px-3 py-2 text-sm shadow-sm focus:border-cyan-500 focus:outline-none focus:ring-1 focus:ring-cyan-500"
                                :class="{ 'border-red-500': form.errors.password }"
                            />
                            <p v-if="form.errors.password" class="mt-1 text-xs text-red-600">{{ form.errors.password }}</p>
                        </div>

                        <!-- Confirmar contraseña -->
                        <div>
                            <label class="block text-sm font-medium text-slate-300">Confirmar contraseña</label>
                            <input
                                v-model="form.password_confirmation"
                                type="password"
                                class="mt-1 block w-full rounded-md border border-slate-700 px-3 py-2 text-sm shadow-sm focus:border-cyan-500 focus:outline-none focus:ring-1 focus:ring-cyan-500"
                            />
                        </div>

                        <!-- Rol -->
                        <div>
                            <label class="block text-sm font-medium text-slate-300">Rol global</label>
                            <select
                                v-model="form.rol_global"
                                class="mt-1 block w-full rounded-md border border-slate-700 px-3 py-2 text-sm shadow-sm focus:border-cyan-500 focus:outline-none focus:ring-1 focus:ring-cyan-500"
                            >
                                <option value="admin">Administrador</option>
                                <option value="desarrollador">Desarrollador</option>
                                <option value="qa">QA</option>
                                <option value="reportante">Reportante</option>
                            </select>
                            <p v-if="form.errors.rol_global" class="mt-1 text-xs text-red-600">{{ form.errors.rol_global }}</p>
                        </div>

                        <!-- Estado -->
                        <div class="flex items-center gap-2">
                            <input
                                v-model="form.activo"
                                type="checkbox"
                                id="activo"
                                class="h-4 w-4 rounded border-slate-700 text-cyan-400"
                            />
                            <label for="activo" class="text-sm font-medium text-slate-300">Usuario activo</label>
                        </div>

                        <div class="flex justify-end gap-3 pt-2">
                            <Link
                                :href="route('usuarios.index')"
                                class="rounded-md border border-slate-700 px-4 py-2 text-sm font-medium text-slate-300 hover:bg-slate-800/50"
                            >
                                Cancelar
                            </Link>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="rounded-md bg-cyan-500 px-4 py-2 text-sm font-medium text-white hover:bg-cyan-400 disabled:opacity-50"
                            >
                                Crear Usuario
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
