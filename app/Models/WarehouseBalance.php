<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class WarehouseBalance extends Model
{
    use HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        $recalc = function (WarehouseBalance $balance) {
            if ($balance->warehouse) {
                $balance->warehouse->recalculateTotalWeight();
            }
        };

        static::created($recalc);
        static::updated($recalc);
        static::deleted(function (WarehouseBalance $balance) {
            // After delete, warehouse relation may still be loaded
            $warehouse = Warehouse::find($balance->warehouse_id);
            if ($warehouse) {
                $warehouse->recalculateTotalWeight();
            }
        });
    }

    protected $fillable = [
        'warehouse_id', 'stockable_type', 'stockable_id',
        'reception_tracking_number', 'warehouse_operation',
        'date', 'total_weight', 'remaining_weight',
        'total_price', 'unit_price', 'package_count', 'package_type_id',
        'supplier_name', 'origin_name', 'fishery_name', 'client_name',
        'source_type', 'source_id', 'is_finished',
    ];

    protected $casts = [
        'date' => 'date',
        'total_weight' => 'decimal:2',
        'remaining_weight' => 'decimal:2',
        'total_price' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'package_count' => 'decimal:2',
        'is_finished' => 'boolean',
    ];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function stockable()
    {
        return $this->morphTo();
    }

    public function packageType()
    {
        return $this->belongsTo(PackageType::class);
    }

    public function operations()
    {
        return $this->hasMany(WarehouseBalanceOperation::class);
    }

    /**
     * Recalculate remaining weight from operations and update is_finished.
     */
    public function recalculateRemaining(): void
    {
        $totalOut = $this->operations()->sum('weight');
        $this->remaining_weight = max(0, $this->total_weight - $totalOut);
        $this->is_finished = $this->remaining_weight <= 0;
        $this->save();
    }

    /**
     * Get computed WAP (unit price).
     */
    public function getWapAttribute(): float
    {
        return $this->total_weight > 0
            ? round($this->total_price / $this->total_weight, 2)
            : 0;
    }
}
