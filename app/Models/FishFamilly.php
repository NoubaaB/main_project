<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FishFamilly extends Model
{
    /** @use HasFactory<\Database\Factories\FishFamillyFactory> */
    use HasFactory , SoftDeletes;

    protected $guarded = [];
}
