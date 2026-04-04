<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Solo el admin puede editar usuarios
        return $this->user()?->rol_global === 'admin';
    }

    public function rules(): array
    {
        $userId = $this->route('usuario');

        return [
            'name'       => ['required', 'string', 'max:255'],
            'email'      => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'password'   => ['nullable', 'string', 'min:8', 'confirmed'],
            'rol_global' => ['required', 'in:admin,desarrollador,qa,reportante'],
            'activo'     => ['required', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name'       => 'nombre',
            'email'      => 'correo electrónico',
            'password'   => 'contraseña',
            'rol_global' => 'rol',
            'activo'     => 'estado',
        ];
    }
}
