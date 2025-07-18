<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FreshFishReceive extends Model
{
    /** @use HasFactory<\Database\Factories\FreshFishReceiveFactory> */
    use HasFactory , SoftDeletes;

    function provider() : BelongsTo
    {
        return $this->belongsTo(Provider::class); 
        
    }
}
