<?php

namespace App\Models;

use App\Models\Stock;
use App\Models\StockFishDetail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StockDetail extends Model
{
    /** @use HasFactory<\Database\Factories\StockDetailFactory> */
    use HasFactory ,SoftDeletes;

    protected $guarded = [];

    function stock() : BelongsTo {
        return $this->belongsTo(Stock::class);
    }

    function stock_fish_details() : HasMany {
        return $this->hasMany(StockFishDetail::class);
    }

    function calc(): array
    {
        $temp_arr = [];
        foreach ($this->stock_fish_details as $stock_fish_detail) {
            $total = $stock_fish_detail->calc();
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
