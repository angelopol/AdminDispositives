<?php

namespace Ezparking\GestionDispositivos\Tests\Feature;

use Ezparking\GestionDispositivos\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
// use Ezparking\GestionDispositivos\Models\Dispositivo; // No necesario en este test

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
            ->assertJsonStructure([
                'ok',
                'dispositivo' => [
                    'nombre', 'mac', 'ip', 'enlace_mac', 'created_at', 'updated_at'
                ]
            ])
            ->assertJsonPath('dispositivo.mac', 'AA:BB:CC:DD:EE:FF')
            ->assertJsonPath('dispositivo.enlace_mac', null);

        $this->assertDatabaseHas('dispositivos', [
            'nombre' => 'Sensor Entrada',
            'mac' => 'AA:BB:CC:DD:EE:FF', // Debe guardarse en mayúsculas
            'ip' => '192.168.0.10'
        ]);
    }

    /** @test */
    public function no_puede_registrar_con_mac_invalida()
    {
        $payload = [
            'nombre' => 'Sensor Erroneo',
            'mac' => 'ZZ:11:22:33:44:55', // ZZ no es hex válido
        ];

        $response = $this->postJson('/api/dispositivos', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['mac']);

        $this->assertDatabaseCount('dispositivos', 0);
    }
}
