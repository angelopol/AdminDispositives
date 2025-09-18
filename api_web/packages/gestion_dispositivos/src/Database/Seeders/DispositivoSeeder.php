<?php

namespace Ezparking\GestionDispositivos\Database\Seeders;

use Illuminate\Database\Seeder;
use Ezparking\GestionDispositivos\Models\Dispositivo;
use Illuminate\Support\Facades\DB;

class DispositivoSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            // Limpia datos previos (opcional en entorno de prueba)
            Dispositivo::truncate();

            $datos = [
                ['nombre' => 'Gateway Central', 'mac' => 'AA:BB:CC:00:00:01', 'ip' => '192.168.0.10'],
                ['nombre' => 'Sensor Norte',   'mac' => 'AA:BB:CC:00:00:02', 'ip' => '192.168.0.11'],
                ['nombre' => 'Sensor Sur',     'mac' => 'AA:BB:CC:00:00:03', 'ip' => '192.168.0.12'],
                ['nombre' => 'Sensor Este',    'mac' => 'AA:BB:CC:00:00:04', 'ip' => '192.168.0.13'],
                ['nombre' => 'Sensor Oeste',   'mac' => 'AA:BB:CC:00:00:05', 'ip' => '192.168.0.14'],
            ];

            $creados = [];
            foreach ($datos as $d) {
                $creados[$d['mac']] = Dispositivo::create($d);
            }

            // Establecer algunos enlaces de ejemplo
            $creados['AA:BB:CC:00:00:02']->enlace_mac = $creados['AA:BB:CC:00:00:01']->mac; // Norte -> Gateway
            $creados['AA:BB:CC:00:00:02']->save();
            $creados['AA:BB:CC:00:00:03']->enlace_mac = $creados['AA:BB:CC:00:00:01']->mac; // Sur -> Gateway
            $creados['AA:BB:CC:00:00:03']->save();
        });
    }
}
