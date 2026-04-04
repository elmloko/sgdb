<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProyectoRequest extends FormRequest
{
    public function authorize(): bool
    {
        // La autorización fina se maneja en el controlador con la Policy
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre'              => ['required', 'string', 'max:255'],
            'descripcion'         => ['nullable', 'string'],
            'estado'              => ['required', 'in:activo,pausado,completado,archivado'],
            'fecha_inicio'        => ['required', 'date'],
            'fecha_fin_estimada'  => ['nullable', 'date', 'after_or_equal:fecha_inicio'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required'             => 'El nombre del proyecto es obligatorio.',
            'nombre.max'                  => 'El nombre no puede superar los 255 caracteres.',
            'estado.required'             => 'El estado del proyecto es obligatorio.',
            'estado.in'                   => 'El estado debe ser: activo, pausado, completado o archivado.',
            'fecha_inicio.required'       => 'La fecha de inicio es obligatoria.',
            'fecha_inicio.date'           => 'La fecha de inicio no tiene un formato válido.',
            'fecha_fin_estimada.date'     => 'La fecha de fin no tiene un formato válido.',
            'fecha_fin_estimada.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la de inicio.',
        ];
    }
}
