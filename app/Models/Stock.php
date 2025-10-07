<?php

namespace App\Models;

use App\Models\StockDetail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Stock extends Model
{
    /** @use HasFactory<\Database\Factories\StockFactory> */
    use HasFactory ,SoftDeletes;

    protected $guarded = [];

    function stock_details() : HasMany {
        return $this->hasMany(StockDetail::class);
    }

    function fish_types() : HasMany {
        return $this->hasMany(FishType::class);
    }

    function calc() : array {
        $temp_arr = [];
        foreach ($this->stock_details as $stock_detail) {
            $total = $stock_detail->calc();
            
            $temp_arr[] = [
                "weight" => $total["total_weight"],
                "price" => $total["total_price"],
                "unit_price" => $total["unit_price"],
            ];
        }

        $this->update([
            "total_weight" => collect($temp_arr)->sum('weight'),
            "total_price" => collect($temp_arr)->sum('price'),
            "unit_price" => collect($temp_arr)->sum('unit_price') / ($count ?? 1),
        ]);

        return [
            "total_weight" => collect($temp_arr)->sum('weight'),
            "total_price" => collect($temp_arr)->sum('price'),
            "unit_price" => collect($temp_arr)->sum('unit_price') / (count($temp_arr) == 0 ? 1 : count($temp_arr)),
        ];
    }
}
