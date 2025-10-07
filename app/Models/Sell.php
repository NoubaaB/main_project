<?php

namespace App\Models;

use App\Models\StockDetailOperation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Sell extends Model
{
    /** @use HasFactory<\Database\Factories\SellFactory> */
    use HasFactory , SoftDeletes;

    protected $guarded = [];

    function stock_detail_operation() : MorphOne {
        return $this->morphOne(StockDetailOperation::class, 'operatable');
    }
}
