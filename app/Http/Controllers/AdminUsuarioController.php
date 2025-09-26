<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminUsuarioController extends Controller
{
    // Mostrar la lista de usuarios
    public function index(Request $request)
    {
        $query = User::query();
        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%')
                  ->orWhere('email', 'like', '%'.$request->search.'%');
            });
        }
        if ($request->filled('rol')) {
            $query->where('rol', $request->rol);
        }
        $usuarios = $query->orderBy('created_at', 'desc')->paginate(15);
        return view('admin.usuarios.index', compact('usuarios'));
    }

    // Aprobar el rol solicitado
    public function aprobarRol($id)
    {
        $usuario = User::findOrFail($id);
        if (!$usuario->email_verified_at) {
            return redirect()->back()->with('error', 'No se puede aprobar el rol hasta que el usuario haya verificado su correo.');
        }
        if ($usuario->rol_solicitado && in_array($usuario->rol_solicitado, ['Profesor', 'Administrativo'])) {
            $usuario->rol = $usuario->rol_solicitado;
            $usuario->rol_solicitado = null;
            $usuario->save();
        }
        return redirect()->back()->with('success', 'Rol aprobado correctamente.');
    }

    // Mostrar formulario de creación
    public function create()
    {
        return view('admin.usuarios.create');
    }

    // Guardar nuevo usuario
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'rol' => 'required|in:Estudiante,Profesor,Administrativo,CooAdmin,AuxAdmin,AdminBienestar,Restaurante',
            'password' => 'required|string|min:8|confirmed',
        ]);
        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'rol' => $request->rol,
            'password' => Hash::make($request->password),
            'email_verified_at' => now(),
        ]);
        return redirect()->route('admin.usuarios.index')->with('success', 'Usuario creado correctamente.');
    }

    // Mostrar formulario de edición
    public function edit($id)
    {
        $usuario = User::findOrFail($id);
        return view('admin.usuarios.edit', compact('usuario'));
    }

    // Actualizar usuario
    public function update(Request $request, $id)
    {
        $usuario = User::findOrFail($id);
        $request->validate([
            'name' => 'required|string|max:255',
            'rol' => 'required|in:Estudiante,Profesor,Administrativo,CooAdmin,AuxAdmin,AdminBienestar,Restaurante',
        ]);
        $usuario->update([
            'name' => $request->name,
            'rol' => $request->rol,
        ]);
        if ($request->filled('password')) {
            $usuario->password = Hash::make($request->password);
        }
        $usuario->save();
        return redirect()->route('admin.usuarios.index')->with('success', 'Usuario actualizado correctamente.');
    }

    // Eliminar usuario
    public function destroy($id)
    {
        $usuario = User::findOrFail($id);
        $usuario->delete();
        return redirect()->route('admin.usuarios.index')->with('success', 'Usuario eliminado correctamente.');
    }
} 