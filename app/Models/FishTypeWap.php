<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FishTypeWap extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'fish_type_id',
        'warehouse_id',
        'date_start',
        'date_end',
        'wap',
    ];

    protected $casts = [
        'date_start' => 'date:Y-m-d',
        'date_end' => 'date:Y-m-d',
        'wap' => 'decimal:2',
    ];

    protected static ?\Illuminate\Support\Collection $cachedRules = null;

    protected static function booted()
    {
        static::saved(fn() => static::clearCachedRules());
        static::deleted(fn() => static::clearCachedRules());
    }

    public static function clearCachedRules(): void
    {
        static::$cachedRules = null;
    }

    public function fishType()
    {
        return $this->belongsTo(FishType::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Find matching WAP for a fish type, warehouse, and date.
     */
    public static function getWapForFishType($fishTypeId, $warehouseId, $date): ?float
    {
        if (!$fishTypeId || !$warehouseId || !$date) {
            return null;
        }

        if (static::$cachedRules === null) {
            static::$cachedRules = static::orderBy('date_start', 'desc')->orderBy('id', 'desc')->get();
        }

        if (static::$cachedRules->isEmpty()) {
            return null;
        }

        $dateStr = is_string($date) ? substr($date, 0, 10) : $date->format('Y-m-d');

        $record = static::$cachedRules->first(function ($rule) use ($fishTypeId, $warehouseId, $dateStr) {
            if ($rule->fish_type_id != $fishTypeId || $rule->warehouse_id != $warehouseId) {
                return false;
            }
            $ruleStart = is_string($rule->date_start) ? substr($rule->date_start, 0, 10) : ($rule->date_start?->format('Y-m-d') ?? '');
            if ($ruleStart > $dateStr) {
                return false;
            }
            if ($rule->date_end !== null) {
                $ruleEnd = is_string($rule->date_end) ? substr($rule->date_end, 0, 10) : ($rule->date_end?->format('Y-m-d') ?? '');
                if ($ruleEnd < $dateStr) {
                    return false;
                }
            }
            return true;
        });

        return $record ? (float) $record->wap : null;
    }
}
