<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryAdjustment extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'date' => 'date:Y-m-d',
    ];

    function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    function warehouse_stockable_tracking_number_operations(): MorphMany
    {
        return $this->morphMany(WarehouseStockableTrackingNumberOperation::class, 'sourceable');
    }
}
