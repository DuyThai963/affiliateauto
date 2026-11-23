<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    use HasFactory;

    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'key',
        'value',
        'description',
    ];

    /**
     * Lấy giá trị cấu hình theo key.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::query()->find($key);
        if ($setting !== null && $setting->value !== null) {
            return $setting->value;
        }

        return $default;
    }

    /**
     * Thiết lập giá trị cấu hình theo key.
     */
    public static function set(string $key, mixed $value, ?string $description = null): void
    {
        static::query()->updateOrCreate(
            ['key' => $key],
            [
                'value' => (string) $value,
                'description' => $description,
            ]
        );
    }
}

