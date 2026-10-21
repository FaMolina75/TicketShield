<?php

namespace App\Providers;

use App\Mail\Transport\BrevoTransport;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Registrar los servicios de la aplicación.
     */
    public function register(): void
    {
        //
    }

    /**
     * Inicializar los servicios de la aplicación.
     */
    public function boot(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Brevo API Mail Transport
        |--------------------------------------------------------------------------
        |
        | Permite enviar correos mediante la API HTTPS de Brevo,
        | sin depender de conexiones SMTP.
        |
        */

        Mail::extend('brevo', function (array $config = []) {
            return new BrevoTransport();
        });

        /*
        |--------------------------------------------------------------------------
        | Configuración existente de TicketShield
        |--------------------------------------------------------------------------
        */

        JsonResource::withoutWrapping();

        Blade::directive('money', function ($money) {
            return "<?php echo '$ ' . number_format($money, 2); ?>";
        });

        Blade::directive('numberFormat', function ($number) {
            return "<?php echo number_format($number, 2); ?>";
        });
    }
}
