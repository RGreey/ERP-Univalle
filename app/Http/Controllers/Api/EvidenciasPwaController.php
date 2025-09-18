<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActividadMantenimiento;
use App\Models\FotoEvidencia;
use App\Models\PaqueteEvidencia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class EvidenciasPwaController extends Controller
{
    public function actividades()
    {
        $actividades = ActividadMantenimiento::orderBy('orden')->get(['id','actividad']);
        return response()->json($actividades);
    }

    public function guardar(Request $request)
    {
        $validated = $request->validate([
            'sede' => 'required|in:MI,VC,LI,Nodo',
            'mes' => 'required|integer|min:1|max:12',
            'anio' => 'required|integer|min:2020|max:2100',
            'fotos' => 'required|array|min:1',
            'fotos.*.actividad_id' => 'required|exists:actividades_mantenimiento,id',
            'fotos.*.archivo' => 'required|file|image|max:5120',
        ]);

        return DB::transaction(function () use ($validated, $request) {
            $paquete = PaqueteEvidencia::firstOrCreate([
                'sede' => $validated['sede'],
                'mes' => $validated['mes'],
                'anio' => $validated['anio'],
            ], [
                'usuario_id' => $request->user() ? $request->user()->id : null,
            ]);

            foreach ($validated['fotos'] as $index => $foto) {
                $path = $request->file("fotos.$index.archivo")->store('evidencias/fotos', 'public');

                // Normalizar imagen: recorte centrado a cuadrado + resize
                try {
                    $absolute = Storage::disk('public')->path($path);
                    $this->squareAndResizeImage($absolute, 1200);
                } catch (\Throwable $e) {
                    // noop: si falla, dejamos la original
                }
                FotoEvidencia::create([
                    'paquete_id' => $paquete->id,
                    'actividad_id' => $foto['actividad_id'],
                    'archivo' => $path,
                    'orden' => $index,
                ]);
            }

            return response()->json([
                'ok' => true,
                'paquete_id' => $paquete->id,
                'mensaje' => 'Evidencias guardadas',
            ]);
        });
    }

    // Recorta al centro para formar un cuadrado y redimensiona al tamaño objetivo
    protected function squareAndResizeImage(string $absolutePath, int $targetSize = 1200): void
    {
        if (!is_file($absolutePath)) return;
        [$w, $h, $type] = @getimagesize($absolutePath);
        if (!$w || !$h) return;

        switch ($type) {
            case IMAGETYPE_JPEG:
                $img = @imagecreatefromjpeg($absolutePath);
                if (function_exists('exif_read_data')) {
                    $exif = @exif_read_data($absolutePath);
                    if (!empty($exif['Orientation'])) {
                        switch ($exif['Orientation']) {
                            case 3: $img = imagerotate($img, 180, 0); break;
                            case 6: $img = imagerotate($img, -90, 0); break;
                            case 8: $img = imagerotate($img, 90, 0); break;
                        }
                        $w = imagesx($img); $h = imagesy($img);
                    }
                }
                $save = function($res) use ($absolutePath) { imagejpeg($res, $absolutePath, 85); };
                break;
            case IMAGETYPE_PNG:
                $img = @imagecreatefrompng($absolutePath);
                imagesavealpha($img, true);
                $save = function($res) use ($absolutePath) { imagesavealpha($res, true); imagepng($res, $absolutePath, 6); };
                break;
            case IMAGETYPE_WEBP:
                if (!function_exists('imagecreatefromwebp')) return;
                $img = @imagecreatefromwebp($absolutePath);
                $save = function($res) use ($absolutePath) { imagewebp($res, $absolutePath, 85); };
                break;
            default:
                return;
        }

        if (!$img) return;

        $w = imagesx($img); $h = imagesy($img);
        $side = min($w, $h);
        $srcX = (int)(($w - $side) / 2);
        $srcY = (int)(($h - $side) / 2);

        $square = imagecreatetruecolor($side, $side);
        imagealphablending($square, true); imagesavealpha($square, true);
        imagecopyresampled($square, $img, 0, 0, $srcX, $srcY, $side, $side, $side, $side);

        $target = imagecreatetruecolor($targetSize, $targetSize);
        imagealphablending($target, true); imagesavealpha($target, true);
        imagecopyresampled($target, $square, 0, 0, 0, 0, $targetSize, $targetSize, $side, $side);

        $save($target);

        imagedestroy($img); imagedestroy($square); imagedestroy($target);
    }
}


