<?php

namespace App\Models;

use App\Models\Sell;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Client extends Model
{
    /** @use HasFactory<\Database\Factories\ClientFactory> */
    use HasFactory, SoftDeletes;
    protected $guarded = [];

    function sells(): BelongsToMany
    {
        return $this->belongsToMany(Sell::class, "sell_clients", "client_id", "sell_id")
            ->withPivot('sell_id', 'client_id');
    }
}
