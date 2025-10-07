<?php

namespace App\Models;

use App\Models\FishType;
use App\Models\StockFishDetail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductionDetail extends Model
{
    /** @use HasFactory<\Database\Factories\ProductionDetailFactory> */
    use HasFactory ,SoftDeletes;

    protected $guarded = [];

    public function stock_fish_details(): MorphMany
    {
        return $this->morphMany(StockFishDetail::class, 'detailable');
    }
    
    function fish_type(): BelongsTo
    {
        return $this->belongsTo(FishType::class, 'fish_type_id');
    }
}
