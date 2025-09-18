<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BackupDatabase extends Command
{
    protected $signature = 'db:backup {--filename= : Nombre del archivo de backup}';
    protected $description = 'Crea un backup completo de la base de datos MySQL';

    public function handle()
    {
        $this->info('Iniciando backup de la base de datos...');

        try {
            $filename = $this->option('filename') ?: 'backup_erp_univalle_' . date('Y-m-d_H-i-s') . '.sql';
            
            // Ruta donde se guardará el backup
            $backupPath = storage_path('app/backups');
            if (!is_dir($backupPath)) {
                mkdir($backupPath, 0755, true);
            }

            $fullPath = $backupPath . '/' . $filename;

            // Usar método PHP directamente (exec() puede estar deshabilitado en hosting compartido)
            $this->info('Creando backup usando método PHP...');
            if ($this->createBackupWithPHP($fullPath)) {
                $this->info("✅ Backup creado exitosamente: {$filename}");
            } else {
                $this->error('❌ Error al crear el backup');
                return 1;
            }

            if (file_exists($fullPath) && filesize($fullPath) > 0) {
                $this->info("📁 Ubicación: {$fullPath}");
                $this->info("📦 Tamaño: " . $this->formatBytes(filesize($fullPath)));
                return 0;
            } else {
                $this->error('❌ El archivo de backup no se creó correctamente');
                return 1;
            }

        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            return 1;
        }
    }



    private function createBackupWithPHP($fullPath)
    {
        try {
            $database = config('database.connections.mysql.database');
            
            // Obtener todas las tablas
            $tables = DB::select('SHOW TABLES');
            
            // El nombre de la columna puede variar según el nombre de la base de datos
            $firstTable = (array)$tables[0];
            $tableKey = array_keys($firstTable)[0];
            
            $sql = "-- Backup de la base de datos: {$database}\n";
            $sql .= "-- Fecha: " . date('Y-m-d H:i:s') . "\n\n";
            $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

            foreach ($tables as $table) {
                $tableName = $table->$tableKey;
                
                // Obtener la estructura de la tabla
                $createTable = DB::select("SHOW CREATE TABLE `{$tableName}`");
                $sql .= "-- Estructura de la tabla `{$tableName}`\n";
                $sql .= "DROP TABLE IF EXISTS `{$tableName}`;\n";
                $sql .= $createTable[0]->{'Create Table'} . ";\n\n";
                
                // Obtener los datos de la tabla
                $rows = DB::table($tableName)->get();
                
                if ($rows->count() > 0) {
                    $sql .= "-- Datos de la tabla `{$tableName}` ({$rows->count()} registros)\n";
                    $sql .= "INSERT INTO `{$tableName}` VALUES\n";
                    
                    $values = [];
                    foreach ($rows as $row) {
                        $rowData = [];
                        foreach ((array)$row as $value) {
                            if (is_null($value)) {
                                $rowData[] = 'NULL';
                            } else {
                                $rowData[] = "'" . addslashes($value) . "'";
                            }
                        }
                        $values[] = '(' . implode(',', $rowData) . ')';
                    }
                    
                    $sql .= implode(",\n", $values) . ";\n\n";
                }
            }
            
            $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
            
            // Escribir el archivo
            return file_put_contents($fullPath, $sql) !== false;
            
        } catch (\Exception $e) {
            $this->error('Error en backup PHP: ' . $e->getMessage());
            return false;
        }
    }

    private function formatBytes($size, $precision = 2)
    {
        $base = log($size, 1024);
        $suffixes = ['B', 'KB', 'MB', 'GB', 'TB'];
        return round(pow(1024, $base - floor($base)), $precision) . ' ' . $suffixes[floor($base)];
    }
}
