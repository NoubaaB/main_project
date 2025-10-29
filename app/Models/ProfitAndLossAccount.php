<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProfitAndLossAccount extends Model
{
    /** @use HasFactory<\Database\Factories\ProfitAndLossAccountFactory> */
    use HasFactory , SoftDeletes;

    function accountable() : MorphTo {
        return $this->morphTo();
    }
}
