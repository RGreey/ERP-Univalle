<?php

namespace App\Http\Controllers;

use App\Models\Restaurante;
use App\Models\User;
use Illuminate\Http\Request;

class AdminRestaurantesController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth','checkrole:AdminBienestar']);
    }

    public function index()
    {
        $restaurantes = Restaurante::with(['users' => function($q){
            $q->select('users.id','name','email','rol');
        }])->orderBy('codigo')->get();

        return view('roles.adminbienestar.restaurantes.index', compact('restaurantes'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'codigo' => ['required','string','max:50','alpha_dash','unique:subsidio_restaurantes,codigo'],
            'nombre' => ['required','string','max:120'],
        ]);

        Restaurante::create($data);
        return back()->with('success','Restaurante creado.');
    }

    public function attachUser(Request $request, Restaurante $restaurante)
    {
        $data = $request->validate(['email' => ['required','email']]);
        $u = User::where('email', $data['email'])->first();
        if (!$u) return back()->with('error','Usuario no encontrado.');

        // Si no tiene rol Restaurante, lo asignamos (no tocamos tu hasRole)
        if ($u->rol !== 'Restaurante') { $u->rol = 'Restaurante'; $u->save(); }

        $restaurante->users()->syncWithoutDetaching([$u->id]);
        return back()->with('success','Usuario asignado a la sede.');
    }

    public function detachUser(Request $request, Restaurante $restaurante)
    {
        $request->validate(['user_id' => 'required|integer']);
        $restaurante->users()->detach((int)$request->user_id);
        return back()->with('success','Usuario retirado de la sede.');
    }

    public function destroy(Restaurante $restaurante)
    {
        $restaurante->users()->detach();
        $restaurante->delete();
        return back()->with('success','Restaurante eliminado.');
    }
}