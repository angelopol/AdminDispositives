<?php

namespace Ezparking\GestionDispositivos\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dispositivo extends Model
{
    use HasFactory;

    protected $table = 'dispositivos';

    // La clave primaria es la MAC
    protected $primaryKey = 'mac';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'nombre',
        'mac',
        'ip',
        'enlace_mac',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    /**
     * Normaliza la MAC a mayúsculas antes de guardar.
     */
    public function setMacAttribute($value): void
    {
        if (is_string($value)) {
            $norm = strtoupper(trim($value));
            // Reemplazar posibles separadores distintos a ':' por ':' (ej. '-')
            $norm = preg_replace('/[-]/', ':', $norm);
            $this->attributes['mac'] = $norm;
        } else {
            $this->attributes['mac'] = $value;
        }
    }

    public function enlace()
    {
        return $this->belongsTo(Dispositivo::class, 'enlace_mac', 'mac');
    }

    public function enlazadoPor()
    {
        return $this->hasMany(Dispositivo::class, 'enlace_mac', 'mac');
    }

    public function tieneEnlace(): bool
    {
        return !is_null($this->enlace_mac);
    }
}
