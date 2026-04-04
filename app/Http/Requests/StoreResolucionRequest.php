<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreResolucionRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Solo quien puede cambiar el bug a 'resuelto' puede registrar la resolución
        $bug = $this->route('bug');
        return Gate::check('cambiarEstado', [$bug, 'resuelto']);
    }

    public function rules(): array
    {
        return [
            'tipo'                => ['required', 'in:correccion,no_reproducible,duplicado,disenio,rechazado'],
            'causa_raiz'          => ['required', 'string', 'max:5000'],
            'solucion_aplicada'   => ['required', 'string', 'max:5000'],
            'archivos_modificados'=> ['nullable', 'array'],
            'archivos_modificados.*' => ['string', 'max:500'],
            'commit_ref'          => ['nullable', 'string', 'max:100'],
            'requiere_deploy'     => ['boolean'],
            'notas_qa'            => ['nullable', 'string', 'max:3000'],
            'prevencion_futura'   => ['nullable', 'string', 'max:3000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'tipo'              => 'tipo de resolución',
            'causa_raiz'        => 'causa raíz',
            'solucion_aplicada' => 'solución aplicada',
            'commit_ref'        => 'referencia de commit',
            'requiere_deploy'   => 'requiere deploy',
            'notas_qa'          => 'notas para QA',
            'prevencion_futura' => 'prevención futura',
        ];
    }
}
