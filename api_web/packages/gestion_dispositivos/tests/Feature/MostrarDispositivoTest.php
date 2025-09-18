<?php

namespace Ezparking\GestionDispositivos\Tests\Feature;

use Ezparking\GestionDispositivos\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Ezparking\GestionDispositivos\Models\Dispositivo;

class MostrarDispositivoTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function puede_mostrar_un_dispositivo_existente()
    {
        $dispositivo = Dispositivo::create([
            'nombre' => 'Gateway',
            'mac' => 'AA:AA:AA:AA:AA:10'
        ]);

        $response = $this->getJson("/api/dispositivos/{$dispositivo->id}");

        $response->assertOk()
            ->assertJsonPath('dispositivo.id', $dispositivo->id)
            ->assertJsonPath('dispositivo.mac', 'AA:AA:AA:AA:AA:10');
    }

    /** @test */
    public function retorna_404_si_no_existe()
    {
        $response = $this->getJson('/api/dispositivos/999');

        $response->assertStatus(404);
    }
}
