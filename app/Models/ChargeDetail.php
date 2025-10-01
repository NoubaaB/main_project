<?php

namespace App\Models;

use App\Models\FishType;
use App\Models\StockDetail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class ChargeDetail extends Model
{
    /** @use HasFactory<\Database\Factories\ChargeDetailFactory> */
    use HasFactory , SoftDeletes;

    protected $guarded = [];

    public function stock_detail() : MorphOne
    {
        return $this->morphOne(StockDetail::class, 'detailable');
    }

    function fish_type(): BelongsTo
    {
        return $this->belongsTo(FishType::class, 'fish_type_id');
    }
}
