<?php

namespace App\Models;

use App\Models\Stock;
use App\Models\StockFishTrackingNumber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StockFish extends Model
{
    /** @use HasFactory<\Database\Factories\StockFishFactory> */
    use HasFactory , SoftDeletes;

    protected $guarded = [];

    function stock() : BelongsTo {
        return $this->belongsTo(Stock::class);
    }

    function stock_fish_tracking_numbers() : HasMany {
        return $this->hasMany(StockFishTrackingNumber::class);
    }

    function calc(): array
    {
        $temp_arr = [];
        $count = 0;
        foreach ($this->stock_fish_tracking_numbers as $stock_fish_tracking_number) {
            $total = $stock_fish_tracking_number->calc();

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
