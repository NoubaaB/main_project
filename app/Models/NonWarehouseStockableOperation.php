<?php

namespace App\Models;

use App\Models\FishType;
use App\Models\PackageType;
use Illuminate\Database\Eloquent\Model;
use App\Models\WarehouseCategoryOperation;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class NonWarehouseStockableOperation extends Model
{
    /** @use HasFactory<\Database\Factories\NonWarehouseStockableOperationFactory> */
    use HasFactory , SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'unit_price'   => 'float',
        'total_price'  => 'float',
        'total_weight' => 'float',
        'total_package_count' => 'float',
        'total_weight_bill' => 'float',
    ];

    
    function charge() : BelongsTo {
        return $this->belongsTo(Charge::class);
    }

    //can be FishType or GuanoType or PackageType
    function stockable() : MorphTo {
        return $this->morphTo();
    }

    function package_type() : BelongsTo {
        return $this->belongsTo(PackageType::class);
    }

    function warehouse_category_operation(): BelongsTo
    {
        return $this->belongsTo(WarehouseCategoryOperation::class);
    }
}

