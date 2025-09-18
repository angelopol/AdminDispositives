<?php

namespace Ezparking\GestionDispositivos\Console\Commands;

use Illuminate\Console\Command;
use Ezparking\GestionDispositivos\Models\ApiKey;

class ListApiKeys extends Command
{
    protected $signature = 'gestion:apikey:list {--json : Mostrar salida en JSON}';
    protected $description = 'Lista las API keys del paquete gestion_dispositivos';

    public function handle(): int
    {
        $keys = ApiKey::orderByDesc('id')->get()->map(function($k){
            return [
                'id' => $k->id,
                'name' => $k->name,
                'preview' => $k->plain_preview,
                'admin' => $k->is_admin ? 'yes':'no',
                'active' => $k->active ? 'yes':'no',
                'last_used_at' => optional($k->last_used_at)->toDateTimeString(),
                'created_at' => $k->created_at->toDateTimeString(),
            ];
        });

        if ($this->option('json')) {
            $this->line(json_encode($keys, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
            return self::SUCCESS;
        }

        if ($keys->isEmpty()) {
            $this->warn('No hay API keys creadas. Usa gestion:apikey:generate para crear una.');
            return self::SUCCESS;
        }

        $this->table(['ID','Name','Preview','Admin','Active','Last Used','Created'], $keys->toArray());
        $this->info('La columna Preview muestra sólo los primeros 12 caracteres (plain_preview).');
        return self::SUCCESS;
    }
}
