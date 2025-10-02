<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\PostulacionSubsidio;
use App\Models\SubsidioObservacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminEstudiantesController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth','checkrole:AdminBienestar']);
    }

    // Lista estudiantes que tienen al menos una postulación al subsidio
    public function index(Request $request)
    {
        $q      = trim((string) $request->input('q'));
        $estado = trim((string) $request->input('estado')); // estado de su última postulación

        // Tomamos últimos registros por usuario con subconsulta (última postulación)
        $subUltima = PostulacionSubsidio::select('user_id')
            ->selectRaw('MAX(id) as last_id')
            ->groupBy('user_id');

        $ultimas = PostulacionSubsidio::from('subsidio_postulaciones as sp')
            ->joinSub($subUltima, 'u', 'sp.id', '=', 'u.last_id')
            ->with('user')
            ->when($estado, fn($qb) => $qb->where('sp.estado', $estado))
            ->when($q, function ($qb) use ($q) {
                $qb->whereHas('user', function ($uq) use ($q) {
                    $uq->where('name','like',"%$q%")
                       ->orWhere('email','like',"%$q%");
                });
            })
            ->orderByRaw("FIELD(sp.estado,'beneficiario','evaluada','enviada','rechazada','anulada')") // orden útil
            ->orderByDesc('sp.created_at')
            ->paginate(15)
            ->withQueryString();

        return view('roles.adminbienestar.estudiantes.index', compact('ultimas','q','estado'));
    }

    // Detalle de un estudiante con su historial de postulaciones
    public function show(User $user)
    {
        $postulaciones = PostulacionSubsidio::with(['convocatoria','respuestas.pregunta'])
            ->where('user_id',$user->id)
            ->orderByDesc('created_at')
            ->get();

        $observaciones = SubsidioObservacion::with('admin')
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->get();

        return view('roles.adminbienestar.estudiantes.show', compact('user','postulaciones','observaciones'));
    }

    // Crear observación interna
    public function storeObservacion(User $user, Request $request)
    {
        $data = $request->validate([
            'texto' => ['required','string','min:3','max:2000'],
        ]);

        SubsidioObservacion::create([
            'user_id'  => $user->id,
            'admin_id' => Auth::id(),
            'texto'    => $data['texto'],
        ]);

        return back()->with('success','Observación agregada.');
    }

    // Eliminar observación
    public function destroyObservacion(User $user, SubsidioObservacion $observacion)
    {
        abort_unless($observacion->user_id === $user->id, 404);
        $observacion->delete();
        return back()->with('success','Observación eliminada.');
    }
}