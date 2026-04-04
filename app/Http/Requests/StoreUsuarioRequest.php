<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Solo el admin puede crear usuarios
        return $this->user()?->rol_global === 'admin';
    }

    public function rules(): array
    {
        return [
            'name'       => ['required', 'string', 'max:255'],
            'email'      => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password'   => ['required', 'string', 'min:8', 'confirmed'],
            'rol_global' => ['required', 'in:admin,desarrollador,qa,reportante'],
            'activo'     => ['boolean'],
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
