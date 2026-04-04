<?php

namespace App\Services;

use App\Exceptions\TransicionInvalidaException;
use App\Models\Bug;
use App\Models\BugHistorial;
use App\Models\Resolucion;
use App\Models\User;
use App\Notifications\BugResuelto;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class BugService
{
    /**
     * Mapa canónico de transiciones permitidas por el sistema (estado actual → estados siguientes).
     * Fuente: CLAUDE.md — "Estados del Bug y Transiciones Permitidas".
     */
    private const TRANSICIONES = [
        'nuevo'         => ['en_revision', 'rechazado'],
        'en_revision'   => ['asignado', 'rechazado'],
        'asignado'      => ['en_desarrollo', 'rechazado'],
        'en_desarrollo' => ['en_qa'],
        'en_qa'         => ['resuelto', 'reabierto'],
        'resuelto'      => ['cerrado', 'reabierto'],
        'reabierto'     => ['en_revision', 'asignado'],
        // Estados terminales sin transición
        'cerrado'   => [],
        'rechazado' => [],
    ];

    /**
     * Etiquetas legibles de cada estado (usadas en los mensajes de error).
     */
    private const ETIQUETAS = [
        'nuevo'         => 'Nuevo',
        'en_revision'   => 'En Revisión',
        'asignado'      => 'Asignado',
        'en_desarrollo' => 'En Desarrollo',
        'en_qa'         => 'En QA',
        'resuelto'      => 'Resuelto',
        'cerrado'       => 'Cerrado',
        'rechazado'     => 'Rechazado',
        'reabierto'     => 'Reabierto',
    ];

    // -------------------------------------------------------------------------

    /**
     * Cambia el estado de un bug, validando la transición y el permiso del usuario.
     *
     * El historial se registra automáticamente vía BugObserver::updated(),
     * que lee $bug->comentarioCambioEstado para incluir el comentario.
     *
     * @throws TransicionInvalidaException si la transición no está en el mapa.
     * @throws \Illuminate\Auth\Access\AuthorizationException si el usuario no tiene permiso.
     */
    public function cambiarEstado(
        Bug $bug,
        string $nuevoEstado,
        User $usuario,
        ?string $comentario = null
    ): void {
        // 1. Validar que la transición existe en el mapa del sistema
        $this->validarTransicion($bug->estado, $nuevoEstado);

        // 2. Validar que el usuario tiene permiso para esta transición (via Policy)
        Gate::forUser($usuario)->authorize('cambiarEstado', [$bug, $nuevoEstado]);

        // 3. Pasar el comentario al modelo para que el Observer lo incluya en el historial
        $bug->comentarioCambioEstado = $comentario;

        // 4. Actualizar estado
        $bug->estado = $nuevoEstado;

        // 5. Si se cierra, registrar timestamp
        if ($nuevoEstado === 'cerrado') {
            $bug->cerrado_en = now();
        }

        $bug->save();
    }

    /**
     * Devuelve los estados a los que este usuario puede transicionar el bug.
     * Combina el mapa del sistema con los permisos de la Policy.
     *
     * @return array<string>  Lista de estados siguientes permitidos para este usuario.
     */
    public function transicionesDisponibles(Bug $bug, User $usuario): array
    {
        $siguientesValidos = self::TRANSICIONES[$bug->estado] ?? [];

        return array_values(array_filter(
            $siguientesValidos,
            fn (string $estado) => Gate::forUser($usuario)->check('cambiarEstado', [$bug, $estado])
        ));
    }

    // -------------------------------------------------------------------------

    /**
     * Registra la resolución de un bug y lo pasa al estado "resuelto".
     * Ejecuta todo en una transacción atómica.
     *
     * @throws TransicionInvalidaException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function registrarResolucion(Bug $bug, array $datos, User $usuario): void
    {
        DB::transaction(function () use ($bug, $datos, $usuario) {
            // 1. Crear la resolución
            $archivos = array_values(array_filter(
                $datos['archivos_modificados'] ?? [],
                fn ($v) => trim($v) !== ''
            ));

            Resolucion::create([
                'bug_id'               => $bug->id,
                'tipo'                 => $datos['tipo'],
                'causa_raiz'           => $datos['causa_raiz'],
                'solucion_aplicada'    => $datos['solucion_aplicada'],
                'archivos_modificados' => $archivos ?: null,
                'commit_ref'           => $datos['commit_ref'] ?: null,
                'requiere_deploy'      => (bool) ($datos['requiere_deploy'] ?? false),
                'notas_qa'             => $datos['notas_qa'] ?: null,
                'prevencion_futura'    => $datos['prevencion_futura'] ?: null,
                'resuelto_por'         => $usuario->id,
            ]);

            // 2. Registrar entrada de tipo 'resolucion' en el historial
            BugHistorial::create([
                'bug_id'      => $bug->id,
                'user_id'     => $usuario->id,
                'accion'      => 'resolucion',
                'valor_nuevo' => $datos['tipo'],
                'comentario'  => $datos['solucion_aplicada'],
            ]);

            // 3. Cambiar estado a 'resuelto' (el Observer registra cambio_estado)
            $this->cambiarEstado($bug, 'resuelto', $usuario);

            // 4. Notificar al reportante original (si es distinto al resolutor)
            $bug->loadMissing('reportadoPor');
            $reportante = $bug->reportadoPor;
            if ($reportante && $reportante->id !== $usuario->id) {
                $reportante->notify(new BugResuelto($bug));
            }
        });
    }

    // -------------------------------------------------------------------------

    /**
     * Lanza TransicionInvalidaException si el paso de $desde → $hasta
     * no está contemplado en el mapa de transiciones.
     */
    private function validarTransicion(string $desde, string $hasta): void
    {
        $permitidos = self::TRANSICIONES[$desde] ?? [];

        if (!in_array($hasta, $permitidos, true)) {
            throw new TransicionInvalidaException($desde, $hasta);
        }
    }
}
