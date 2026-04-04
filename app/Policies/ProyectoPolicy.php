<?php

namespace App\Policies;

use App\Models\Proyecto;
use App\Models\User;

class ProyectoPolicy
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
    // Helper privado
    // -----------------------------------------------------------------------

    /**
     * Devuelve el rol del usuario en el proyecto, o null si no es miembro.
     */
    private function rolEnProyecto(User $user, Proyecto $proyecto): ?string
    {
        $miembro = $proyecto->usuarios()
                            ->where('user_id', $user->id)
                            ->first();

        return $miembro?->pivot->rol_proyecto;
    }

    // -----------------------------------------------------------------------
    // Permisos CRUD
    // -----------------------------------------------------------------------

    /**
     * Ver el listado de proyectos.
     * Todos los roles pueden ver (filtrado en controlador según membresía).
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Ver el detalle de un proyecto.
     * Solo miembros del proyecto (o admin) pueden verlo.
     */
    public function view(User $user, Proyecto $proyecto): bool
    {
        return $this->rolEnProyecto($user, $proyecto) !== null;
    }

    /**
     * Crear un proyecto.
     * Solo el admin puede crear proyectos (before() lo maneja).
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Actualizar datos del proyecto (nombre, fechas, estado, etc.).
     * - Desarrollador: solo proyectos donde tiene rol 'desarrollador'.
     */
    public function update(User $user, Proyecto $proyecto): bool
    {
        return $this->rolEnProyecto($user, $proyecto) === 'desarrollador';
    }

    /**
     * Eliminar un proyecto.
     * Solo el admin puede eliminar (before() lo maneja).
     */
    public function delete(User $user, Proyecto $proyecto): bool
    {
        return false;
    }

    /**
     * Gestionar los miembros del proyecto (agregar/quitar usuarios).
     * - Desarrollador del proyecto puede gestionar su equipo.
     */
    public function gestionarMiembros(User $user, Proyecto $proyecto): bool
    {
        return $this->rolEnProyecto($user, $proyecto) === 'desarrollador';
    }

    /**
     * Ver el dashboard del proyecto (KPIs, burndown, etc.).
     * Cualquier miembro del proyecto puede verlo.
     */
    public function verDashboard(User $user, Proyecto $proyecto): bool
    {
        return $this->rolEnProyecto($user, $proyecto) !== null;
    }

    /**
     * Gestionar sprints del proyecto.
     * Solo el desarrollador líder puede crear/editar sprints.
     */
    public function gestionarSprints(User $user, Proyecto $proyecto): bool
    {
        return $this->rolEnProyecto($user, $proyecto) === 'desarrollador';
    }
}
