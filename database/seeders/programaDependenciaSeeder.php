<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class programaDependenciaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $programas = [
            ['nombrePD' => 'Administración de Empresas'],
            ['nombrePD' => 'Contaduría Pública'],
            ['nombrePD' => 'Tecnología Agroambiental'],
            ['nombrePD' => 'Tecnología en Gestión de Organizaciones Turísticas'],
            ['nombrePD' => 'Tecnología en Desarrollo de Software'],
            ['nombrePD' => 'Secretaria Academica'],
            ['nombrePD' => 'Coordinacion Administrativa'],
            ['nombrePD' => 'Bienestar Universitario'],
            ['nombrePD' => 'Investigacion'],
            ['nombrePD' => 'Extensión y proyección social'],
            ['nombrePD' => 'Biblioteca'],
            ['nombrePD' => 'Recursos Tecnológicos'],
            ['nombrePD' => 'Dirección de Seccional'],
            ['nombrePD' => 'Consejería Estudiantil'],
            ['nombrePD' => 'Estudiante / Externo / otro']

            
        ];


        DB::table('programadependencia')->insert($programas);
        
    }
}