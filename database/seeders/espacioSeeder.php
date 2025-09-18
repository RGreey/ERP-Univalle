<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EspacioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Sede María Inmaculada
        DB::table('espacio')->insert([
            'nombreEspacio' => 'Auditorio',
            'lugar' => 1,
        ]);

        DB::table('espacio')->insert([
            'nombreEspacio' => 'Biblioteca',
            'lugar' => 1, 
        ]);    

        DB::table('espacio')->insert([
            'nombreEspacio' => 'Salon 03',
            'lugar' => 1, 
        ]);
        
        DB::table('espacio')->insert([
            'nombreEspacio' => 'Salon 08',
            'lugar' => 1, 
        ]);
        
        DB::table('espacio')->insert([
            'nombreEspacio' => 'Sala de Sistemas A',
            'lugar' => 1,
        ]);
        
        DB::table('espacio')->insert([
            'nombreEspacio' => 'Sala de Sistemas B',
            'lugar' => 1, 
        ]);
        
        DB::table('espacio')->insert([
            'nombreEspacio' => 'Sala de Sistemas C',
            'lugar' => 1, 
        ]);
        
        DB::table('espacio')->insert([
            'nombreEspacio' => 'Cafeteria',
            'lugar' => 1, 
        ]);

        DB::table('espacio')->insert([
            'nombreEspacio' => 'Otro(especificarlo en detalles)',
            'lugar' => 1, 
        ]);

        // Sede Valle del Cauca
        DB::table('espacio')->insert([
            'nombreEspacio' => 'Cancha',
            'lugar' => 2,
        ]);    

        DB::table('espacio')->insert([
            'nombreEspacio' => 'Salón de música',
            'lugar' => 2, 
        ]);
        
        DB::table('espacio')->insert([
            'nombreEspacio' => 'Plazoleta Central',
            'lugar' => 2, 
        ]);

        DB::table('espacio')->insert([
            'nombreEspacio' => 'Cafeteria',
            'lugar' => 2, 
        ]);
        
        DB::table('espacio')->insert([
            'nombreEspacio' => 'Sala de Sistemas D',
            'lugar' => 2, 
        ]);
        
        DB::table('espacio')->insert([
            'nombreEspacio' => 'Otro(especificarlo en detalles)',
            'lugar' => 2,
        ]);

        // Nodo Sevilla
        DB::table('espacio')->insert([
            'nombreEspacio' => 'Auditorio',
            'lugar' => 3,
        ]);    
        
        DB::table('espacio')->insert([
            'nombreEspacio' => 'Cancha',
            'lugar' => 3, 
        ]);
        
        DB::table('espacio')->insert([
            'nombreEspacio' => 'Sala de Sistemas',
            'lugar' => 3, 
        ]);

        DB::table('espacio')->insert([
            'nombreEspacio' => 'Otro(especificarlo en detalles)',
            'lugar' => 3, 
        ]);


    }
}
