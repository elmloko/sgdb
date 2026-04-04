<?php

namespace App\Console\Commands;

use App\Models\Bug;
use App\Models\Notificacion;
use App\Models\User;
use App\Notifications\SlaProximoVencer;
use App\Notifications\SlaVencido;
use Illuminate\Console\Command;

class VerificarSLAs extends Command
{
    protected $signature   = 'sla:verificar';
    protected $description = 'Verifica SLAs de bugs activos y envía alertas de proximidad o vencimiento.';

    // Umbral en minutos para considerar un SLA "próximo a vencer"
    private const UMBRAL_ALERTA_MINUTOS = 60;

    public function handle(): int
    {
        $ahora = now();

        // Bugs activos con SLA definido (excluye cerrados y rechazados)
        $bugs = Bug::whereNotIn('estado', ['cerrado', 'rechazado'])
            ->whereNotNull('sla_vence_en')
            ->with(['asignadoA:id,name,email,rol_global'])
            ->get();

        if ($bugs->isEmpty()) {
            $this->info('No hay bugs activos con SLA definido.');
            return self::SUCCESS;
        }

        $this->line("Revisando {$bugs->count()} bug(s) con SLA...");

        $enviados  = 0;
        $omitidos  = 0;

        // Cargamos los admins una sola vez para evitar N+1 en el loop
        $admins = User::where('rol_global', 'admin')
            ->where('activo', true)
            ->get(['id', 'name', 'email', 'rol_global']);

        foreach ($bugs as $bug) {
            $slaVence = $bug->sla_vence_en;

            if ($slaVence->isPast()) {
                // ── SLA ya venció ──────────────────────────────────────────
                $resultado = $this->procesarSlaVencido($bug, $admins, $ahora);
            } elseif ($ahora->diffInMinutes($slaVence) <= self::UMBRAL_ALERTA_MINUTOS) {
                // ── SLA próximo a vencer (< 60 min) ────────────────────────
                $resultado = $this->procesarSlaProximo($bug, $ahora);
            } else {
                // SLA todavía con margen, nada que hacer
                continue;
            }

            $enviados += $resultado['enviados'];
            $omitidos += $resultado['omitidos'];
        }

        $this->info("Revisión completada: {$enviados} alerta(s) enviada(s), {$omitidos} duplicado(s) omitido(s).");

        return self::SUCCESS;
    }

    // -------------------------------------------------------------------------

    /**
     * Procesa la alerta de "próximo a vencer" para un bug.
     * Solo se envía UNA vez dentro de la ventana de 60 minutos previos al vencimiento.
     * Criterio de deduplicación: notificación sla_vence para este bug/usuario
     * con created_at ENTRE (sla_vence_en - 60 min) Y sla_vence_en.
     */
    private function procesarSlaProximo(Bug $bug, $ahora): array
    {
        $enviados = 0;
        $omitidos = 0;

        if (!$bug->asignadoA) {
            $this->warn("  [{$bug->ticket_num}] Sin desarrollador asignado — no se puede notificar SLA próximo.");
            return compact('enviados', 'omitidos');
        }

        $ventanaDesde = $bug->sla_vence_en->copy()->subMinutes(self::UMBRAL_ALERTA_MINUTOS);

        $yaNotificado = Notificacion::where('bug_id', $bug->id)
            ->where('user_id', $bug->asignadoA->id)
            ->where('tipo', 'sla_vence')
            ->whereBetween('created_at', [$ventanaDesde, $bug->sla_vence_en])
            ->exists();

        if ($yaNotificado) {
            $this->line("  [{$bug->ticket_num}] Alerta 'próximo' ya enviada — omitida.");
            $omitidos++;
        } else {
            $bug->asignadoA->notify(new SlaProximoVencer($bug));
            $minutos = (int) $ahora->diffInMinutes($bug->sla_vence_en);
            $this->info("  [{$bug->ticket_num}] ⚠ SLA próximo a vencer ({$minutos} min) → {$bug->asignadoA->name}");
            $enviados++;
        }

        return compact('enviados', 'omitidos');
    }

    /**
     * Procesa la alerta de "vencido" para un bug.
     * Solo se envía UNA vez por destinatario después de que el SLA expiró.
     * Criterio de deduplicación: notificación sla_vence para este bug/usuario
     * con created_at >= sla_vence_en (es decir, enviada después del vencimiento).
     */
    private function procesarSlaVencido(Bug $bug, $admins, $ahora): array
    {
        $enviados = 0;
        $omitidos = 0;

        // Recopilar todos los destinatarios: el asignado + todos los admins
        $destinatarios = collect();

        if ($bug->asignadoA) {
            $destinatarios->push($bug->asignadoA);
        } else {
            $this->warn("  [{$bug->ticket_num}] Sin desarrollador asignado — solo se notificará a admins.");
        }

        // Agregar admins que no sean ya el asignado
        foreach ($admins as $admin) {
            if (!$bug->asignadoA || $admin->id !== $bug->asignadoA->id) {
                $destinatarios->push($admin);
            }
        }

        if ($destinatarios->isEmpty()) {
            $this->warn("  [{$bug->ticket_num}] Sin destinatarios disponibles para alerta de SLA vencido.");
            return compact('enviados', 'omitidos');
        }

        foreach ($destinatarios as $destinatario) {
            $yaNotificado = Notificacion::where('bug_id', $bug->id)
                ->where('user_id', $destinatario->id)
                ->where('tipo', 'sla_vence')
                ->where('created_at', '>=', $bug->sla_vence_en)
                ->exists();

            if ($yaNotificado) {
                $this->line("  [{$bug->ticket_num}] Alerta 'vencido' ya enviada a {$destinatario->name} — omitida.");
                $omitidos++;
            } else {
                $destinatario->notify(new SlaVencido($bug));
                $minutosVencido = (int) $bug->sla_vence_en->diffInMinutes($ahora);
                $this->warn("  [{$bug->ticket_num}] 🔴 SLA VENCIDO (hace {$minutosVencido} min) → {$destinatario->name}");
                $enviados++;
            }
        }

        return compact('enviados', 'omitidos');
    }
}
