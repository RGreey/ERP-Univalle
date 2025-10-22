<?php

namespace App\Http\Controllers\PWA;

use App\Http\Controllers\Controller;
use App\Models\StandbyRegistro;
use App\Models\ConvocatoriaSubsidio;
use Illuminate\Http\Request;

class StandbyController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Convocatoria activa o la más reciente
        $conv = ConvocatoriaSubsidio::orderByDesc('created_at')->first();
        abort_unless($conv, 404);

        $reg = StandbyRegistro::firstOrNew([
            'convocatoria_id' => $conv->id,
            'user_id'         => $user->id,
        ], [
            'es_externo' => false, // si este user no es beneficiario, puedes marcarlo externamente
            'activo'     => true,
        ]);

        $enum = ['caicedonia','sevilla','ninguno'];

        return view('pwa.subsidio.standby', [
            'convocatoria' => $conv,
            'reg'          => $reg,
            'enum'         => $enum,
        ]);
    }

    public function save(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'convocatoria_id' => ['required','integer'],
            'pref_lun' => ['required','in:caicedonia,sevilla,ninguno'],
            'pref_mar' => ['required','in:caicedonia,sevilla,ninguno'],
            'pref_mie' => ['required','in:caicedonia,sevilla,ninguno'],
            'pref_jue' => ['required','in:caicedonia,sevilla,ninguno'],
            'pref_vie' => ['required','in:caicedonia,sevilla,ninguno'],
            'activo'   => ['nullable','boolean'],
        ]);

        $reg = StandbyRegistro::firstOrNew([
            'convocatoria_id' => $data['convocatoria_id'],
            'user_id'         => $user->id,
        ]);

        $reg->fill([
            'pref_lun' => $data['pref_lun'],
            'pref_mar' => $data['pref_mar'],
            'pref_mie' => $data['pref_mie'],
            'pref_jue' => $data['pref_jue'],
            'pref_vie' => $data['pref_vie'],
            'activo'   => (bool)($data['activo'] ?? true),
        ]);
        $reg->save();

        return back()->with('success','Preferencias de standby guardadas.');
    }
}