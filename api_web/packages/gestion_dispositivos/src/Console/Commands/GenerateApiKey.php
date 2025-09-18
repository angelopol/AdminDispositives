<?php

namespace Ezparking\GestionDispositivos\Console\Commands;

use Illuminate\Console\Command;
use Ezparking\GestionDispositivos\Models\ApiKey;
use Illuminate\Support\Facades\Hash;

class GenerateApiKey extends Command
{
    protected $signature = 'gestion:apikey:generate {name : Nombre descriptivo de la llave} {--inactive : Crear como inactiva} {--admin : Marcar la llave como administradora}';
    protected $description = 'Genera una nueva API key del paquete gestion_dispositivos y muestra el valor en claro una sola vez';

    public function handle(): int
    {
        $name = $this->argument('name');
    $inactive = (bool) $this->option('inactive');
    $isAdmin = (bool) $this->option('admin');

        $raw = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $token = substr($raw, 0, 48);

        $key = ApiKey::create([
            'name' => $name,
            'key_hash' => Hash::make($token),
            'plain_preview' => substr($token, 0, 12),
            'is_admin' => $isAdmin,
            'active' => ! $inactive,
        ]);

        $this->info('API Key creada. Guarda el token ahora:');
        $this->line($token);
        $this->newLine();
        $this->table(['ID','Name','Preview','Admin','Active'], [[ $key->id, $key->name, $key->plain_preview, $key->is_admin ? 'yes':'no', $key->active ? 'yes':'no' ]]);
        return self::SUCCESS;
    }
}
