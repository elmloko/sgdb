<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AsignarUsuarioProyectoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->rol_global === 'admin';
    }

    public function rules(): array
    {
        return [
            'user_id'      => ['required', 'exists:users,id'],
            'rol_proyecto' => ['required', 'in:desarrollador,qa,reportante'],
        ];
    }

    public function attributes(): array
    {
        return [
            'user_id'      => 'usuario',
            'rol_proyecto' => 'rol en el proyecto',
        ];
    }
}
