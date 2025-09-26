<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SubsidioAlimenticioController extends Controller
{
    // Método que muestra el dashboard del AdminBienestar
    public function dashboard()
    {
        return view('roles.adminbienestar.dashboard');
    }
}
