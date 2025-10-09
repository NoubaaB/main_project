<?php

namespace App\Models;

use App\Models\Package;
use App\Models\StockFishTrackingNumber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StockFishTrackingNumberOperation extends Model
{
    /** @use HasFactory<\Database\Factories\StockFishTrackingNumberOperationFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    function stock_fish_tracking_number_operation() : BelongsTo {
        return $this->belongsTo(StockFishTrackingNumber::class);
    }

    function operationable() : MorphTo {
        return $this->morphTo();
    }

    function package() : BelongsTo {
        return $this->belongsTo(Package::class);
        
    }

    function calc(): array
    {
        if($this->type_operation == 'out'){
            return [
                "total_weight" => 0.00,
                "total_price" => 0.00,
                "unit_price" => 0.00,
                "total_package_count" => 0.00
            ];
        }else{
            return [
                "total_weight" => $this->total_weight,
                "total_price" => $this->total_price,
                "unit_price" => $this->unit_price,
                "total_package_count" => $this->total_package_count
            ];
        }
            
    }
}
