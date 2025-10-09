<?php

namespace App\Models;

use App\Models\StockFish;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\StockFishTrackingNumberOperation;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StockFishTrackingNumber extends Model
{
    /** @use HasFactory<\Database\Factories\StockFishTrackingNumberFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    function stock_fish() : BelongsTo {
        return $this->belongsTo(StockFish::class);
    }

    function stock_fish_tracking_number_operations() : HasMany {
        return $this->hasMany(StockFishTrackingNumberOperation::class);
    }

    function calc(): array
    {
        $total_weight = 0.00;
        $total_price = 0.00;
        $unit_price = 0.00;
        $total_package_count = 0.00;
        $count = 0;
        foreach ($this->stock_fish_tracking_number_operations as $stock_fish_tracking_number_operation) {
            if($stock_fish_tracking_number_operation->type_operation == 'in'){
                $total_weight+= $stock_fish_tracking_number_operation->total_weight;
                $total_price+= $stock_fish_tracking_number_operation->total_price;
                $unit_price+= $stock_fish_tracking_number_operation->unit_price;
                $total_package_count+= $stock_fish_tracking_number_operation->total_package_count;
            }else{
                $total_weight-= $stock_fish_tracking_number_operation->total_weight;
                $total_price-= $stock_fish_tracking_number_operation->total_price;
                $unit_price-= $stock_fish_tracking_number_operation->unit_price;
                $total_package_count-= $stock_fish_tracking_number_operation->total_package_count;
            }
            $count++;
        }

        $t = [
            "total_weight" => ceil($total_weight),
            "total_price" => ceil($total_price),
            "total_package_count" => ceil($total_package_count),
            "unit_price" => ceil($unit_price / ($count == 0? 1 : $count)),
            "out_of_stock" => $total_weight > 0 ? false : true,
        ];
        $this->update($t);

        return $t;
    }
    
}
