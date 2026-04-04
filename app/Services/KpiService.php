<?php

namespace App\Services;

use App\Models\Bug;
use App\Models\Proyecto;
use App\Models\Resolucion;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class KpiService
{
    /**
     * Bugs críticos activos (no cerrados ni rechazados).
     */
    public function bugsCriticosActivos(): int
    {
        return Bug::where('prioridad', 'critica')
            ->whereNotIn('estado', ['cerrado', 'rechazado'])
            ->count();
    }

    /**
     * Total de bugs activos (no cerrados ni rechazados).
     */
    public function totalBugsActivos(): int
    {
        return Bug::whereNotIn('estado', ['cerrado', 'rechazado'])->count();
    }

    /**
     * Tasa de resolución del mes actual:
     * % de bugs cerrados en el mes sobre el total creado en el mismo mes.
     */
    public function tasaResolucionMes(): float
    {
        $creadosMes = Bug::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        if ($creadosMes === 0) {
            return 0.0;
        }

        $cerradosMes = Bug::whereIn('estado', ['cerrado', 'resuelto'])
            ->whereMonth('cerrado_en', now()->month)
            ->whereYear('cerrado_en', now()->year)
            ->count();

        return round(($cerradosMes / $creadosMes) * 100, 1);
    }

    /**
     * Tiempo promedio de resolución en horas
     * (desde created_at hasta cerrado_en, solo bugs cerrados).
     */
    public function tiempoPromedioResolucionHoras(): float
    {
        $promedio = Bug::where('estado', 'cerrado')
            ->whereNotNull('cerrado_en')
            ->select(DB::raw('AVG(EXTRACT(EPOCH FROM (cerrado_en - created_at)) / 3600) as promedio'))
            ->value('promedio');

        return round((float) $promedio, 1);
    }

    /**
     * Carga por desarrollador:
     * nombre, bugs asignados activos, bugs resueltos esta semana.
     */
    public function cargaPorDesarrollador(): array
    {
        $devs = User::where('activo', true)
            ->whereIn('rol_global', ['desarrollador', 'admin'])
            ->get(['id', 'name', 'email', 'rol_global']);

        $inicioSemana = now()->startOfWeek();

        return $devs->map(function (User $dev) use ($inicioSemana) {
            $asignados = Bug::where('asignado_a', $dev->id)
                ->whereNotIn('estado', ['cerrado', 'rechazado'])
                ->count();

            $resueltasSemana = Resolucion::where('resuelto_por', $dev->id)
                ->where('created_at', '>=', $inicioSemana)
                ->count();

            return [
                'id'               => $dev->id,
                'name'             => $dev->name,
                'rol_global'       => $dev->rol_global,
                'bugs_asignados'   => $asignados,
                'resueltos_semana' => $resueltasSemana,
            ];
        })->sortByDesc('bugs_asignados')->values()->all();
    }

    /**
     * Estado de todos los proyectos activos con conteo de bugs por estado.
     */
    public function estadoProyectosActivos(): array
    {
        $proyectos = Proyecto::where('estado', 'activo')
            ->withCount([
                'bugs as bugs_total',
                'bugs as bugs_activos' => fn($q) => $q->whereNotIn('estado', ['cerrado', 'rechazado']),
                'bugs as bugs_criticos' => fn($q) => $q->where('prioridad', 'critica')
                                                        ->whereNotIn('estado', ['cerrado', 'rechazado']),
                'bugs as bugs_cerrados' => fn($q) => $q->where('estado', 'cerrado'),
            ])
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'estado', 'fecha_fin_estimada']);

        return $proyectos->map(fn($p) => [
            'id'                 => $p->id,
            'nombre'             => $p->nombre,
            'estado'             => $p->estado,
            'fecha_fin_estimada' => $p->fecha_fin_estimada?->format('d/m/Y'),
            'bugs_total'         => $p->bugs_total,
            'bugs_activos'       => $p->bugs_activos,
            'bugs_criticos'      => $p->bugs_criticos,
            'bugs_cerrados'      => $p->bugs_cerrados,
        ])->all();
    }

    /**
     * Bugs críticos sin atender:
     * prioridad=critica, estado en nuevo/en_revision/asignado, sin asignar o asignados.
     */
    public function bugsCriticosSinAtender(): array
    {
        return Bug::where('prioridad', 'critica')
            ->whereIn('estado', ['nuevo', 'en_revision', 'asignado'])
            ->with(['proyecto:id,nombre', 'asignadoA:id,name'])
            ->orderBy('created_at')
            ->get(['id', 'ticket_num', 'titulo', 'estado', 'proyecto_id', 'asignado_a', 'sla_vence_en', 'created_at'])
            ->map(fn($bug) => [
                'id'          => $bug->id,
                'ticket_num'  => $bug->ticket_num,
                'titulo'      => $bug->titulo,
                'estado'      => $bug->estado,
                'sla_vence_en'=> $bug->sla_vence_en?->toIso8601String(),
                'created_at'  => $bug->created_at->toIso8601String(),
                'proyecto'    => $bug->proyecto?->nombre,
                'asignado_a'  => $bug->asignadoA?->name,
            ])
            ->all();
    }
}
