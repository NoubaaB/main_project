<?php

namespace App\Models;

use App\Models\StockDetail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Stock extends Model
{
    /** @use HasFactory<\Database\Factories\StockFactory> */
    use HasFactory ,SoftDeletes;

    protected $guarded = [];

    function stock_details() : HasMany {
        return $this->hasMany(StockDetail::class);
    }
}
