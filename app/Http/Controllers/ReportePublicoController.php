<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReportePublicoRequest;
use App\Models\Bug;
use App\Models\BugAdjunto;
use App\Models\Proyecto;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ReportePublicoController extends Controller
{
    /**
     * Muestra el formulario público de reporte de bug.
     */
    public function create(): Response
    {
        $proyectos = Proyecto::where('estado', 'activo')
                             ->select('id', 'nombre')
                             ->orderBy('nombre')
                             ->get();

        return Inertia::render('ReportePublico', [
            'proyectos' => $proyectos,
        ]);
    }

    /**
     * Guarda el bug reportado públicamente (sin autenticación).
     */
    public function store(StoreReportePublicoRequest $request)
    {
        $data = $request->safe()->except('adjuntos');

        $data['estado']    = 'nuevo';
        $data['prioridad'] = 'media'; // prioridad por defecto para reportes públicos
        $data['entorno']   = 'produccion';

        $bug = Bug::create($data);

        // Guardar adjuntos si se enviaron
        if ($request->hasFile('adjuntos')) {
            foreach ($request->file('adjuntos') as $archivo) {
                $ruta = $archivo->store("adjuntos/{$bug->id}", 'public');

                BugAdjunto::create([
                    'bug_id'          => $bug->id,
                    'nombre_original' => $archivo->getClientOriginalName(),
                    'ruta'            => $ruta,
                    'mime_type'       => $archivo->getMimeType(),
                    'tamanio'         => $archivo->getSize(),
                ]);
            }
        }

        return redirect()->route('reporte-publico.gracias', [
            'ticket' => $bug->ticket_num,
        ]);
    }

    /**
     * Pantalla de confirmación tras enviar el reporte.
     */
    public function gracias(): Response
    {
        return Inertia::render('ReportePublicoGracias', [
            'ticket' => request('ticket'),
        ]);
    }
}
