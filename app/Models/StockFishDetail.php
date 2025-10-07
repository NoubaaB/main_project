<?php

namespace App\Models;

use App\Models\FishType;
use App\Models\StockDetail;
use App\Models\StockDetailOperation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StockFishDetail extends Model
{
    /** @use HasFactory<\Database\Factories\StockFishDetailFactory> */
    use HasFactory , SoftDeletes;

    protected $guarded = [];

    function stock_detail() : BelongsTo {
        return $this->belongsTo(StockDetail::class);
    }
    
    function stock_detail_operations() : HasMany {
        return $this->hasMany(StockDetailOperation::class);
    }

    function detailable() : MorphTo {
        return $this->morphTo();
    }

    function calc(): array
    {
        $temp_arr = [
            "weight" => 0.00,
            "price" => 0.00,
            "unit_price" => 0.00
        ];
        foreach ($this->stock_detail_operations as $stock_detail_operation) {
                $temp_arr['weight'] += $stock_detail_operation->weight;
                $temp_arr['price'] += $stock_detail_operation->price;
                $temp_arr['unit_price'] += $stock_detail_operation->unit_price;
        }
        $this->update([
            "weight" => $this->detailable->weight - collect($temp_arr)->sum('weight'),
            "price" => $this->detailable->price - collect($temp_arr)->sum('price'),
            "unit_price" => collect($temp_arr)->sum('unit_price') / (count($temp_arr) == 0 ? 1 : count($temp_arr)),
        ]);
        
        return [
            "total_weight" => $this->detailable->weight - collect($temp_arr)->sum('weight'),
            "total_price" => $this->detailable->price - collect($temp_arr)->sum('price'),
            "unit_price" => collect($temp_arr)->sum('unit_price') / (count($temp_arr) == 0 ? 1 : count($temp_arr)),
        ];
    }
}
