<?php

namespace App\Models;

use App\Models\Warehouse;
use App\Models\WarehouseStockableTrackingNumber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class WarehouseStockable extends Model
{
    /** @use HasFactory<\Database\Factories\WarehouseStockableFactory> */
    use HasFactory , SoftDeletes;

    protected $guarded = [];

    function warehouse() : BelongsTo {
        return $this->belongsTo(Warehouse::class);
    }

    function warehouse_stockable_tracking_numbers() : HasMany {
        return $this->hasMany(WarehouseStockableTrackingNumber::class);
    }

    function stockable() : MorphTo {
        return $this->morphTo();
    }

    function calc(): array
    {
        $temp_arr = [];
        $count = 0;
        foreach ($this->warehouse_stockable_tracking_numbers as $warehouse_stockable_tracking_number) {
            $total = $warehouse_stockable_tracking_number->calc();

            $temp_arr[] = [
                "total_weight" => $total["total_weight"],
                "total_price" => $total["total_price"],
                "unit_price" => $total["unit_price"],
                "total_package_count" => $total["total_package_count"]
            ];
            $count++;
        }

        $t = [
            "total_weight" => (collect($temp_arr)->sum('total_weight')),
            "total_price" => (collect($temp_arr)->sum('total_price')),
            "total_package_count" => (collect($temp_arr)->sum('total_package_count')),
            "unit_price" => (collect($temp_arr)->sum('unit_price') / ($count == 0 ? 1 : $count)),
        ];
        $this->update($t);

        return $t;
    }
}
