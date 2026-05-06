<?php

namespace App\Models;

use App\Models\Charge;
use App\Models\NonWarehouseStockableOperation;
use App\Models\Shipping;
use App\Models\WarehouseStockableTrackingNumberOperation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Truck extends Model
{
    /** @use HasFactory<\Database\Factories\TruckFactory> */
    use HasFactory , SoftDeletes;

    protected $guarded = [];

    function charges() : BelongsToMany {
        return $this->belongsToMany(Charge::class , 'truck_charge');
    }

    function sells() : BelongsToMany {
        return $this->belongsToMany(Shipping::class, "truck_sell");
    }

    function warehouse_stockable_tracking_number_operations(): BelongsToMany
    {
        return $this->belongsToMany(WarehouseStockableTrackingNumberOperation::class, "truck_warehouse_stockable_tracking_number_operation");
    }

    function non_warehouse_stockable_operations(): BelongsToMany
    {
        return $this->belongsToMany(NonWarehouseStockableOperation::class, "truck_non_warehouse_stockable_operation");
    }
}

