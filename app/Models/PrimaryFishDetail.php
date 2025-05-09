<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PrimaryFishDetail extends Model
{
    /** @use HasFactory<\Database\Factories\PrimaryFishDetailFactory> */
    use HasFactory , SoftDeletes;
}
