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

        static::creating(function (WarehouseBalance $balance) {
            if ($balance->stockable_type && str_contains($balance->stockable_type, 'FishType')) {
                $wap = FishTypeWap::getWapForFishType($balance->stockable_id, $balance->warehouse_id, $balance->date ?? now());
                if ($wap !== null) {
                    $balance->unit_price = $wap;
                    $balance->total_price = round($wap * (float)$balance->total_weight, 2);
                }
            }
        });

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

    protected $appends = ['wap', 'effective_total_price'];

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
     * Recalculate remaining weight and package count from operations and update is_finished.
     */
    public function recalculateRemaining(): void
    {
        $totalOutWeight = (float) $this->operations()->sum('weight');
        $this->remaining_weight = max(0, (float) $this->total_weight - $totalOutWeight);

        if ($this->stockable_type === 'PackageType' || (float) $this->package_count > 0) {
            $totalOutPackages = (float) $this->operations()->sum('package_count');
            $remainingPackages = max(0, (float) $this->package_count - $totalOutPackages);
            $this->is_finished = ($remainingPackages <= 0);
        } else {
            $this->is_finished = ($this->remaining_weight <= 0);
        }

        $this->save();
    }

    /**
     * Get WAP strictly from parameters/fish-management WAP Configuration.
     * Does NOT take WAP from record source.
     */
    public function getWapAttribute(): float
    {
        if ($this->stockable_type && str_contains($this->stockable_type, 'FishType')) {
            $wapConfig = FishTypeWap::getWapForFishType($this->stockable_id, $this->warehouse_id, $this->date ?? now());
            if ($wapConfig !== null) {
                return (float) $wapConfig;
            }
        }

        return 0.0;
    }

    /**
     * Get effective total price based strictly on WAP Configuration.
     */
    public function getEffectiveTotalPriceAttribute(): float
    {
        $wap = $this->wap;
        if ($wap > 0 && (float) $this->remaining_weight > 0) {
            return round($wap * (float) $this->remaining_weight, 2);
        }
        return 0.0;
    }
}
