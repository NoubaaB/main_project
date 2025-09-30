<?php

namespace App\Models;

use App\Models\FishType;
use App\Models\StockDetail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FreezeFishProcessDetail extends Model
{
    /** @use HasFactory<\Database\Factories\FreezeFishProcessDetailFactory> */
    use HasFactory ,SoftDeletes;

    protected $guarded = [];

    public function stock_detail()
    {
        return $this->morphOne(StockDetail::class, 'detailable');
    }
    
    function fishType(): BelongsTo
    {
        return $this->belongsTo(FishType::class, 'fish_type_id');
    }
}
