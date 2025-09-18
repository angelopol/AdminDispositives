<?php

namespace Ezparking\GestionDispositivos\Tests\Feature;

use Ezparking\GestionDispositivos\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Ezparking\GestionDispositivos\Models\Dispositivo;

class ListarDispositivosTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function puede_listar_filtrar_por_prefijo_y_ordenar()
    {
        Dispositivo::create(['nombre' => 'A1', 'mac' => 'AA:BB:CC:00:00:01']);
        Dispositivo::create(['nombre' => 'A2', 'mac' => 'AA:BB:CC:00:00:02']);
        Dispositivo::create(['nombre' => 'B1', 'mac' => 'FF:EE:DD:00:00:03']);

        $response = $this->getJson('/api/dispositivos?mac_prefijo=AA:BB:CC:00:00&sort_by=mac&sort_dir=desc&per_page=2');

        $response->assertOk()
            ->assertJsonStructure([
                'data', 'current_page', 'per_page', 'total'
            ]);

        $json = $response->json();
        $this->assertEquals(2, $json['per_page']);
        $this->assertEquals(2, count($json['data']));
        // Orden descendente por MAC (terminaciones 02 luego 01)
        $this->assertEquals('AA:BB:CC:00:00:02', $json['data'][0]['mac']);
        $this->assertEquals('AA:BB:CC:00:00:01', $json['data'][1]['mac']);
    }
}
