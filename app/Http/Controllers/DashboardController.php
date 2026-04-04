<?php

namespace App\Http\Controllers;

use App\Models\Bug;
use App\Models\BugHistorial;
use App\Models\Resolucion;
use App\Services\KpiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function admin(KpiService $kpi): Response
    {
        return Inertia::render('Dashboard/Admin', [
            'kpis' => [
                'bugs_criticos_activos'       => $kpi->bugsCriticosActivos(),
                'total_bugs_activos'          => $kpi->totalBugsActivos(),
                'tasa_resolucion_mes'         => $kpi->tasaResolucionMes(),
                'tiempo_promedio_resolucion'  => $kpi->tiempoPromedioResolucionHoras(),
            ],
            'cargaDesarrolladores' => $kpi->cargaPorDesarrollador(),
            'proyectosActivos'     => $kpi->estadoProyectosActivos(),
            'bugsCriticos'         => $kpi->bugsCriticosSinAtender(),
        ]);
    }

    public function desarrollador(Request $request): Response
    {
        $usuario = $request->user();

        // 1. Cola de bugs asignados al usuario, activos, ordenados por prioridad + SLA
        $bugsAsignados = Bug::where('asignado_a', $usuario->id)
            ->whereNotIn('estado', ['cerrado', 'rechazado'])
            ->with(['proyecto:id,nombre'])
            ->orderByRaw("CASE prioridad
                WHEN 'critica' THEN 1
                WHEN 'alta'    THEN 2
                WHEN 'media'   THEN 3
                WHEN 'baja'    THEN 4
                ELSE 5 END")
            ->orderByRaw('sla_vence_en ASC NULLS LAST')
            ->get(['id', 'ticket_num', 'titulo', 'prioridad', 'estado', 'proyecto_id', 'sla_vence_en', 'created_at']);

        // 2. Bugs resueltos este mes por el usuario
        $resueltosMes = Resolucion::where('resuelto_por', $usuario->id)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        // 3. Tasa de reapertura personal
        //    = bugs que el usuario resolvió y que posteriormente fueron reabiertos
        $totalResueltos = Resolucion::where('resuelto_por', $usuario->id)->count();

        $bugsReasignados = 0;
        if ($totalResueltos > 0) {
            // IDs de bugs que este usuario resolvió
            $bugIdsResueltos = Resolucion::where('resuelto_por', $usuario->id)
                ->pluck('bug_id');

            // De esos bugs, cuántos tienen al menos un evento 'reabierto' en historial
            $bugsReasignados = BugHistorial::whereIn('bug_id', $bugIdsResueltos)
                ->where('accion', 'cambio_estado')
                ->where('valor_nuevo', 'reabierto')
                ->distinct('bug_id')
                ->count('bug_id');
        }

        $tasaReapertura = $totalResueltos > 0
            ? round(($bugsReasignados / $totalResueltos) * 100, 1)
            : 0;

        // 4. Bugs sin asignar en proyectos donde participa el usuario
        $proyectosDelUsuario = $usuario->proyectos()->pluck('proyectos.id');

        $bugsSinAsignar = Bug::whereIn('proyecto_id', $proyectosDelUsuario)
            ->whereNull('asignado_a')
            ->whereNotIn('estado', ['cerrado', 'rechazado'])
            ->with(['proyecto:id,nombre'])
            ->orderByRaw("CASE prioridad
                WHEN 'critica' THEN 1
                WHEN 'alta'    THEN 2
                WHEN 'media'   THEN 3
                WHEN 'baja'    THEN 4
                ELSE 5 END")
            ->orderBy('created_at')
            ->get(['id', 'ticket_num', 'titulo', 'prioridad', 'estado', 'proyecto_id', 'sla_vence_en', 'created_at']);

        return Inertia::render('Dashboard/Desarrollador', [
            'bugsAsignados'  => $bugsAsignados,
            'resueltosMes'   => $resueltosMes,
            'totalResueltos' => $totalResueltos,
            'tasaReapertura' => $tasaReapertura,
            'bugsSinAsignar' => $bugsSinAsignar,
        ]);
    }
}
