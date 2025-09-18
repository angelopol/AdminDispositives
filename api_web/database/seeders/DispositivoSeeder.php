<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DispositivoSeeder extends Seeder
{
    public function run(): void
    {
        // Delegar al seeder del paquete instanciándolo
        $packageSeeder = new \Ezparking\GestionDispositivos\Database\Seeders\DispositivoSeeder();
        $packageSeeder->run();
    }
}
