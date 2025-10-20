<?php

namespace App\Models;

use App\Models\Warehouse;
use App\Models\WarehouseFishTrackingNumber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WarehouseFish extends Model
{
    /** @use HasFactory<\Database\Factories\WarehouseFishFactory> */
    use HasFactory , SoftDeletes;

    protected $guarded = [];

    function warehouse() : BelongsTo {
        return $this->belongsTo(Warehouse::class);
    }

    function warehouse_fish_tracking_numbers() : HasMany {
        return $this->hasMany(WarehouseFishTrackingNumber::class);
    }

    function calc(): array
    {
        $temp_arr = [];
        $count = 0;
        foreach ($this->warehouse_fish_tracking_numbers as $warehouse_fish_tracking_number) {
            $total = $warehouse_fish_tracking_number->calc();

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
