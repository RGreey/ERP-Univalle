<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;

class BackupController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        // Verificar que el usuario sea administrador o el usuario especial
        if (!in_array(auth()->user()->rol, ['Administrativo', 'CooAdmin', 'AuxAdmin']) && 
            auth()->user()->email !== 'soporte.caicedonia@correounivalle.edu.co') {
            abort(403, 'No tienes permisos para acceder a esta funcionalidad.');
        }

        $backupPath = storage_path('app/backups');
        $backups = [];

        if (is_dir($backupPath)) {
            $files = glob($backupPath . '/*.sql');
            foreach ($files as $file) {
                $backups[] = [
                    'name' => basename($file),
                    'size' => $this->formatBytes(filesize($file)),
                    'date' => date('d/m/Y H:i:s', filemtime($file)),
                    'path' => $file
                ];
            }
            
            // Ordenar por fecha (más reciente primero)
            usort($backups, function($a, $b) {
                return filemtime($b['path']) - filemtime($a['path']);
            });
        }

        return view('admin.backups.index', compact('backups'));
    }

    public function create()
    {
        // Verificar que el usuario sea administrador o el usuario especial
        if (!in_array(auth()->user()->rol, ['Administrativo', 'CooAdmin', 'AuxAdmin']) && 
            auth()->user()->email !== 'soporte.caicedonia@correounivalle.edu.co') {
            abort(403, 'No tienes permisos para acceder a esta funcionalidad.');
        }

        try {
            $filename = 'backup_erp_univalle_' . date('Y-m-d_H-i-s') . '.sql';
            
            Artisan::call('db:backup', ['--filename' => $filename]);
            
            $output = Artisan::output();
            
            if (strpos($output, '✅') !== false) {
                return response()->json([
                    'success' => true,
                    'message' => 'Backup creado exitosamente',
                    'filename' => $filename
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al crear el backup'
                ], 500);
            }
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function download($filename)
    {
        // Verificar que el usuario sea administrador o el usuario especial
        if (!in_array(auth()->user()->rol, ['Administrativo', 'CooAdmin', 'AuxAdmin']) && 
            auth()->user()->email !== 'soporte.caicedonia@correounivalle.edu.co') {
            abort(403, 'No tienes permisos para acceder a esta funcionalidad.');
        }

        $filepath = storage_path('app/backups/' . $filename);
        
        if (!file_exists($filepath)) {
            abort(404, 'Archivo de backup no encontrado.');
        }

        return Response::download($filepath);
    }

    public function delete($filename)
    {
        // Verificar que el usuario sea administrador o el usuario especial
        if (!in_array(auth()->user()->rol, ['Administrativo', 'CooAdmin', 'AuxAdmin']) && 
            auth()->user()->email !== 'soporte.caicedonia@correounivalle.edu.co') {
            abort(403, 'No tienes permisos para acceder a esta funcionalidad.');
        }

        $filepath = storage_path('app/backups/' . $filename);
        
        if (file_exists($filepath)) {
            unlink($filepath);
            return response()->json([
                'success' => true,
                'message' => 'Backup eliminado exitosamente'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Archivo no encontrado'
        ], 404);
    }

    private function formatBytes($size, $precision = 2)
    {
        $base = log($size, 1024);
        $suffixes = ['B', 'KB', 'MB', 'GB', 'TB'];
        return round(pow(1024, $base - floor($base)), $precision) . ' ' . $suffixes[floor($base)];
    }
}
