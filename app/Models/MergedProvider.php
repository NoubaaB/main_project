<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MergedProvider extends Model
{
    /** @use HasFactory<\Database\Factories\MergedProviderFactory> */
    use HasFactory ,SoftDeletes;

    protected $guarded = [];
    
}
