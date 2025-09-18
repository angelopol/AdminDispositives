<?php

namespace Ezparking\GestionDispositivos\Tests\Feature;

use Ezparking\GestionDispositivos\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Ezparking\GestionDispositivos\Models\Dispositivo;

class ActualizarDispositivoTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function puede_actualizar_ip_y_enlace()
    {
        $principal = Dispositivo::create([
            'nombre' => 'Gateway',
            'mac' => 'AA:AA:AA:AA:AA:01'
        ]);

        $sensor = Dispositivo::create([
            'nombre' => 'Sensor',
            'mac' => 'AA:AA:AA:AA:AA:02'
        ]);

        $payload = [
            'nombre' => 'Sensor Actualizado',
            'ip' => '10.0.0.99'
        ];

    $response = $this->putJson("/api/dispositivos/{$sensor->mac}", $payload);

        $response->assertOk()
            ->assertJsonPath('dispositivo.nombre', 'Sensor Actualizado')
            ->assertJsonPath('dispositivo.ip', '10.0.0.99');

        // Establecer enlace
        $respEnlace = $this->postJson("/api/dispositivos/{$sensor->mac}/enlace", [
            'enlace' => $principal->mac,
        ]);

        $respEnlace->assertOk()
            ->assertJsonPath('dispositivo.enlace_mac', $principal->mac);

        $this->assertDatabaseHas('dispositivos', [
            'mac' => $sensor->mac,
            'enlace_mac' => $principal->mac,
        ]);
    }
}
