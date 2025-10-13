<?php

namespace App\Models;

use App\Models\StockFish;
use App\Models\TypeStock;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Stock extends Model
{
    /** @use HasFactory<\Database\Factories\StockFactory> */
    use HasFactory ,SoftDeletes;

    protected $guarded = [];

    function stock_fishes() : HasMany {
        return $this->hasMany(StockFish::class);
    }

    function type_stock() : BelongsTo {
        return $this->belongsTo(TypeStock::class);
    }

    function calc() : array {
        $temp_arr = [];
        $count = 0;
        foreach ($this->stock_fishes as $stock_fishe) {
            $total = $stock_fishe->calc();
            
            $temp_arr[] = [
                "total_weight" => $total["total_weight"],
                "total_price" => $total["total_price"],
                "unit_price" => $total["unit_price"],
                "total_package_count" => $total["total_package_count"]
            ];
            $count++;
        }

        $t = [
            "total_weight" => ceil(collect($temp_arr)->sum('total_weight')),
            "total_price" => ceil(collect($temp_arr)->sum('total_price')),
            "total_package_count" => ceil(collect($temp_arr)->sum('total_package_count')),
            "unit_price" => ceil(collect($temp_arr)->sum('unit_price') / ($count == 0 ? 1 : $count)),
        ];
        $this->update($t);

        return $t;
    }
}
