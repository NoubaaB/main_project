<?php

namespace App\Models;

use App\Models\WarehouseFish;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\WarehouseFishTrackingNumberOperation;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WarehouseFishTrackingNumber extends Model
{
    /** @use HasFactory<\Database\Factories\WarehouseFishTrackingNumberFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected static function boot()
    {
        parent::boot();

        static::saved(function ($model) {
            // $model->calc();
        });

        static::saving(function ($model) {});

        static::deleted(function ($model) {});
    }

    function warehouse_fish() : BelongsTo {
        return $this->belongsTo(WarehouseFish::class);
    }

    function warehouse_fish_tracking_number_operations() : HasMany {
        return $this->hasMany(WarehouseFishTrackingNumberOperation::class);
    }

    function calc(): array
    {
        $total_weight = 0.00;
        $total_price = 0.00;
        $unit_price = 0.00;
        $total_package_count = 0.00;
        $count = 0;
        foreach ($this->warehouse_fish_tracking_number_operations as $warehouse_fish_tracking_number_operation) {
            if($warehouse_fish_tracking_number_operation->type_operation == 'in'){
                $total_weight+= $warehouse_fish_tracking_number_operation->total_weight;
                $total_price+= $warehouse_fish_tracking_number_operation->total_price;
                $unit_price+= $warehouse_fish_tracking_number_operation->unit_price;
                $total_package_count+= $warehouse_fish_tracking_number_operation->total_package_count;
            }else{
                $total_weight-= $warehouse_fish_tracking_number_operation->total_weight;
                $total_price-= $warehouse_fish_tracking_number_operation->total_price;
                $unit_price-= $warehouse_fish_tracking_number_operation->unit_price;
                $total_package_count-= $warehouse_fish_tracking_number_operation->total_package_count;
            }
            $count++;
        }

        $t = [
            "total_weight" => ceil($total_weight),
            "total_price" => ceil($total_price),
            "total_package_count" => ceil($total_package_count),
            "unit_price" => ceil($unit_price / ($count == 0? 1 : $count)),
        ];
        $this->update($t);

        return $t;
    }
    
}
