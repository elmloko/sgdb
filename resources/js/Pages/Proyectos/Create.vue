<script setup>
import { useForm } from '@inertiajs/vue3'
import { Head, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import InputLabel from '@/Components/InputLabel.vue'
import TextInput from '@/Components/TextInput.vue'
import InputError from '@/Components/InputError.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'

const form = useForm({
    nombre:             '',
    descripcion:        '',
    estado:             'activo',
    fecha_inicio:       '',
    fecha_fin_estimada: '',
})

const submit = () => form.post(route('proyectos.store'))
</script>

<template>
    <Head title="Nuevo Proyecto" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-slate-100">Nuevo Proyecto</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
                <div class="rounded-xl border border-slate-800 bg-slate-900 p-6 shadow-sm">
                    <form @submit.prevent="submit" class="space-y-5">

                        <div>
                            <InputLabel for="nombre" value="Nombre del proyecto *" />
                            <TextInput id="nombre" v-model="form.nombre" class="mt-1 block w-full" autofocus />
                            <InputError :message="form.errors.nombre" class="mt-1" />
                        </div>

                        <div>
                            <InputLabel for="descripcion" value="Descripción" />
                            <textarea
                                id="descripcion"
                                v-model="form.descripcion"
                                rows="3"
                                class="mt-1 block w-full rounded-md border-slate-700 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 text-sm"
                            />
                            <InputError :message="form.errors.descripcion" class="mt-1" />
                        </div>

                        <div>
                            <InputLabel for="estado" value="Estado *" />
                            <select
                                id="estado"
                                v-model="form.estado"
                                class="mt-1 block w-full rounded-md border-slate-700 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 text-sm"
                            >
                                <option value="activo">Activo</option>
                                <option value="pausado">Pausado</option>
                                <option value="completado">Completado</option>
                                <option value="archivado">Archivado</option>
                            </select>
                            <InputError :message="form.errors.estado" class="mt-1" />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <InputLabel for="fecha_inicio" value="Fecha de inicio *" />
                                <TextInput id="fecha_inicio" type="date" v-model="form.fecha_inicio" class="mt-1 block w-full" />
                                <InputError :message="form.errors.fecha_inicio" class="mt-1" />
                            </div>
                            <div>
                                <InputLabel for="fecha_fin_estimada" value="Fecha fin estimada" />
                                <TextInput id="fecha_fin_estimada" type="date" v-model="form.fecha_fin_estimada" class="mt-1 block w-full" />
                                <InputError :message="form.errors.fecha_fin_estimada" class="mt-1" />
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-2">
                            <Link :href="route('proyectos.index')" class="text-sm text-slate-500 hover:text-slate-300">
                                Cancelar
                            </Link>
                            <PrimaryButton :disabled="form.processing">
                                Crear proyecto
                            </PrimaryButton>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
