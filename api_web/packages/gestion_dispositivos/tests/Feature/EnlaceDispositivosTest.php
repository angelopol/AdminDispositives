<?php

namespace Ezparking\GestionDispositivos\Tests\Feature;

use Ezparking\GestionDispositivos\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Ezparking\GestionDispositivos\Models\Dispositivo;

class EnlaceDispositivosTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function puede_establecer_y_eliminar_enlace()
    {
        $gateway = Dispositivo::create(['nombre' => 'Gateway', 'mac' => 'AA:AA:AA:AA:AA:20']);
        $sensor = Dispositivo::create(['nombre' => 'Sensor', 'mac' => 'AA:AA:AA:AA:AA:21']);

        $respEnlace = $this->postJson("/api/dispositivos/{$sensor->mac}/enlace", [
            'enlace' => $gateway->mac,
        ]);

        $respEnlace->assertOk()
            ->assertJsonPath('dispositivo.enlace_mac', $gateway->mac);

        $this->assertDatabaseHas('dispositivos', [
            'mac' => $sensor->mac,
            'enlace_mac' => $gateway->mac,
        ]);

        $respEliminar = $this->deleteJson("/api/dispositivos/{$sensor->mac}/enlace");

        $respEliminar->assertOk()
            ->assertJsonPath('dispositivo.enlace_mac', null);

        $this->assertDatabaseHas('dispositivos', [
            'mac' => $sensor->mac,
            'enlace_mac' => null,
        ]);
    }
}
