<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class CambiarEstadoBugRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Comprobación básica: el usuario puede ver el bug
        return Gate::check('view', $this->route('bug'));
    }

    public function rules(): array
    {
        return [
            'nuevo_estado' => ['required', 'string', 'in:en_revision,asignado,en_desarrollo,en_qa,resuelto,cerrado,rechazado,reabierto'],
            'comentario'   => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'nuevo_estado' => 'nuevo estado',
            'comentario'   => 'comentario',
        ];
    }
}
