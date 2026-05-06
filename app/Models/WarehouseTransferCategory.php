<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class WarehouseTransferCategory extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name_fr', 'name_en', 'name_es', 'description',
        'article_type', 'fish_type_filter',
        'allow_weight', 'allow_package_type', 'allow_nbr_box',
        'allow_warehouse', 'allow_trucks',
    ];

    protected $casts = [
        'allow_weight' => 'boolean',
        'allow_package_type' => 'boolean',
        'allow_nbr_box' => 'boolean',
        'allow_warehouse' => 'boolean',
        'allow_trucks' => 'boolean',
    ];

    public function detailWarehouseTransfers()
    {
        return $this->hasMany(DetailWarehouseTransfer::class);
    }
}
