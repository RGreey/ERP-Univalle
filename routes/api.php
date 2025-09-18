<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\EvidenciasPwaController;
use App\Http\Controllers\PwaController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// RUTAS DE LA PWA (SIN AUTENTICACIÓN)
Route::get('/pwa', [PwaController::class, 'index'])->name('pwa.index');
Route::get('/pwa/novedades', [PwaController::class, 'getNovedades'])->name('pwa.novedades');
Route::post('/pwa/novedades/{id}/evidencia', [PwaController::class, 'uploadEvidencia'])->name('pwa.evidencia');
Route::post('/pwa/novedades/{id}/mantenimiento-realizado', [PwaController::class, 'marcarMantenimientoRealizado'])->name('pwa.mantenimiento-realizado');

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// PWA Evidencias de Mantenimiento (público, el PWA no envía sesión/token)
Route::get('/pwa/actividades-mantenimiento', [EvidenciasPwaController::class, 'actividades']);
Route::post('/pwa/evidencias/guardar', [EvidenciasPwaController::class, 'guardar']);
