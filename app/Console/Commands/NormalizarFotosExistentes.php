<?php

namespace App\Console\Commands;

use App\Models\FotoEvidencia;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class NormalizarFotosExistentes extends Command
{
    protected $signature = 'fotos:normalizar {--paquete= : ID del paquete específico}';
    protected $description = 'Normaliza todas las fotos existentes a cuadrado 1200x1200 con recorte centrado';

    public function handle()
    {
        $paqueteId = $this->option('paquete');
        
        $query = FotoEvidencia::with('paquete');
        if ($paqueteId) {
            $query->where('paquete_id', $paqueteId);
        }
        
        $fotos = $query->get();
        
        if ($fotos->isEmpty()) {
            $this->error('No se encontraron fotos para procesar.');
            return 1;
        }

        $this->info("Procesando {$fotos->count()} fotos...");
        $bar = $this->output->createProgressBar($fotos->count());
        $bar->start();

        $procesadas = 0;
        $errores = 0;

        foreach ($fotos as $foto) {
            try {
                $absolutePath = Storage::disk('public')->path($foto->archivo);
                
                if (!file_exists($absolutePath)) {
                    $this->warn("\nArchivo no encontrado: {$foto->archivo}");
                    $errores++;
                    continue;
                }

                // Usar el mismo método del controlador
                $this->squareAndResizeImage($absolutePath, 1200);
                $procesadas++;
                
            } catch (\Throwable $e) {
                $this->warn("\nError procesando foto {$foto->id}: " . $e->getMessage());
                $errores++;
            }
            
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        
        $this->info("✅ Procesadas: {$procesadas}");
        if ($errores > 0) {
            $this->warn("⚠️  Errores: {$errores}");
        }
        
        $this->info('🎉 Normalización completada. Las fotos ahora están en formato cuadrado 1200x1200.');
        
        return 0;
    }

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
                $save = function($res) use ($absolutePath) { 
                    imagesavealpha($res, true); 
                    imagepng($res, $absolutePath, 6); 
                };
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
        imagealphablending($square, true); 
        imagesavealpha($square, true);
        imagecopyresampled($square, $img, 0, 0, $srcX, $srcY, $side, $side, $side, $side);

        $target = imagecreatetruecolor($targetSize, $targetSize);
        imagealphablending($target, true); 
        imagesavealpha($target, true);
        imagecopyresampled($target, $square, 0, 0, 0, 0, $targetSize, $targetSize, $side, $side);

        $save($target);

        imagedestroy($img); 
        imagedestroy($square); 
        imagedestroy($target);
    }
}
