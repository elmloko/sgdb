<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBugRequest extends FormRequest
{
    public function authorize(): bool
    {
        // La policy BugPolicy::create() verifica el rol
        return $this->user()->can('create', \App\Models\Bug::class);
    }

    public function rules(): array
    {
        return [
            'titulo'                  => ['required', 'string', 'max:255'],
            'descripcion'             => ['required', 'string'],
            'prioridad'               => ['required', 'in:critica,alta,media,baja'],
            'proyecto_id'             => ['required', 'integer', 'exists:proyectos,id'],
            'modulo'                  => ['nullable', 'string', 'max:100'],
            'entorno'                 => ['required', 'in:produccion,staging,desarrollo'],
            'pasos_reproducir'        => ['nullable', 'string'],
            'comportamiento_esperado' => ['nullable', 'string'],
            'comportamiento_actual'   => ['nullable', 'string'],
        ];
    }

    public function attributes(): array
    {
        return [
            'titulo'                  => 'título',
            'descripcion'             => 'descripción',
            'prioridad'               => 'prioridad',
            'proyecto_id'             => 'proyecto',
            'modulo'                  => 'módulo',
            'entorno'                 => 'entorno',
            'pasos_reproducir'        => 'pasos para reproducir',
            'comportamiento_esperado' => 'comportamiento esperado',
            'comportamiento_actual'   => 'comportamiento actual',
        ];
    }
}
