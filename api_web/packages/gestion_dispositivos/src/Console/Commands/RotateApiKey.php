<?php

namespace Ezparking\GestionDispositivos\Console\Commands;

use Illuminate\Console\Command;
use Ezparking\GestionDispositivos\Models\ApiKey;
use Illuminate\Support\Facades\Hash;

class RotateApiKey extends Command
{
    protected $signature = 'gestion:apikey:rotate {identifier : ID o nombre de la API key a rotar} {--name : Interpretar el identificador como nombre en lugar de ID} {--show-json : Salida JSON con nueva clave}';
    protected $description = 'Rota (regenera) el token secreto de una API key existente buscándola por ID (default) o por nombre (--name) y muestra el nuevo valor una sola vez';

    public function handle(): int
    {
        $identifier = $this->argument('identifier');
        $byName = $this->option('name');

        /** @var ApiKey|null $key */
        if ($byName) {
            $matches = ApiKey::where('name', $identifier)->get();
            if ($matches->isEmpty()) {
                $this->error("API Key con nombre '{$identifier}' no encontrada");
                return self::FAILURE;
            }
            if ($matches->count() > 1) {
                $this->error("Se encontraron ".$matches->count()." API Keys con el mismo nombre '{$identifier}'. Usa el ID para desambiguar (IDs: ".$matches->pluck('id')->join(', ').")");
                return self::FAILURE;
            }
            $key = $matches->first();
        } else {
            $key = ApiKey::find($identifier);
            if (! $key) {
                $this->error("API Key con ID {$identifier} no encontrada");
                return self::FAILURE;
            }
        }

        if (! $key->active) {
            $this->warn('La key está inactiva: puedes rotarla, pero recuerda activarla si deseas usarla.');
        }

        $raw = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $token = substr($raw, 0, 48);
        $key->key_hash = Hash::make($token);
        $key->plain_preview = substr($token, 0, 12);
        $key->save();

        if ($this->option('show-json')) {
            $this->line(json_encode([
                'ok' => true,
                'id' => $key->id,
                'name' => $key->name,
                'plain_preview' => $key->plain_preview,
                'is_admin' => $key->is_admin,
                'active' => $key->active,
                'rotated_token_plain' => $token,
                'looked_up_by' => $byName ? 'name' : 'id',
                'updated_at' => $key->updated_at?->toDateTimeString(),
            ], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
        } else {
            $this->info('API Key rotada. Guarda el nuevo token ahora (no se volverá a mostrar):');
            $this->line($token);
            $this->newLine();
            $this->table(['ID','Name','Preview','Admin','Active','Updated','Lookup'], [[
                $key->id,
                $key->name,
                $key->plain_preview,
                $key->is_admin ? 'yes':'no',
                $key->active ? 'yes':'no',
                $key->updated_at?->toDateTimeString(),
                $byName ? 'name' : 'id',
            ]]);
        }
        return self::SUCCESS;
    }
}
