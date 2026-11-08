<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiKey extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider',
        'key_name',
        'api_key',
        'daily_limit',
        'used_today',
        'cooldown_seconds',
        'is_active',
        'last_used_at',
        'reset_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'daily_limit' => 'integer',
            'used_today' => 'integer',
            'cooldown_seconds' => 'integer',
            'is_active' => 'boolean',
            'last_used_at' => 'datetime',
            'reset_at' => 'datetime',
        ];
    }
}

