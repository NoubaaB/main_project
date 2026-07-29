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

        $dateStr = is_string($date) ? substr($date, 0, 10) : $date->format('Y-m-d');

        $record = static::where('fish_type_id', $fishTypeId)
            ->where('warehouse_id', $warehouseId)
            ->where('date_start', '<=', $dateStr)
            ->where(function ($q) use ($dateStr) {
                $q->whereNull('date_end')
                  ->orWhere('date_end', '>=', $dateStr);
            })
            ->orderBy('date_start', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        return $record ? (float) $record->wap : null;
    }
}
