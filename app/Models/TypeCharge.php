<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TypeCharge extends Model
{
    /** @use HasFactory<\Database\Factories\TypeChargeFactory> */
    use HasFactory , SoftDeletes;
    
    protected $guarded = [];
}
