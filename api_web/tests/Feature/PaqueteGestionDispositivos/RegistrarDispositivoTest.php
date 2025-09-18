<?php

namespace Tests\Feature\PaqueteGestionDispositivos;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Ezparking\GestionDispositivos\Models\Dispositivo;

class RegistrarDispositivoTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function puede_registrar_un_dispositivo_con_datos_validos()
    {
        $payload = [
            'nombre' => 'Sensor Entrada',
            'mac' => 'aa:bb:cc:dd:ee:ff',
            'ip' => '192.168.0.10'
        ];

        $response = $this->postJson('/api/dispositivos', $payload);
        $response->assertStatus(201)
            ->assertJsonPath('dispositivo.nombre', 'Sensor Entrada')
            ->assertJsonPath('dispositivo.mac', 'AA:BB:CC:DD:EE:FF')
            ->assertJsonPath('dispositivo.enlace_mac', null);

        $this->assertDatabaseHas('dispositivos', [ 'mac' => 'AA:BB:CC:DD:EE:FF' ]);
    }

    /** @test */
    public function no_puede_registrar_con_mac_invalida()
    {
        $payload = [
            'nombre' => 'Sensor Erroneo',
            'mac' => 'ZZ:11:22:33:44:55',
        ];

        $response = $this->postJson('/api/dispositivos', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['mac']);

        $this->assertDatabaseCount('dispositivos', 0);
    }
}
