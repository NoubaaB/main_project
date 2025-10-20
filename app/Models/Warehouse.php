<?php

namespace App\Models;

use App\Models\WarehouseFish;
use App\Models\TypeWarehouse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Warehouse extends Model
{
    /** @use HasFactory<\Database\Factories\WarehouseFactory> */
    use HasFactory ,SoftDeletes;

    protected $guarded = [];

    function warehouse_fishes() : HasMany {
        return $this->hasMany(WarehouseFish::class);
    }

    function type_warehouse() : BelongsTo {
        return $this->belongsTo(TypeWarehouse::class);
    }

    function calc() : array {
        $temp_arr = [];
        $count = 0;
        foreach ($this->warehouse_fishes as $warehouse_fishe) {
            $total = $warehouse_fishe->calc();
            
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
