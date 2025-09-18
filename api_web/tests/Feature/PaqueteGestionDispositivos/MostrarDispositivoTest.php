<?php

namespace Tests\Feature\PaqueteGestionDispositivos;

use Tests\TestCase;
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

        $response = $this->getJson("/api/dispositivos/{$dispositivo->mac}");
        $response->assertOk()
            ->assertJsonPath('dispositivo.mac', 'AA:AA:AA:AA:AA:10');
    }

    /** @test */
    public function retorna_404_si_no_existe()
    {
        $response = $this->getJson('/api/dispositivos/ZZ:ZZ:ZZ:ZZ:ZZ:99');
        $response->assertStatus(404);
    }
}
