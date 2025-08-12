<?php

namespace App\Models;

use App\Models\StockDetail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FreezeFishProcessDetail extends Model
{
    /** @use HasFactory<\Database\Factories\FreezeFishProcessDetailFactory> */
    use HasFactory ,SoftDeletes;

    public function stockDetail()
    {
        return $this->morphOne(StockDetail::class, 'detailable');
    }
}
