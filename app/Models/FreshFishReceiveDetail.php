<?php

namespace App\Models;

use App\Models\StockDetail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FreshFishReceiveDetail extends Model
{
    /** @use HasFactory<\Database\Factories\FreshFishReceiveDetailFactory> */
    use HasFactory , SoftDeletes;

    protected $guarded = [];

    public function stockDetail()
    {
        return $this->morphOne(StockDetail::class, 'detailable');
    }
}
