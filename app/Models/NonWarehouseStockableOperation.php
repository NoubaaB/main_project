<?php

namespace App\Models;

use App\Models\Charge;
use App\Models\PackageType;
use App\Models\Truck;
use App\Models\WarehouseCategoryOperation;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class NonWarehouseStockableOperation extends Model
{
    /** @use HasFactory<\Database\Factories\NonWarehouseStockableOperationFactory> */
    use HasFactory , SoftDeletes;

    protected $guarded = [];
    
    protected $hidden = ['trucks'];

    protected $appends = ['truck_ids'];

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

    function sell() : BelongsTo {
        return $this->belongsTo(\App\Models\Sell::class);
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

    function trucks(): BelongsToMany
    {
        return $this->belongsToMany(Truck::class, "truck_non_warehouse_stockable_operation");
    }
    
    protected function truckIds(): Attribute
    {
        return Attribute::get(fn() => $this->trucks->pluck('id')->toArray());
    }
}

