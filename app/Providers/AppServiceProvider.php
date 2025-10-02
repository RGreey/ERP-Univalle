<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Vincular interfaz -> implementación
        $this->app->bind(CalculadoraPrioridad::class, PrioridadNivelService::class);

        // Si quieres singleton:
        // $this->app->singleton(CalculadoraPrioridad::class, PrioridadNivelService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Comparte en el layout el número de convocatorias abiertas para Estudiante
        View::composer('layouts.app', function ($view) {
            $count = 0;
            if (auth()->check() && auth()->user()->hasRole('Estudiante')) {
                $count = \App\Models\ConvocatoriaSubsidio::abiertasParaPostulacion()->count();
            }
            $view->with('subsidioConvocatoriasCount', $count);
        });
    }
}