<?php

namespace App\Models;

use App\Models\Stock;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StockDetail extends Model
{
    /** @use HasFactory<\Database\Factories\StockDetailFactory> */
    use HasFactory ,SoftDeletes;

    function stock() : BelongsTo {
        return $this->belongsTo(Stock::class);
    }

    public function detailable()
    {
        return $this->morphTo();
    }
}
