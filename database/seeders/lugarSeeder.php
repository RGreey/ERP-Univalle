<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LugarSeeder extends Seeder
{
    public function run()
    {
        $lugares = [
            ['nombreLugar' => 'Sede María Inmaculada'],
            ['nombreLugar' => 'Sede Valle del Cauca'],
            ['nombreLugar' => 'Nodo Sevilla'],
            
        ];

        DB::table('lugar')->insert($lugares);
    }
}
