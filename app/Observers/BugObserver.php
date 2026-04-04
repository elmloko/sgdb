<?php

namespace App\Observers;

use App\Models\Bug;
use App\Models\BugHistorial;
use App\Models\User;
use App\Notifications\BugAsignado;
use App\Notifications\BugReabierto;
use Illuminate\Support\Facades\DB;

class BugObserver
{
    /**
     * Se ejecuta antes de persistir un nuevo bug.
     * Genera ticket_num y calcula sla_vence_en.
     */
    public function creating(Bug $bug): void
    {
        $bug->ticket_num = $this->generarTicketNum();

        $bug->sla_vence_en = match ($bug->prioridad) {
            'critica' => now()->addHours(4),
            'alta'    => now()->addHours(8),
            'media'   => now()->addHours(48),
            default   => null,
        };
    }

    /**
     * Se ejecuta después de persistir un nuevo bug.
     * Registra la entrada inicial en el historial.
     */
    public function created(Bug $bug): void
    {
        BugHistorial::create([
            'bug_id'         => $bug->id,
            'user_id'        => $bug->reportado_por,
            'accion'         => 'cambio_estado',
            'valor_anterior' => null,
            'valor_nuevo'    => 'nuevo',
            'comentario'     => 'Bug reportado.',
        ]);
    }

    /**
     * Se ejecuta después de actualizar un bug existente.
     * Registra cambios de estado y reasignaciones, y dispara notificaciones.
     */
    public function updated(Bug $bug): void
    {
        $userId = auth()->id();

        // ── Cambio de estado ────────────────────────────────────────────────
        if ($bug->isDirty('estado')) {
            BugHistorial::create([
                'bug_id'         => $bug->id,
                'user_id'        => $userId,
                'accion'         => 'cambio_estado',
                'valor_anterior' => $bug->getOriginal('estado'),
                'valor_nuevo'    => $bug->estado,
                'comentario'     => $bug->comentarioCambioEstado,
            ]);
            $bug->comentarioCambioEstado = null;

            // Notificar reapertura al desarrollador asignado y a todos los admins
            if ($bug->estado === 'reabierto') {
                $this->notificarReabierto($bug);
            }
        }

        // ── Nueva asignación ────────────────────────────────────────────────
        if ($bug->isDirty('asignado_a')) {
            BugHistorial::create([
                'bug_id'         => $bug->id,
                'user_id'        => $userId,
                'accion'         => 'asignacion',
                'valor_anterior' => (string) $bug->getOriginal('asignado_a'),
                'valor_nuevo'    => (string) $bug->asignado_a,
            ]);

            // Notificar al nuevo asignado (si lo hay y no es él mismo quien lo asigna)
            if ($bug->asignado_a && $bug->asignado_a !== $userId) {
                $asignado = User::find($bug->asignado_a);
                $asignado?->notify(new BugAsignado($bug));
            }
        }
    }

    // -----------------------------------------------------------------------

    private function notificarReabierto(Bug $bug): void
    {
        $motivo = $bug->comentarioCambioEstado ?? '';

        // Notificar al desarrollador asignado (si existe y no es quien reabrió)
        if ($bug->asignado_a && $bug->asignado_a !== auth()->id()) {
            $asignado = User::find($bug->asignado_a);
            $asignado?->notify(new BugReabierto($bug, $motivo));
        }

        // Notificar a todos los admins (excepto al que realizó la acción)
        User::where('rol_global', 'admin')
            ->where('id', '!=', auth()->id())
            ->get()
            ->each(fn (User $admin) => $admin->notify(new BugReabierto($bug, $motivo)));
    }

    // -----------------------------------------------------------------------

    private function generarTicketNum(): string
    {
        $year = now()->year;

        return DB::transaction(function () use ($year) {
            DB::statement('SELECT pg_advisory_xact_lock(hashtext(\'bug_ticket_num\'))');

            $ultimo = Bug::whereRaw("ticket_num LIKE ?", ["BUG-{$year}-%"])
                         ->max('ticket_num');

            $siguiente = $ultimo ? ((int) substr($ultimo, -4)) + 1 : 1;

            return sprintf('BUG-%d-%04d', $year, $siguiente);
        });
    }
}
