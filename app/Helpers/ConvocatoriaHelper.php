<?php

namespace App\Helpers;

use Carbon\Carbon;

class ConvocatoriaHelper
{
    /**
     * Ajustar fecha de cierre usando configuración
     */
    public static function ajustarFechaCierre($fechaCierre)
    {
        return self::ajustarFechaLimite($fechaCierre);
    }

    /**
     * Ajuste genérico para fechas límite (cierre/entrevistas):
     * - Aplica ajustes manuales de config si corresponden
     * - Ajuste universal: si es 00:00:00, interpreta como medianoche del día siguiente
     */
    public static function ajustarFechaLimite($fecha)
    {
        $fechaCarbon = Carbon::parse($fecha);

        // Ajuste manual (si coincide exactamente con alguno configurado)
        $ajustesFechas = config('app.ajustes_fechas_cierre', []);
        foreach ($ajustesFechas as $ajuste) {
            if ($fechaCarbon->format('Y-m-d H:i:s') === $ajuste['fecha_original']) {
                return Carbon::parse($ajuste['fecha_ajustada']);
            }
        }

        // Ajuste universal 00:00:00 -> día siguiente 00:00:00
        if ($fechaCarbon->hour === 0 && $fechaCarbon->minute === 0 && $fechaCarbon->second === 0) {
            return $fechaCarbon->copy()->addDay();
        }

        return $fechaCarbon;
    }

    /**
     * Verificar si una convocatoria está abierta (considerando ajustes)
     */
    public static function convocatoriaEstaAbierta($fechaCierre)
    {
        $fechaAjustada = self::ajustarFechaCierre($fechaCierre);
        return now()->lt($fechaAjustada);
    }

    /**
     * Verificar si una convocatoria está en período de entrevistas (considerando ajustes)
     */
    public static function convocatoriaEnEntrevistas($fechaCierre, $fechaEntrevistas)
    {
        $fechaCierreAjustada = self::ajustarFechaLimite($fechaCierre);
        $fechaEntrevistasAjustada = self::ajustarFechaLimite($fechaEntrevistas);
        $fechaActual = now();

        return $fechaActual->gt($fechaCierreAjustada) && $fechaActual->lt($fechaEntrevistasAjustada->copy()->addSecond());
    }

    /**
     * Obtener convocatoria activa o en entrevistas (considerando ajustes)
     */
    public static function obtenerConvocatoriaActiva()
    {
        // Traer últimas convocatorias y decidir en PHP con los ajustes
        $convocatorias = \App\Models\Convocatoria::orderBy('fechaCierre', 'desc')->take(10)->get();
        foreach ($convocatorias as $convocatoria) {
            if (self::convocatoriaEstaAbierta($convocatoria->fechaCierre) || self::convocatoriaEnEntrevistas($convocatoria->fechaCierre, $convocatoria->fechaEntrevistas)) {
                return $convocatoria;
            }
        }
        return null;
    }

    /**
     * NUEVO: Función para verificar si una fecha necesita ajuste automático
     */
    public static function necesitaAjusteAutomatico($fechaCierre)
    {
        $fecha = Carbon::parse($fechaCierre);
        
        // Detectar patrones que necesitan ajuste
        $patrones = [
            // CUALQUIER fecha a las 00:00:00 (ajuste universal)
            $fecha->hour === 0 && $fecha->minute === 0 && $fecha->second === 0,
            
            // Casos especiales que podrían necesitar ajuste manual
            $fecha->day === 30 && $fecha->month === 2 && $fecha->hour === 0 && $fecha->minute === 0 && $fecha->second === 0,
            $fecha->day === 29 && $fecha->month === 2 && !$fecha->isLeapYear() && $fecha->hour === 0 && $fecha->minute === 0 && $fecha->second === 0,
        ];
        
        return in_array(true, $patrones);
    }

    /**
     * NUEVO: Función para obtener información de ajuste
     */
    public static function obtenerInfoAjuste($fechaCierre)
    {
        $fecha = Carbon::parse($fechaCierre);
        $fechaAjustada = self::ajustarFechaLimite($fechaCierre);
        
        $info = [
            'fecha_original' => $fecha->format('Y-m-d H:i:s'),
            'fecha_ajustada' => $fechaAjustada->format('Y-m-d H:i:s'),
            'se_ajusto' => $fecha->ne($fechaAjustada),
            'tipo_ajuste' => 'ninguno'
        ];
        
        if ($info['se_ajusto']) {
            if ($fecha->hour === 0 && $fecha->minute === 0 && $fecha->second === 0) {
                $info['tipo_ajuste'] = 'automatico_universal';
                $info['descripcion'] = 'Ajuste automático universal: cualquier fecha 00:00:00 se interpreta como medianoche del día siguiente';
            } else {
                $info['tipo_ajuste'] = 'manual';
                $info['descripcion'] = 'Ajuste manual configurado en config/app.php';
            }
        }
        
        return $info;
    }
}
