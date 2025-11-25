<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\WarehouseStockableTrackingNumberOperation;

class GuanoType extends Model
{
    /** @use HasFactory<\Database\Factories\GuanoTypeFactory> */
    use HasFactory , SoftDeletes;

    protected $guarded = [];

    function warehouse_stockable_tracking_number_operations(): MorphMany
    {
        return $this->morphMany(WarehouseStockableTrackingNumberOperation::class, 'stockable');
    }
}
