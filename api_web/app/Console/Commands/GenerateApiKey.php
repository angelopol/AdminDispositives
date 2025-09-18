<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Security\ApiKey;
use Illuminate\Support\Facades\Hash;

class GenerateApiKey extends Command
{
    protected $signature = 'apikey:generate {name : Nombre descriptivo de la llave}';
    protected $description = 'Genera una nueva API key y muestra el valor en claro una sola vez';

    public function handle(): int
    {
        $name = $this->argument('name');
        $raw = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $token = substr($raw, 0, 48);
        $hash = Hash::make($token);

        $key = ApiKey::create([
            'name' => $name,
            'key_hash' => $hash,
            'plain_preview' => substr($token, 0, 12),
            'active' => true,
        ]);

        $this->info('API Key creada (guárdala ahora, no se mostrará de nuevo):');
        $this->line($token);
        $this->table(['ID','Name','Preview','Active'], [[ $key->id, $key->name, $key->plain_preview, $key->active ? 'yes':'no' ]]);
        return self::SUCCESS;
    }
}
