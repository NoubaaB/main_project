<?php

namespace App\Models;

use App\Models\WarehouseStockable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\WarehouseStockableTrackingNumberOperation;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WarehouseStockableTrackingNumber extends Model
{
    /** @use HasFactory<\Database\Factories\WarehouseStockableTrackingNumberFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        "tracking_numbers" => "array"
    ];

    protected static function boot()
    {
        parent::boot();

        static::saved(function ($model) {
            // $model->calc();
        });

        static::saving(function ($model) {});

        static::deleted(function ($model) {});
    }

    function warehouse_stockable() : BelongsTo {
        return $this->belongsTo(WarehouseStockable::class);
    }

    function warehouse_stockable_tracking_number_operations() : HasMany {
        return $this->hasMany(WarehouseStockableTrackingNumberOperation::class);
    }

    function calc(): array
    {
        $total_weight = 0.00;
        $total_price = 0.00;
        $unit_price = 0.00;
        $total_package_count = 0.00;
        $count = 0;
        foreach ($this->warehouse_stockable_tracking_number_operations as $warehouse_stockable_tracking_number_operation) {
            if($warehouse_stockable_tracking_number_operation->type_operation == 'in'){
                $total_weight+= $warehouse_stockable_tracking_number_operation->total_weight;
                $total_price+= $warehouse_stockable_tracking_number_operation->total_price;
                $unit_price+= $warehouse_stockable_tracking_number_operation->unit_price;
                $total_package_count+= $warehouse_stockable_tracking_number_operation->total_package_count;
            }else{
                $total_weight-= $warehouse_stockable_tracking_number_operation->total_weight;
                $total_price-= $warehouse_stockable_tracking_number_operation->total_price;
                $unit_price-= $warehouse_stockable_tracking_number_operation->unit_price;
                $total_package_count-= $warehouse_stockable_tracking_number_operation->total_package_count;
            }
            $count++;
        }

        $t = [
            "total_weight" => ($total_weight),
            "total_price" => ($total_price),
            "total_package_count" => ($total_package_count),
            "unit_price" => ($unit_price / ($count == 0? 1 : $count)),
        ];
        $this->update($t);

        return $t;
    }
    
}
