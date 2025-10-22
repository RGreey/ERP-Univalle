<?php

namespace App\Services;

use App\Models\CupoAsignacion;
use App\Models\CupoDiario;
use Carbon\Carbon;

class ReglasCuposService
{
    public function canCancel(CupoAsignacion $asig): bool
    {
        if (($asig->asistencia_estado ?? 'pendiente') !== 'pendiente') return false;
        if (!$asig->relationLoaded('cupo')) $asig->load('cupo');
        if (!$asig->cupo) return false;

        // Bloquea si el día es festivo
        if ($this->isFestivo($asig->cupo)) return false;

        $hora = (string) config('subsidio.cancelacion_tardia_hasta', '13:00');
        [$lim, $fecha] = $this->limiteDelDia($asig->cupo, $hora);

        return $this->isDiaHabil($fecha) && now($this->tz())->lte($lim);
    }

    public function razonNoCancelar(CupoAsignacion $asig): ?string
    {
        if (($asig->asistencia_estado ?? 'pendiente') !== 'pendiente') {
            return 'Estado actual: "'.$asig->asistencia_estado.'".';
        }
        if (!$asig->relationLoaded('cupo')) $asig->load('cupo');
        if (!$asig->cupo) return 'Cupo no encontrado.';
        if ($this->isFestivo($asig->cupo)) return 'Día festivo: no hay servicio.';

        $hora = (string) config('subsidio.cancelacion_tardia_hasta', '13:00');
        [$lim, $fecha] = $this->limiteDelDia($asig->cupo, $hora);
        if (!$this->isDiaHabil($fecha)) return 'No es un día hábil.';
        if (now($this->tz())->gt($lim)) return 'Pasó la hora límite ('.$lim->format('H:i').').';
        return null;
    }

    public function limiteCancelar(CupoDiario $cupo): Carbon
    {
        $hora = (string) config('subsidio.cancelacion_tardia_hasta', '13:00');
        return $this->limiteDelDia($cupo, $hora)[0];
    }

    public function canUndo(CupoAsignacion $asig): bool
    {
        if (($asig->asistencia_estado ?? '') !== 'cancelado') return false;
        if (!$asig->relationLoaded('cupo')) $asig->load('cupo');
        if (!$asig->cupo) return false;
        if ($this->isFestivo($asig->cupo)) return false;

        $hora = (string) config('subsidio.hora_limite_deshacer', '10:00');
        [$lim, $fecha] = $this->limiteDelDia($asig->cupo, $hora);

        return $this->isDiaHabil($fecha) && now($this->tz())->lte($lim);
    }

    public function razonNoDeshacer(CupoAsignacion $asig): ?string
    {
        if (($asig->asistencia_estado ?? '') !== 'cancelado') {
            return 'Solo puedes deshacer si está en estado "cancelado".';
        }
        if (!$asig->relationLoaded('cupo')) $asig->load('cupo');
        if (!$asig->cupo) return 'Cupo no encontrado.';
        if ($this->isFestivo($asig->cupo)) return 'Día festivo: no hay servicio.';

        $hora = (string) config('subsidio.hora_limite_deshacer', '10:00');
        [$lim, $fecha] = $this->limiteDelDia($asig->cupo, $hora);
        if (!$this->isDiaHabil($fecha)) return 'No es un día hábil.';
        if (now($this->tz())->gt($lim)) return 'Pasó la hora límite ('.$lim->format('H:i').').';
        return null;
    }

    public function limiteDeshacer(CupoDiario $cupo): Carbon
    {
        $hora = (string) config('subsidio.hora_limite_deshacer', '10:00');
        return $this->limiteDelDia($cupo, $hora)[0];
    }

    // Helpers

    private function limiteDelDia(CupoDiario $cupo, string $hora): array
    {
        $base = $cupo->fecha instanceof \Carbon\CarbonInterface
            ? $cupo->fecha->copy()->timezone($this->tz())
            : Carbon::parse((string) $cupo->fecha, $this->tz());

        $fecha = $base->copy()->startOfDay();
        $lim   = $base->copy()->setTimeFromTimeString($hora);
        return [$lim, $fecha];
    }

    private function isDiaHabil(Carbon $fecha): bool
    {
        return in_array($fecha->dayOfWeekIso, (array) config('subsidio.dias_habiles_iso', [1,2,3,4,5]), true);
    }

    private function isFestivo(CupoDiario $cupo): bool
    {
        // Soporta ambos: atributo cast o columna booleana. Por defecto: no festivo.
        return (bool) ($cupo->es_festivo ?? false);
    }

    private function tz(): string
    {
        return (string) config('subsidio.timezone','America/Bogota');
    }
}