<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

// Agrega estos use para tu flujo de standby
use App\Events\CupoCancelado;
use App\Listeners\EmitirOfertasStandby;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        // Listener que ya tenías (verificación de email)
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],

        // NUEVO: al cancelar un cupo, emitir ofertas de standby
        CupoCancelado::class => [
            EmitirOfertasStandby::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * If you prefer auto-discovery of events, you could set this to true,
     * pero lo dejamos en false para controlar explícitamente $listen.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
