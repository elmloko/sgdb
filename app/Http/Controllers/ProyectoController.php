<?php

namespace App\Http\Controllers;

use App\Http\Requests\AsignarUsuarioProyectoRequest;
use App\Http\Requests\StoreProyectoRequest;
use App\Models\Proyecto;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class ProyectoController extends Controller
{
    public function index(): Response
    {
        $user      = auth()->user();
        $isAdmin   = $user->rol_global === 'admin';

        $proyectos = $isAdmin
            ? Proyecto::withCount('bugs')->orderBy('estado')->orderBy('nombre')->get()
            : $user->proyectos()->withCount('bugs')->orderBy('estado')->orderBy('nombre')->get();

        return Inertia::render('Proyectos/Index', [
            'proyectos' => $proyectos,
            'esAdmin'   => $isAdmin,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Proyecto::class);

        return Inertia::render('Proyectos/Create');
    }

    public function store(StoreProyectoRequest $request)
    {
        $this->authorize('create', Proyecto::class);

        $proyecto = Proyecto::create([
            ...$request->validated(),
            'creado_por' => $request->user()->id,
        ]);

        return redirect()->route('proyectos.show', $proyecto)
            ->with('success', "Proyecto \"{$proyecto->nombre}\" creado correctamente.");
    }

    public function edit(Proyecto $proyecto): Response
    {
        $this->authorize('update', $proyecto);

        return Inertia::render('Proyectos/Edit', [
            'proyecto' => $proyecto,
        ]);
    }

    public function update(StoreProyectoRequest $request, Proyecto $proyecto)
    {
        $this->authorize('update', $proyecto);

        $proyecto->update($request->validated());

        return redirect()->route('proyectos.show', $proyecto)
            ->with('success', 'Proyecto actualizado correctamente.');
    }

    public function show(Proyecto $proyecto): Response
    {
        $proyecto->load(['usuarios:id,name,email,rol_global', 'creadoPor:id,name']);

        // Bugs del proyecto con sus relaciones
        $bugs = $proyecto->bugs()
            ->with(['asignadoA:id,name', 'reportadoPor:id,name'])
            ->orderByRaw("CASE prioridad WHEN 'critica' THEN 1 WHEN 'alta' THEN 2 WHEN 'media' THEN 3 ELSE 4 END")
            ->orderBy('created_at', 'desc')
            ->get(['id','ticket_num','titulo','prioridad','estado','asignado_a','reportado_por','sla_vence_en','created_at','cerrado_en']);

        // Resoluciones del proyecto
        $resoluciones = $proyecto->bugs()
            ->join('resoluciones', 'bugs.id', '=', 'resoluciones.bug_id')
            ->join('users', 'resoluciones.resuelto_por', '=', 'users.id')
            ->select(
                'bugs.ticket_num',
                'bugs.titulo',
                'resoluciones.tipo',
                'resoluciones.commit_ref',
                'resoluciones.requiere_deploy',
                'resoluciones.created_at as resuelto_en',
                'users.name as resuelto_por'
            )
            ->orderByDesc('resoluciones.created_at')
            ->get();

        // Stats rápidas
        $stats = [
            'total'         => $bugs->count(),
            'abiertos'      => $bugs->whereNotIn('estado', ['cerrado','rechazado'])->count(),
            'cerrados'      => $bugs->where('estado', 'cerrado')->count(),
            'criticos'      => $bugs->where('prioridad', 'critica')->whereNotIn('estado', ['cerrado','rechazado'])->count(),
            'sin_asignar'   => $bugs->whereNull('asignado_a')->whereNotIn('estado', ['cerrado','rechazado'])->count(),
        ];

        $usuariosDisponibles = User::where('activo', true)
            ->select('id', 'name', 'email', 'rol_global')
            ->orderBy('name')
            ->get();

        return Inertia::render('Proyectos/Show', [
            'proyecto'            => $proyecto,
            'bugs'                => $bugs,
            'resoluciones'        => $resoluciones,
            'stats'               => $stats,
            'usuariosDisponibles' => $usuariosDisponibles,
        ]);
    }

    /**
     * Cambia el estado del proyecto (archivar, pausar, activar, etc.).
     */
    public function cambiarEstado(Proyecto $proyecto)
    {
        $this->authorize('update', $proyecto);

        $nuevoEstado = request()->validate([
            'estado' => ['required', 'in:activo,pausado,completado,archivado'],
        ])['estado'];

        $proyecto->update(['estado' => $nuevoEstado]);

        $etiquetas = [
            'activo'     => 'activado',
            'pausado'    => 'pausado',
            'completado' => 'marcado como completado',
            'archivado'  => 'archivado',
        ];

        return redirect()->route('proyectos.show', $proyecto)
            ->with('success', "Proyecto \"{$proyecto->nombre}\" {$etiquetas[$nuevoEstado]} correctamente.");
    }

    /**
     * Asigna un usuario al proyecto con un rol específico.
     * Si ya existe, actualiza su rol.
     */
    public function asignarUsuario(AsignarUsuarioProyectoRequest $request, Proyecto $proyecto)
    {
        $data = $request->validated();

        if ($proyecto->usuarios()->where('users.id', $data['user_id'])->exists()) {
            // Actualizar el rol si ya es miembro
            $proyecto->usuarios()->updateExistingPivot($data['user_id'], [
                'rol_proyecto' => $data['rol_proyecto'],
            ]);
        } else {
            $proyecto->usuarios()->attach($data['user_id'], [
                'rol_proyecto' => $data['rol_proyecto'],
            ]);
        }

        return redirect()->route('proyectos.show', $proyecto)
            ->with('success', 'Usuario asignado al proyecto correctamente.');
    }

    /**
     * Quita un usuario del proyecto.
     */
    public function quitarUsuario(Proyecto $proyecto, User $usuario)
    {
        // Solo admin puede hacer esto (verificado en ruta con middleware)
        $proyecto->usuarios()->detach($usuario->id);

        return redirect()->route('proyectos.show', $proyecto)
            ->with('success', 'Usuario removido del proyecto.');
    }
}
