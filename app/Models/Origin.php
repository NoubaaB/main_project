<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Origin extends Model
{
    /** @use HasFactory<\Database\Factories\OriginFactory> */
    use HasFactory , SoftDeletes;
}
