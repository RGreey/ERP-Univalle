<?php

namespace App\Services;

use App\Models\StandbyOferta;
use App\Models\CupoAsignacion;
use App\Models\CupoDiario;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StandbyOfferServiceAccept
{
    public function __construct(private StandbyOfferService $svc) {}

    /**
     * Acepta una oferta de standby por token y crea la asignación si hay vacante.
     * Retorna arreglo para renderizar en la vista de resultado.
     */
    public function aceptar(string $token): array
    {
        $tz  = (string) config('subsidio.timezone', 'America/Bogota');
        $now = now($tz);

        $oferta = StandbyOferta::with(['cupo','user'])->where('token', $token)->first();
        if (!$oferta) return ['ok' => false, 'msg' => 'Oferta no encontrada o token inválido.'];

        if ($oferta->estado !== 'pendiente') {
            return ['ok' => false, 'msg' => 'La oferta ya no está disponible (estado: '.$oferta->estado.').'];
        }

        $cupo = $oferta->cupo;
        if (!$cupo) {
            $oferta->estado = 'expirada';
            $oferta->save();
            return ['ok' => false, 'msg' => 'Cupo no encontrado.'];
        }

        // Expiración por tiempo
        if ($oferta->vence_en && $now->gt(Carbon::parse($oferta->vence_en, $tz))) {
            $oferta->estado = 'expirada';
            $oferta->save();
            return ['ok' => false, 'msg' => 'La oferta expiró.'];
        }

        // Validar que el usuario no tenga ya cupo ese día (en cualquier sede)
        $yaTiene = CupoAsignacion::where('user_id', $oferta->user_id)
            ->whereHas('cupo', function ($q) use ($cupo) {
                $q->where('convocatoria_id', $cupo->convocatoria_id)
                  ->whereDate('fecha', $cupo->fecha);
            })->exists();

        if ($yaTiene) {
            $oferta->estado = 'cup_full';
            $oferta->save();
            return ['ok' => false, 'msg' => 'Ya tienes un cupo asignado para este día.'];
        }

        // Capacidad usando ocupación activa (no cancelados)
        $ocupacionActiva = CupoAsignacion::where('cupo_diario_id', $cupo->id)
            ->where(function ($q) {
                $q->whereNull('asistencia_estado')
                  ->orWhere('asistencia_estado', '!=', 'cancelado');
            })->count();

        if ($ocupacionActiva >= (int) $cupo->capacidad) {
            $oferta->estado = 'cup_full';
            $oferta->save();
            return ['ok' => false, 'msg' => 'El cupo ya fue ocupado.'];
        }

        // Postulación (si existe) del usuario en esta convocatoria (usar FQCN para evitar errores de import)
        $postulacionId = \App\Models\PostulacionSubsidio::where('convocatoria_id', $cupo->convocatoria_id)
            ->where('user_id', $oferta->user_id)
            ->value('id'); // puede ser null (soportado por la migración que lo hace nullable)

        $asignacion = null;

        DB::transaction(function () use ($cupo, $oferta, $postulacionId, &$asignacion) {
            $attrs = [
                'cupo_diario_id' => $cupo->id,
                'postulacion_id' => $postulacionId, // puede ser null
                'user_id'        => $oferta->user_id,
                'estado'         => 'asignado',
                'asignado_en'    => now(),
                'qr_token'       => bin2hex(random_bytes(16)),
            ];

            if (Schema::hasColumn('subsidio_cupo_asignaciones', 'es_reemplazo')) {
                $attrs['es_reemplazo'] = true;
            }

            $asignacion = CupoAsignacion::create($attrs);

            // Marcar esta oferta como asignada
            $oferta->estado = 'asignada';
            $oferta->save();

            // Invalidar ofertas hermanas del mismo lote en este cupo
            if (!empty($oferta->batch_id)) {
                StandbyOferta::where('batch_id', $oferta->batch_id)
                    ->where('cupo_diario_id', $cupo->id)
                    ->where('id', '!=', $oferta->id)
                    ->where('estado', 'pendiente')
                    ->update(['estado' => 'cup_full']);
            }
        });

        return [
            'ok'          => true,
            'msg'         => 'Cupo asignado con éxito.',
            'cupo'        => $cupo,
            'oferta'      => $oferta->fresh(['cupo','user']),
            'asignacion'  => $asignacion,
        ];
    }
}