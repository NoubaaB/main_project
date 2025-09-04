<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChargeType extends Model
{
    /** @use HasFactory<\Database\Factories\ChargeTypeFactory> */
    use HasFactory , SoftDeletes;
    
    protected $guarded = [];
}
