<?php

namespace App\Policies;

use App\Models\Bug;
use App\Models\User;

class BugPolicy
{
    /**
     * El admin puede hacer todo — se evalúa antes de cualquier método.
     */
    public function before(User $user, string $ability): bool|null
    {
        if ($user->rol_global === 'admin') {
            return true;
        }

        return null;
    }

    // -----------------------------------------------------------------------
    // Helpers privados
    // -----------------------------------------------------------------------

    /**
     * Devuelve true si el usuario está asignado al proyecto del bug.
     */
    private function esMiembroDelProyecto(User $user, Bug $bug): bool
    {
        return $bug->proyecto
                   ->usuarios()
                   ->where('user_id', $user->id)
                   ->exists();
    }

    /**
     * Devuelve el rol del usuario en el proyecto del bug, o null si no está.
     */
    private function rolEnProyecto(User $user, Bug $bug): ?string
    {
        $miembro = $bug->proyecto
                       ->usuarios()
                       ->where('user_id', $user->id)
                       ->first();

        return $miembro?->pivot->rol_proyecto;
    }

    // -----------------------------------------------------------------------
    // Permisos CRUD estándar
    // -----------------------------------------------------------------------

    /**
     * Ver el listado de bugs.
     * Todos los roles pueden ver listados (filtrados en el controlador).
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Ver el detalle de un bug.
     * - Reportante: solo sus propios bugs.
     * - Desarrollador / QA: solo bugs de proyectos donde están asignados.
     */
    public function view(User $user, Bug $bug): bool
    {
        if ($user->rol_global === 'reportante') {
            return $bug->reportado_por === $user->id;
        }

        return $this->esMiembroDelProyecto($user, $bug);
    }

    /**
     * Crear un bug.
     * - Desarrollador, QA y Reportante pueden crear bugs.
     */
    public function create(User $user): bool
    {
        return in_array($user->rol_global, ['desarrollador', 'qa', 'reportante']);
    }

    /**
     * Editar/actualizar un bug (campos generales: título, descripción, módulo, etc.).
     * - Desarrollador: solo en proyectos donde tiene rol 'desarrollador'.
     * - QA y Reportante no pueden editar campos generales.
     */
    public function update(User $user, Bug $bug): bool
    {
        if ($user->rol_global === 'desarrollador') {
            return $this->rolEnProyecto($user, $bug) === 'desarrollador';
        }

        return false;
    }

    /**
     * Eliminar un bug.
     * Solo el admin puede eliminar (before() lo maneja).
     */
    public function delete(User $user, Bug $bug): bool
    {
        return false;
    }

    // -----------------------------------------------------------------------
    // Permisos de negocio específicos
    // -----------------------------------------------------------------------

    /**
     * Cambiar el estado de un bug.
     *
     * Transiciones permitidas por rol:
     * - Desarrollador: puede avanzar el estado dentro de su flujo
     *   (en_revision → asignado, asignado → en_desarrollo, en_desarrollo → en_qa).
     * - QA: solo puede cambiar a 'en_qa' o 'reabierto'.
     * - Reportante: solo puede confirmar resolución (resuelto → cerrado).
     *
     * Uso: $this->authorize('cambiarEstado', [$bug, $nuevoEstado])
     */
    public function cambiarEstado(User $user, Bug $bug, string $nuevoEstado): bool
    {
        return match ($user->rol_global) {

            'desarrollador' => $this->rolEnProyecto($user, $bug) === 'desarrollador'
                               && in_array($nuevoEstado, [
                                   'en_revision',
                                   'asignado',
                                   'en_desarrollo',
                                   'en_qa',
                                   'rechazado',
                               ]),

            'qa'            => $this->esMiembroDelProyecto($user, $bug)
                               && in_array($nuevoEstado, ['en_qa', 'reabierto', 'resuelto']),

            'reportante'    => $bug->reportado_por === $user->id
                               && $nuevoEstado === 'cerrado'
                               && $bug->estado === 'resuelto',

            default         => false,
        };
    }

    /**
     * Asignar un bug a un desarrollador.
     * Solo el desarrollador líder del proyecto puede asignar.
     */
    public function asignar(User $user, Bug $bug): bool
    {
        return $this->rolEnProyecto($user, $bug) === 'desarrollador';
    }

    /**
     * Registrar una resolución en el bug.
     * Solo el desarrollador asignado al bug puede resolver.
     */
    public function resolver(User $user, Bug $bug): bool
    {
        return $user->rol_global === 'desarrollador'
               && $bug->asignado_a === $user->id;
    }
}
