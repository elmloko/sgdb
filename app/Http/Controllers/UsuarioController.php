<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUsuarioRequest;
use App\Http\Requests\UpdateUsuarioRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class UsuarioController extends Controller
{
    public function index(): Response
    {
        $usuarios = User::select('id', 'name', 'email', 'rol_global', 'activo', 'created_at')
            ->orderBy('name')
            ->get();

        return Inertia::render('Usuarios/Index', [
            'usuarios' => $usuarios,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Usuarios/Create');
    }

    public function store(StoreUsuarioRequest $request)
    {
        $data = $request->validated();
        $data['password'] = Hash::make($data['password']);
        $data['activo'] = $data['activo'] ?? true;

        User::create($data);

        return redirect()->route('usuarios.index')
            ->with('success', 'Usuario creado correctamente.');
    }

    public function edit(User $usuario): Response
    {
        return Inertia::render('Usuarios/Edit', [
            'usuario' => $usuario->only('id', 'name', 'email', 'rol_global', 'activo'),
        ]);
    }

    public function update(UpdateUsuarioRequest $request, User $usuario)
    {
        $data = $request->validated();

        // Solo actualizar la contraseña si se proporcionó una nueva
        if (empty($data['password'])) {
            unset($data['password']);
        } else {
            $data['password'] = Hash::make($data['password']);
        }

        $usuario->update($data);

        return redirect()->route('usuarios.index')
            ->with('success', 'Usuario actualizado correctamente.');
    }
}
