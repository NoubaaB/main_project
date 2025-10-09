<?php

namespace App\Models;

use App\Models\Sell;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TypeSell extends Model
{
    /** @use HasFactory<\Database\Factories\TypeSellFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    function sells(): HasMany
    {
        return $this->hasMany(Sell::class, 'type_sell_id');
    }
}
