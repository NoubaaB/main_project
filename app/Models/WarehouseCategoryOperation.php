<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\NonWarehouseStockableOperation;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\WarehouseStockableTrackingNumberOperation;

class WarehouseCategoryOperation extends Model
{
    /** @use HasFactory<\Database\Factories\WarehouseCategoryOperationFactory> */
    use HasFactory ,SoftDeletes;

    protected $guarded = [];

    function warehouse_stockable_tracking_number_operations() : HasMany {
        return $this->hasMany(WarehouseStockableTrackingNumberOperation::class);
    }

    function non_warehouse_stockable_operations() : HasMany {
        return $this->hasMany(NonWarehouseStockableOperation::class);
    }
    
}
