<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CostConfiguration extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name_fr',
        'name_en',
        'name_es',
        'unit',
        'variables',
        'formula',
        'description',
        'enabled',
        'created_by',
    ];

    protected $casts = [
        'variables' => 'array',
        'enabled'   => 'boolean',
    ];

    /**
     * User who created this cost configuration.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
