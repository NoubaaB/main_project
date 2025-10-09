<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\StockFishTrackingNumberOperation;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Package extends Model
{
    /** @use HasFactory<\Database\Factories\PackageFactory> */
    use HasFactory , SoftDeletes;

    protected $guarded = [];
    
    function stock_fish_tracking_number_operations() : HasMany {
        return $this->hasMany(StockFishTrackingNumberOperation::class);
    }
}
