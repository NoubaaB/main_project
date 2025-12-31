<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Formula extends Model
{
    /** @use HasFactory<\Database\Factories\FormulaFactory> */
    use HasFactory, SoftDeletes;

    protected $casts = [
        'expressions' => 'array',
        'allow_variables' => 'array',
        'enabled' => 'boolean',
    ];

    protected $guarded = [];
}
