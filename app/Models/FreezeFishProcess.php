<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FreezeFishProcess extends Model
{
    /** @use HasFactory<\Database\Factories\FreezeFishProcessFactory> */
    use HasFactory , SoftDeletes;

    protected $guarded = [];
    
}
