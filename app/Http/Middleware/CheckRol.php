<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRol
{
    /**
     * Handle an incoming request.
     *
     * Uso en rutas:
     *   ->middleware('check.rol:admin')
     *   ->middleware('check.rol:admin,desarrollador')
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!$request->user()) {
            abort(401, 'No autenticado.');
        }

        $rolUsuario = $request->user()->rol_global;

        if (!in_array($rolUsuario, $roles)) {
            abort(403, 'No tienes permisos para acceder a este recurso.');
        }

        return $next($request);
    }
}
