<?php

namespace Ezparking\GestionDispositivos;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Routing\Router;
use Ezparking\GestionDispositivos\Http\Middleware\ApiKeyAuth;
use Ezparking\GestionDispositivos\Console\Commands\GenerateApiKey;
use Ezparking\GestionDispositivos\Console\Commands\ListApiKeys;
use Ezparking\GestionDispositivos\Console\Commands\RotateApiKey;

class GestionDispositivosServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Aquí podríamos hacer bindings si se requiere
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Registrar alias middleware de paquete
        $this->app->booted(function() {
            /** @var Router $router */
            $router = $this->app['router'];
            if (! isset($router->getMiddleware()['api_key.auth'])) {
                $router->aliasMiddleware('api_key.auth', ApiKeyAuth::class);
            }
        });

        // Registrar rutas del paquete bajo /api/dispositivos asegurando middleware 'api'
        $routesFile = __DIR__.'/../routes/api.php';
        if (file_exists($routesFile)) {
            Route::middleware('api')->prefix('api')->group(function () use ($routesFile) {
                require $routesFile;
            });
        }

        // Registrar comandos de consola del paquete
        if ($this->app->runningInConsole()) {
            $this->commands([
                GenerateApiKey::class,
                ListApiKeys::class,
                RotateApiKey::class,
            ]);
        }

        // Cargar migraciones automáticamente (útil para entorno de pruebas)
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'gestion-dispositivos-migrations');

        $this->publishes([
            __DIR__.'/Database/Seeders' => database_path('seeders/GestionDispositivos'),
        ], 'gestion-dispositivos-seeders');

        // (Intencional) No se registran rutas web de demo aquí: el paquete queda enfocado sólo en APIs.
    }
}
