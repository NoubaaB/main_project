<?php

namespace App\Models;

use App\Models\StockFishDetail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockDetailOperation extends Model
{
    use SoftDeletes;
    
    protected $guarded = [];

    function operatable() : MorphTo {
        return $this->morphTo();
    }

    function stock_fish_detail() : BelongsTo {
        return $this->belongsTo(StockFishDetail::class);
    }
}
