<?php

namespace Ezparking\GestionDispositivos\Tests;

use Ezparking\GestionDispositivos\GestionDispositivosServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function getPackageProviders($app)
    {
        return [GestionDispositivosServiceProvider::class];
    }

    protected function setUp(): void
    {
        parent::setUp();
        // Ejecutar migraciones del paquete (loadMigrationsFrom en provider ya lo hace)
    }
}
