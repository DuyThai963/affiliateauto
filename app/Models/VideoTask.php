<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VideoTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'channel_id',
        'product_id',
        'task_type',
        'title',
        'caption',
        'hashtags',
        'script_data',
        'video_path',
        'status',
        'current_step',
        'progress',
        'scheduled_at',
        'published_at',
        'error_log',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'script_data' => 'array',
            'progress' => 'integer',
            'scheduled_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    /**
     * Get the channel that owns the video task.
     */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class, 'channel_id');
    }

    /**
     * Get the product associated with the video task.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}

