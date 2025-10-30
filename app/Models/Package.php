<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\WarehouseStockableTrackingNumberOperation;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Package extends Model
{
    /** @use HasFactory<\Database\Factories\PackageFactory> */
    use HasFactory , SoftDeletes;

    protected $guarded = [];
    
    function warehouse_stockable_tracking_number_operations() : HasMany {
        return $this->hasMany(WarehouseStockableTrackingNumberOperation::class);
    }
}
