<?php

use App\Http\Controllers\BugController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificacionController;
use App\Http\Controllers\ProyectoController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UsuarioController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin'       => Route::has('login'),
        'canRegister'    => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion'     => PHP_VERSION,
    ]);
});

Route::get('/dashboard', [DashboardController::class, 'admin'])
    ->middleware(['auth', 'verified', 'check.rol:admin'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    // Perfil
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Gestión de usuarios (solo admin)
    Route::middleware('check.rol:admin')->group(function () {
        Route::get('/usuarios', [UsuarioController::class, 'index'])->name('usuarios.index');
        Route::get('/usuarios/crear', [UsuarioController::class, 'create'])->name('usuarios.create');
        Route::post('/usuarios', [UsuarioController::class, 'store'])->name('usuarios.store');
        Route::get('/usuarios/{usuario}/editar', [UsuarioController::class, 'edit'])->name('usuarios.edit');
        Route::put('/usuarios/{usuario}', [UsuarioController::class, 'update'])->name('usuarios.update');
    });

    // Panel del desarrollador (solo desarrolladores y admin)
    Route::get('/mi-panel', [DashboardController::class, 'desarrollador'])
        ->middleware('check.rol:admin,desarrollador')
        ->name('mi-panel');

    // Bugs
    Route::get('/bugs', [BugController::class, 'index'])->name('bugs.index');
    Route::get('/bugs/crear', [BugController::class, 'create'])->name('bugs.create');
    Route::post('/bugs', [BugController::class, 'store'])->name('bugs.store');
    Route::get('/bugs/{bug}', [BugController::class, 'show'])->name('bugs.show');
    Route::patch('/bugs/{bug}/estado', [BugController::class, 'cambiarEstado'])->name('bugs.cambiarEstado');
    Route::post('/bugs/{bug}/resolucion', [BugController::class, 'storeResolucion'])->name('bugs.storeResolucion');

    // Notificaciones
    // IMPORTANTE: las rutas específicas ('conteo', 'ultimas', 'leer-todas') deben ir
    // ANTES de la ruta con parámetro {notificacion} para evitar conflictos.
    Route::get('/notificaciones/conteo', [NotificacionController::class, 'conteo'])->name('notificaciones.conteo');
    Route::get('/notificaciones/ultimas', [NotificacionController::class, 'ultimas'])->name('notificaciones.ultimas');
    Route::patch('/notificaciones/leer-todas', [NotificacionController::class, 'marcarTodasLeidas'])->name('notificaciones.leerTodas');
    Route::get('/notificaciones', [NotificacionController::class, 'index'])->name('notificaciones.index');
    Route::patch('/notificaciones/{notificacion}', [NotificacionController::class, 'update'])->name('notificaciones.update');

    // Proyectos
    Route::get('/proyectos', [ProyectoController::class, 'index'])->name('proyectos.index');
    Route::get('/proyectos/{proyecto}', [ProyectoController::class, 'show'])->name('proyectos.show');
    Route::get('/proyectos/{proyecto}/editar', [ProyectoController::class, 'edit'])->name('proyectos.edit');
    Route::put('/proyectos/{proyecto}', [ProyectoController::class, 'update'])->name('proyectos.update');

    // Crear proyecto (solo admin)
    Route::middleware('check.rol:admin')->group(function () {
        Route::get('/proyectos/crear', [ProyectoController::class, 'create'])->name('proyectos.create');
        Route::post('/proyectos', [ProyectoController::class, 'store'])->name('proyectos.store');
        Route::post('/proyectos/{proyecto}/usuarios', [ProyectoController::class, 'asignarUsuario'])
            ->name('proyectos.asignarUsuario');
        Route::delete('/proyectos/{proyecto}/usuarios/{usuario}', [ProyectoController::class, 'quitarUsuario'])
            ->name('proyectos.quitarUsuario');
    });
});

require __DIR__.'/auth.php';
