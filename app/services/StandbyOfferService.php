<?php

namespace App\Services;

use App\Mail\StandbyOfertaMail;
use App\Models\CupoAsignacion;
use App\Models\CupoDiario;
use App\Models\StandbyOferta;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class StandbyOfferService
{


    public function expirePendientesVencidas(CupoDiario $cupo): int
    {
        $tz = (string) config('subsidio.timezone','America/Bogota');
        $now = now($tz);
        return StandbyOferta::where('cupo_diario_id', $cupo->id)
            ->where('estado','pendiente')
            ->whereNotNull('vence_en')
            ->where('vence_en','<',$now)
            ->update(['estado'=>'expirada']);
    }

    public function emitirOfertasPorVacantes(CupoDiario $cupo, int $vacantes, int $parallelN, int $ttlMin): int
    {
        // Primero caduca las vencidas para no bloquear reenvíos
        $this->expirePendientesVencidas($cupo);

        $total = 0;
        for ($i=0; $i<$vacantes; $i++) {
            $total += $this->emitirLote($cupo, $parallelN, $ttlMin);
        }
        return $total;
    }

    // Crea hasta N ofertas "pendiente" en un batch y envía correos
    public function emitirLote(CupoDiario $cupo, int $parallelN, int $ttlMin): int
    {
        $eligibles = app(\App\Services\StandbySelectorService::class)->elegiblesPara($cupo, $parallelN * 3);
        if ($eligibles->isEmpty()) return 0;

        $tz    = (string) config('subsidio.timezone','America/Bogota');
        $now   = now($tz);
        $h12   = Carbon::parse($cupo->fecha->toDateString().' '.config('subsidio.cancelacion_tardia_hasta','12:00'), $tz);
        // Si el cupo es FUTURO, la oferta vive hasta el corte de ese día; si es HOY, usa TTL capado al corte
        $vence = $cupo->fecha->isFuture()
            ? $h12
            : min($now->copy()->addMinutes($ttlMin), $h12);

        $batchId = (string) Str::uuid();
        $creadas = 0;

        foreach ($eligibles->take($parallelN) as $uid) {
            // Evitar duplicado solo si existe PENDIENTE VIGENTE
            $dupe = StandbyOferta::where('cupo_diario_id',$cupo->id)
                ->where('user_id',$uid)
                ->where('estado','pendiente')
                ->where(function($q) use ($now) {
                    $q->whereNull('vence_en')->orWhere('vence_en','>',$now);
                })
                ->exists();
            if ($dupe) continue;

            $oferta = StandbyOferta::create([
                'batch_id'      => $batchId,
                'cupo_diario_id'=> $cupo->id,
                'user_id'       => $uid,
                'estado'        => 'pendiente',
                'token'         => bin2hex(random_bytes(32)),
                'enviado_en'    => $now,
                'vence_en'      => $vence instanceof Carbon ? $vence : Carbon::parse($vence, $tz),
                'via'           => 'email',
            ]);
            $creadas++;

            $user = User::find($uid);
            if ($user && $user->email) {
                // Para desarrollo, usa send(); en prod con worker, puedes usar queue()
                Mail::to($user->email)->send(new StandbyOfertaMail($oferta));
            }
        }

        return $creadas;
    }

    // Aceptación con lock de capacidad
    public function aceptarPorToken(string $token): array
    {
        $tz = config('subsidio.timezone','America/Bogota');
        $now = now($tz);

        $oferta = \App\Models\StandbyOferta::with('cupo','user')->where('token', $token)->first();
        if (!$oferta || $oferta->estado !== 'pendiente') {
            return ['ok'=>false,'msg'=>'Esta oferta no está disponible.'];
        }

        $cupo = $oferta->cupo;
        if (!$cupo) return ['ok'=>false,'msg'=>'Cupo no disponible.'];

        $limiteDia = Carbon::parse($cupo->fecha->toDateString().' '.config('subsidio.cancelacion_tardia_hasta','12:00'), $tz);
        if ($now->gt($limiteDia) || ($oferta->vence_en && $now->gt($oferta->vence_en))) {
            $oferta->estado = 'expirada';
            $oferta->save();
            return ['ok'=>false,'msg'=>'La oferta expiró.'];
        }

        $yaTiene = \App\Models\CupoAsignacion::where('user_id', $oferta->user_id)
            ->whereHas('cupo', function($q) use ($cupo) {
                $q->where('convocatoria_id', $cupo->convocatoria_id)
                  ->whereDate('fecha', $cupo->fecha->toDateString());
            })->exists();
        if ($yaTiene) {
            $oferta->estado = 'rechazada';
            $oferta->save();
            return ['ok'=>false,'msg'=>'Ya tienes un cupo asignado para este día.'];
        }

        return DB::transaction(function () use ($cupo, $oferta, $tz) {
            $cupo->refresh();
            if ($cupo->ocupacionActiva() >= $cupo->capacidad) {
                $oferta->estado = 'cup_full';
                $oferta->save();
                return ['ok'=>false,'msg'=>'El cupo ya fue ocupado.'];
            }

            \App\Models\CupoAsignacion::create([
                'cupo_diario_id' => $cupo->id,
                'postulacion_id' => null,
                'user_id'        => $oferta->user_id,
                'estado'         => 'asignado',
                'asignado_en'    => now($tz),
                'qr_token'       => bin2hex(random_bytes(16)),
                'es_reemplazo'   => true,
                'reemplaza_asignacion_id' => null, // puedes enlazar si tienes el id de la cancelada
            ]);
            $cupo->increment('asignados');

            $oferta->estado = 'asignada';
            $oferta->aceptada_en = now($tz);
            $oferta->save();

            \App\Models\StandbyOferta::where('batch_id', $oferta->batch_id)
                ->where('id','<>',$oferta->id)
                ->where('estado','pendiente')
                ->update(['estado'=>'cup_full','updated_at'=>now($tz)]);

            // Email de confirmación
            if ($oferta->user && $oferta->user->email) {
                \Illuminate\Support\Facades\Mail::to($oferta->user->email)->queue(new StandbyConfirmacionMail($oferta));
            }

            return ['ok'=>true,'msg'=>'Tu cupo ha sido asignado. ¡Nos vemos hoy!'];
        });
    }
}