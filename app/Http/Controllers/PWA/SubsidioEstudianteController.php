<?php

namespace App\Http\Controllers\PWA;

use App\Http\Controllers\Controller;
use App\Models\CupoAsignacion;
use App\Models\CupoDiario;
use App\Services\ReglasCuposService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SubsidioEstudianteController extends Controller
{
    public function misCupos(Request $request, ReglasCuposService $reglas)
    {
        $lunes = Carbon::parse($request->input('semana', now()->toDateString()))
            ->startOfWeek(Carbon::MONDAY);

        $asignaciones = CupoAsignacion::with('cupo')
            ->where('user_id', auth()->id())
            ->whereHas('cupo', function($q) use ($lunes) {
                $q->whereBetween('fecha', [$lunes->toDateString(), $lunes->copy()->addDays(6)->toDateString()]);
            })
            ->orderBy(CupoDiario::select('fecha')->whereColumn('subsidio_cupos_diarios.id','subsidio_cupo_asignaciones.cupo_diario_id'))
            ->orderBy(CupoDiario::select('sede')->whereColumn('subsidio_cupos_diarios.id','subsidio_cupo_asignaciones.cupo_diario_id'))
            ->get()
            ->map(function ($a) use ($reglas) {
                $a->can_cancel      = $reglas->canCancel($a);
                $a->can_undo        = $reglas->canUndo($a);
                $a->cancel_reason   = $a->can_cancel ? null : $reglas->razonNoCancelar($a);
                $a->undo_reason     = $a->can_undo ? null : $reglas->razonNoDeshacer($a);
                $a->lim_cancel_hhmm = $reglas->limiteCancelar($a->cupo)->format('H:i');
                $a->lim_undo_hhmm   = $reglas->limiteDeshacer($a->cupo)->format('H:i');
                return $a;
            });

        $prev = $lunes->copy()->subWeek()->toDateString();
        $next = $lunes->copy()->addWeek()->toDateString();

        return view('pwa.subsidio.mis_cupos', compact('asignaciones','lunes','prev','next'));
    }

    public function cancelar(Request $request, ReglasCuposService $reglas)
    {
        $data = $request->validate([
            'asignacion_id' => ['required','integer','exists:subsidio_cupo_asignaciones,id'],
            'motivo'        => ['nullable','string','max:2000'],
        ]);

        $asig = CupoAsignacion::with('cupo')
            ->where('user_id', auth()->id())
            ->findOrFail($data['asignacion_id']);

        abort_unless($reglas->canCancel($asig), 403);

        $asig->update([
            'asistencia_estado'     => 'cancelado',
            'cancelada_en'          => now(),
            'cancelada_por_user_id' => auth()->id(),
            'cancelacion_origen'    => 'estudiante',
            'cancelacion_motivo'    => $data['motivo'] ?? null,
        ]);

        return back()->with('success','Cancelación registrada.');
    }

    public function deshacer(Request $request, ReglasCuposService $reglas)
    {
        $data = $request->validate([
            'asignacion_id' => ['required','integer','exists:subsidio_cupo_asignaciones,id'],
            'motivo'        => ['required','string','max:2000'],
        ]);

        $asig = CupoAsignacion::with('cupo')
            ->where('user_id', auth()->id())
            ->findOrFail($data['asignacion_id']);

        abort_unless($reglas->canUndo($asig), 403);

        $asig->update([
            'asistencia_estado'     => 'pendiente',
            'reversion_en'          => now(),
            'reversion_por_user_id' => auth()->id(),
            'reversion_motivo'      => $data['motivo'],
        ]);

        return back()->with('success','Se deshizo la cancelación.');
    }
}