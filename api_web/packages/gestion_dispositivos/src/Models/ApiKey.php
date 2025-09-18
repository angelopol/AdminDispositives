<?php

namespace Ezparking\GestionDispositivos\Models;

use Illuminate\Database\Eloquent\Model;

class ApiKey extends Model
{
    protected $table = 'api_keys';

    protected $fillable = [
        'name', 'key_hash', 'plain_preview', 'is_admin', 'active', 'last_used_at'
    ];

    protected $casts = [
        'active' => 'boolean',
        'is_admin' => 'boolean',
        'last_used_at' => 'datetime',
    ];
}
