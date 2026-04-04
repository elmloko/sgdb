<?php

namespace App\Http\Controllers;

use App\Models\Notificacion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificacionController extends Controller
{
    /**
     * Lista todas las notificaciones del usuario autenticado (paginadas).
     * Ruta: GET /notificaciones
     */
    public function index(Request $request): Response
    {
        $notificaciones = Notificacion::where('user_id', $request->user()->id)
            ->with('bug:id,ticket_num,titulo')
            ->orderByRaw('leido ASC')        // no leídas primero
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Marca todas como leídas al abrir la página
        Notificacion::where('user_id', $request->user()->id)
            ->where('leido', false)
            ->update(['leido' => true]);

        return Inertia::render('Notificaciones/Index', [
            'notificaciones' => $notificaciones,
        ]);
    }

    /**
     * Marca una notificación como leída.
     * Ruta: PATCH /notificaciones/{notificacion}
     */
    public function update(Request $request, Notificacion $notificacion): JsonResponse
    {
        // Solo el dueño puede marcar la notificación
        if ($notificacion->user_id !== $request->user()->id) {
            abort(403);
        }

        $notificacion->update(['leido' => true]);

        return response()->json(['ok' => true]);
    }

    /**
     * Marca todas las notificaciones del usuario como leídas.
     * Ruta: PATCH /notificaciones/leer-todas
     */
    public function marcarTodasLeidas(Request $request): JsonResponse
    {
        Notificacion::where('user_id', $request->user()->id)
            ->where('leido', false)
            ->update(['leido' => true]);

        return response()->json(['ok' => true]);
    }

    /**
     * Devuelve el conteo de notificaciones no leídas (para polling).
     * Ruta: GET /notificaciones/conteo
     */
    public function conteo(Request $request): JsonResponse
    {
        $count = Notificacion::where('user_id', $request->user()->id)
            ->where('leido', false)
            ->count();

        return response()->json(['count' => $count]);
    }

    /**
     * Devuelve las últimas 5 notificaciones (para el dropdown del navbar).
     * Ruta: GET /notificaciones/ultimas
     */
    public function ultimas(Request $request): JsonResponse
    {
        $notificaciones = Notificacion::where('user_id', $request->user()->id)
            ->with('bug:id,ticket_num')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get(['id', 'tipo', 'titulo', 'mensaje', 'leido', 'bug_id', 'created_at']);

        return response()->json($notificaciones);
    }
}
