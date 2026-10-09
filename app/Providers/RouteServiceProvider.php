<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     * Typically, users are redirected here after authentication.
     * @var string
     */
    public const HOME = '/dashboard';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        // Limitador por defecto de la API
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // 🛡️ NUEVO LIMITADOR ANTI-BOTS (TicketShield)
        RateLimiter::for('anti-bot', function (Request $request) {
            // Llave de conteo: combinación de IP y cuenta/token de sesión (N-08)
            $identificador = $request->user()?->id ?: $request->session()->getId();
            $llaveConteo = $request->ip() . '|' . $identificador;

            // Límite estricto de 5 peticiones.
            // Laravel devolverá automáticamente el HTTP 429 con la cabecera Retry-After.
            return Limit::perMinute(5)->by($llaveConteo);
        });

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}
