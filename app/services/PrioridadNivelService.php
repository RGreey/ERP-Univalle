<?php

namespace App\Services;

use App\Models\PostulacionSubsidio;

class PrioridadNivelService
{
    // Retorna: base, f1, f2, delta_auto, delta_solicitud, total_mejora, final, detalles[]
    public function calcular(PostulacionSubsidio $p): array
    {
        $p->loadMissing('respuestas.pregunta', 'respuestas.opcion', 'respuestas.pregunta.opciones');

        $vals = [
            'residencia' => null,
            'estrato'    => null,
            'jornada'    => null,
            'f1'         => false,
            'f2'         => false,
        ];

        foreach ($p->respuestas as $r) {
            $preg = $r->pregunta;
            if (!$preg) continue;

            if ($preg->grupo === 'Prioridad (base)') {
                if (stripos($preg->titulo, '¿Dónde resides?') !== false) {
                    $vals['residencia'] = optional($r->opcion)->texto;
                }
                if (stripos($preg->titulo, 'Estrato') !== false) {
                    $t = optional($r->opcion)->texto;
                    $vals['estrato'] = $t === 'Estrato 1' ? 1 : ($t === 'Estrato 2' ? 2 : ($t === 'Estrato 3 o más' ? 3 : null));
                }
                if ($preg->titulo === 'Jornada') {
                    $vals['jornada'] = optional($r->opcion)->texto;
                }
            }

            if ($preg->grupo === 'Prioridad (ajustes)') {
                if (stripos($preg->titulo, 'protección social') !== false) {
                    $vals['f1'] = (optional($r->opcion)->texto === 'Sí');
                }
                if (stripos($preg->titulo, 'madre cabeza de hogar') !== false) {
                    $vals['f2'] = (optional($r->opcion)->texto === 'Sí');
                }
            }
        }

        // Base
        $base = null;
        $res  = $vals['residencia'];
        $estr = $vals['estrato'];
        $jor  = $vals['jornada'];

        if (in_array($res, ['Rural / Vereda','Foráneo'], true)) {
            $base = match ($jor) {
                'Doble' => 1, 'Simple' => 2, 'Nocturna' => 3, default => null,
            };
        } elseif ($res === 'Urbano') {
            $base = match ($estr) {
                1 => match ($jor) { 'Doble'=>3,'Simple'=>4,'Nocturna'=>5, default=>null },
                2 => match ($jor) { 'Doble'=>5,'Simple'=>6,'Nocturna'=>7, default=>null },
                3 => match ($jor) { 'Doble'=>7,'Simple'=>8,'Nocturna'=>9, default=>null },
                default => null,
            };
        }

        // Ajustes
        $delta_auto      = min(2, ($vals['f1'] ? 1 : 0) + ($vals['f2'] ? 1 : 0));
        $delta_solicitud = 0; // reservado
        $total_mejora    = min(3, $delta_auto + $delta_solicitud);

        // Piso mínimo corregido a 1 (antes era 2):
        // si la base es 1, nunca debe subir a 2 por aplicar mejoras.
        $final           = $base !== null ? max(1, $base - $total_mejora) : null;

        return [
            'base'            => $base,
            'f1'              => $vals['f1'],
            'f2'              => $vals['f2'],
            'delta_auto'      => $delta_auto,
            'delta_solicitud' => $delta_solicitud,
            'total_mejora'    => $total_mejora,
            'final'           => $final,
            'detalles'        => [
                'residencia' => $res,
                'estrato'    => $estr,
                'jornada'    => $jor,
            ],
        ];
    }
}