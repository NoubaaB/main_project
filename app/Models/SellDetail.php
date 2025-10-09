<?php

namespace App\Models;

use App\Models\Sell;
use App\Models\FishType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SellDetail extends Model
{
    /** @use HasFactory<\Database\Factories\SellDetailFactory> */
    use HasFactory , SoftDeletes;

    protected $guarded = [];

    function sell(): BelongsTo
    {
        return $this->belongsTo(Sell::class);
    }

    function detailable(): MorphTo
    {
        return $this->morphTo();
    }
    
    function fish_type(): BelongsTo
    {
        return $this->belongsTo(FishType::class, 'fish_type_id');
    }
}
