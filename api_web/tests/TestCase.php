<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Ezparking\GestionDispositivos\GestionDispositivosServiceProvider;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Illuminate\Filesystem\Filesystem;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Registrar explícitamente el service provider del paquete
        $this->app->register(GestionDispositivosServiceProvider::class);

        // Ejecutar migraciones propias del proyecto
        Artisan::call('migrate', ['--force' => true]);

        // Ejecutar migraciones del paquete explícitamente
        $migrationsPath = 'packages/gestion_dispositivos/database/migrations';
        if (is_dir(base_path($migrationsPath))) {
            // Laravel permite --path relativo a base_path
            Artisan::call('migrate', ['--path' => $migrationsPath, '--force' => true]);
        }

        // Registrar rutas del paquete bajo el mismo prefijo /api que usa Laravel por defecto
        $routesFile = base_path('packages/gestion_dispositivos/routes/api.php');
        if (file_exists($routesFile)) {
            Route::middleware('api')->prefix('api')->group(function () use ($routesFile) {
                require $routesFile;
            });
        }
    }
}
