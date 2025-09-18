<?php

namespace Tests\Feature\PaqueteGestionDispositivos;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Ezparking\GestionDispositivos\Models\Dispositivo;

class EliminarDispositivoTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function puede_eliminar_un_dispositivo()
    {
        $dispositivo = Dispositivo::create([
            'nombre' => 'Temporal',
            'mac' => 'AA:AA:AA:AA:AA:30'
        ]);

        $response = $this->deleteJson("/api/dispositivos/{$dispositivo->mac}");
        $response->assertOk();

        $this->assertDatabaseMissing('dispositivos', [
            'id' => $dispositivo->id
        ]);
    }
}
