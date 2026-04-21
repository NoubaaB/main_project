<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SellAuditLog extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'changes' => 'array',
        'created_at' => 'datetime',
    ];

    public function sell(): BelongsTo
    {
        return $this->belongsTo(Sell::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
