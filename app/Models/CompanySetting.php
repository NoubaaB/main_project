<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanySetting extends Model
{
    protected $guarded = [];

    /**
     * Get a single setting value by key.
     */
    public static function getValue(string $key, $default = null): ?string
    {
        $setting = static::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    /**
     * Set a single setting value by key.
     */
    public static function setValue(string $key, ?string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    /**
     * Get all settings as a key-value array.
     */
    public static function getAllSettings(): array
    {
        return static::pluck('value', 'key')->toArray();
    }
}
