<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReportePublicoRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Formulario público — sin autenticación
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre_reportante' => ['required', 'string', 'max:150'],
            'email_reportante'  => ['nullable', 'email', 'max:150'],
            'proyecto_id'       => ['required', 'integer', 'exists:proyectos,id,estado,activo'],
            'titulo'            => ['required', 'string', 'max:255'],
            'descripcion'       => ['required', 'string'],
            'modulo'            => ['nullable', 'string', 'max:100'],
            'pasos_reproducir'  => ['nullable', 'string'],
            'comportamiento_esperado' => ['nullable', 'string'],
            'comportamiento_actual'   => ['nullable', 'string'],
            // Hasta 5 imágenes, máximo 5 MB cada una
            'adjuntos'          => ['nullable', 'array', 'max:5'],
            'adjuntos.*'        => ['file', 'mimes:jpg,jpeg,png,gif,webp,pdf', 'max:5120'],
        ];
    }

    public function attributes(): array
    {
        return [
            'nombre_reportante'       => 'nombre',
            'email_reportante'        => 'correo electrónico',
            'proyecto_id'             => 'sistema',
            'titulo'                  => 'título',
            'descripcion'             => 'descripción',
            'modulo'                  => 'módulo',
            'pasos_reproducir'        => 'pasos para reproducir',
            'comportamiento_esperado' => 'comportamiento esperado',
            'comportamiento_actual'   => 'comportamiento actual',
            'adjuntos'                => 'archivos adjuntos',
            'adjuntos.*'              => 'archivo adjunto',
        ];
    }
}
