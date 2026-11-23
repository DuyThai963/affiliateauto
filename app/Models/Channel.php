<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Channel extends Model
{
    use HasFactory;

    protected $fillable = [
        'platform',
        'channel_name',
        'type',
        'browser_profile_path',
        'daily_post_limit',
        'min_interval_minutes',
        'operation_mode',
        'status',
        'last_posted_at',
        'last_healthchecked_at',
        'health_notes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'daily_post_limit' => 'integer',
            'min_interval_minutes' => 'integer',
            'last_posted_at' => 'datetime',
            'last_healthchecked_at' => 'datetime',
        ];
    }

    /**
     * Get all video tasks for the channel.
     */
    public function videoTasks(): HasMany
    {
        return $this->hasMany(VideoTask::class, 'channel_id');
    }
}

