<?php

namespace App\Models;

use App\Models\Ship;
use App\Models\Truck;
use App\Models\Origin;
use App\Models\Fishery;
use App\Models\Supplier;
use App\Models\ChargeCategory;
use Illuminate\Database\Eloquent\Model;
use App\Models\NonWarehouseStockableOperation;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\WarehouseStockableTrackingNumberOperation;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Charge extends Model
{
    /** @use HasFactory<\Database\Factories\ChargeFactory> */
    use HasFactory, SoftDeletes;

    protected $with = ['warehouse_stockable_tracking_number_operations', 'non_warehouse_stockable_operations'];

    protected $guarded = [];

    protected $appends = ['supplier_ids'];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'tracking_numbers' => 'array',
        'tracking_onp_numbers' => 'array',
        'note' => 'string',
        'total_price'  => 'float',
        'total_weight' => 'float',
        'total_package_count' => 'float',
    ];
    protected static function boot()
    {
        parent::boot();

        static::saved(function ($model) {
            // $model->calc();
        });

        static::saving(function ($model) {
        });

        static::deleted(function ($model) {
        });
    }

    function non_warehouse_stockable_operations() : HasMany {
        return $this->hasMany(NonWarehouseStockableOperation::class);
    }

    function charge_category() : BelongsTo {
        return $this->belongsTo(ChargeCategory::class, 'charge_category_id'); 
    }

    function warehouse_stockable_tracking_number_operations() : MorphMany {
        return $this->morphMany(WarehouseStockableTrackingNumberOperation::class, 'sourceable');
    }

    function suppliers() : BelongsToMany 
    {
        return $this->belongsToMany(Supplier::class, "charge_suppliers", "charge_id", "supplier_id");
    }

    function fishery() : BelongsTo {
        return $this->belongsTo(Fishery::class);
    }

    function ships(): BelongsToMany
    {
        return $this->belongsToMany(Ship::class, "charge_ships", "charge_id", "ship_id")
            ->withPivot('charge_id', 'ship_id');
    }

    function truck() : BelongsTo {
        return $this->belongsTo(Truck::class);
    }

    function origin() : BelongsTo {
        return $this->belongsTo(Origin::class, 'origin_id'); 
    }

    function supplier_process() : BelongsTo {
        return $this->belongsTo(Supplier::class, 'supplier_process_id'); 
    }

    protected function supplierIds(): Attribute
    {
        return Attribute::get(fn() => $this->suppliers->pluck('id')->toArray());
    }

    //private functions

    function calc() : void {
        $total_weight = 0.00;
        $total_price = 0.00;
        $total_package_count = 0.00;
        foreach ($this->warehouse_stockable_tracking_number_operations as $warehouse_stockable_tracking_number_operation) {
            $total_weight += $warehouse_stockable_tracking_number_operation->total_weight;
            $total_price += $warehouse_stockable_tracking_number_operation->total_price;
            $total_package_count += $warehouse_stockable_tracking_number_operation->total_package_count;
        }

        foreach ($this->non_warehouse_stockable_operations as $non_warehouse_stockable_operation) {
            $total_weight += $non_warehouse_stockable_operation->total_weight;
            $total_price += $non_warehouse_stockable_operation->total_price;
            $total_package_count += $non_warehouse_stockable_operation->total_package_count;
        }

        $t = [
            "total_weight" => $total_weight,
            "total_price" => $total_price,
            "total_package_count" => $total_package_count,
        ];
        $this->update($t);
    }
}
