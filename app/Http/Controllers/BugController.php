<?php

namespace App\Http\Controllers;

use App\Exceptions\TransicionInvalidaException;
use App\Http\Requests\CambiarEstadoBugRequest;
use App\Http\Requests\StoreBugRequest;
use App\Http\Requests\StoreResolucionRequest;
use App\Models\Bug;
use App\Models\BugAdjunto;
use App\Models\Proyecto;
use App\Services\BugService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BugController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Bug::class);

        $user  = $request->user();
        $query = Bug::with([
            'proyecto:id,nombre',
            'reportadoPor:id,name',
            'asignadoA:id,name',
        ]);

        // Visibilidad según rol
        if ($user->rol_global === 'reportante') {
            $query->where('reportado_por', $user->id);
        } elseif ($user->rol_global === 'qa') {
            // QA solo ve bugs en estados de su competencia dentro de sus proyectos
            $proyectosIds = $user->proyectos()->pluck('proyectos.id');
            $query->whereIn('proyecto_id', $proyectosIds)
                  ->whereIn('estado', ['en_qa', 'resuelto', 'reabierto']);
        } elseif ($user->rol_global !== 'admin') {
            // Desarrollador solo ve bugs de sus proyectos
            $proyectosIds = $user->proyectos()->pluck('proyectos.id');
            $query->whereIn('proyecto_id', $proyectosIds);
        }

        // Filtros opcionales
        if ($estado = $request->input('estado')) {
            $query->where('estado', $estado);
        }
        if ($prioridad = $request->input('prioridad')) {
            $query->where('prioridad', $prioridad);
        }
        if ($proyectoId = $request->input('proyecto_id')) {
            $query->where('proyecto_id', $proyectoId);
        }

        $bugs = $query->orderByRaw("
            CASE prioridad
                WHEN 'critica'     THEN 1
                WHEN 'alta'        THEN 2
                WHEN 'media'       THEN 3
                WHEN 'baja'        THEN 4
            END
        ")->orderBy('created_at', 'desc')
          ->paginate(25)
          ->withQueryString();

        // Proyectos disponibles para el filtro
        if ($user->rol_global === 'admin') {
            $proyectos = Proyecto::select('id', 'nombre')->orderBy('nombre')->get();
        } else {
            $proyectos = $user->proyectos()
                              ->select('proyectos.id', 'proyectos.nombre')
                              ->orderBy('nombre')
                              ->get();
        }

        return Inertia::render('Bugs/Index', [
            'bugs'      => $bugs,
            'proyectos' => $proyectos,
            'filtros'   => $request->only(['estado', 'prioridad', 'proyecto_id']),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Bug::class);

        $user = auth()->user();

        if ($user->rol_global === 'admin') {
            $proyectos = Proyecto::where('estado', 'activo')
                                 ->select('id', 'nombre')
                                 ->orderBy('nombre')
                                 ->get();
        } else {
            $proyectos = $user->proyectos()
                              ->where('proyectos.estado', 'activo')
                              ->select('proyectos.id', 'proyectos.nombre')
                              ->orderBy('nombre')
                              ->get();
        }

        return Inertia::render('Bugs/Create', [
            'proyectos' => $proyectos,
        ]);
    }

    public function store(StoreBugRequest $request)
    {
        $data = $request->safe()->except('adjuntos');
        $data['reportado_por'] = $request->user()->id;
        $data['estado']        = 'nuevo';

        $bug = Bug::create($data);

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

        return redirect()->route('bugs.show', $bug)
            ->with('success', "Bug {$bug->ticket_num} creado correctamente.");
    }

    public function show(Bug $bug, BugService $bugService): Response
    {
        $this->authorize('view', $bug);

        $bug->load([
            'proyecto:id,nombre',
            'reportadoPor:id,name',
            'asignadoA:id,name',
            'resolucion.resueltoPor:id,name',
            'historial' => fn ($q) => $q->with('usuario:id,name')
                                        ->orderBy('created_at', 'asc'),
        ]);

        return Inertia::render('Bugs/Show', [
            'bug'                    => $bug,
            'transicionesDisponibles' => $bugService->transicionesDisponibles($bug, auth()->user()),
        ]);
    }

    /**
     * Cambia el estado del bug.
     * Ruta: PATCH /bugs/{bug}/estado
     */
    public function cambiarEstado(CambiarEstadoBugRequest $request, Bug $bug, BugService $bugService)
    {
        try {
            $bugService->cambiarEstado(
                $bug,
                $request->nuevo_estado,
                $request->user(),
                $request->comentario ?: null,
            );
        } catch (TransicionInvalidaException $e) {
            return back()->withErrors(['nuevo_estado' => $e->getMessage()]);
        } catch (AuthorizationException) {
            return back()->withErrors(['nuevo_estado' => 'No tienes permiso para realizar esta transición.']);
        }

        return redirect()->route('bugs.show', $bug)
            ->with('success', 'Estado actualizado correctamente.');
    }

    /**
     * Guarda la resolución del bug y lo pasa al estado "resuelto".
     * Ruta: POST /bugs/{bug}/resolucion
     */
    public function storeResolucion(StoreResolucionRequest $request, Bug $bug, BugService $bugService)
    {
        if ($bug->resolucion) {
            return back()->withErrors(['resolucion' => 'Este bug ya tiene una resolución registrada.']);
        }

        try {
            $bugService->registrarResolucion($bug, $request->validated(), $request->user());
        } catch (TransicionInvalidaException $e) {
            return back()->withErrors(['estado' => $e->getMessage()]);
        } catch (AuthorizationException) {
            return back()->withErrors(['estado' => 'No tienes permiso para resolver este bug.']);
        }

        return redirect()->route('bugs.show', $bug)
            ->with('success', 'Resolución registrada. Bug marcado como Resuelto.');
    }
}
