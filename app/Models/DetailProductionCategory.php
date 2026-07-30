<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class DetailProductionCategory extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name_fr', 'name_en', 'name_es', 'description',
        'article_type', 'fish_type_filter', 'allow_weight', 'allow_unit_price', 'allow_price',
        'allow_package_type', 'allow_nbr_box', 'allow_warehouse',
        'warehouse_operation', 'allow_detail_truck_id', 'include_in_invoice', 'allow_secondary_packaging', 'production_category_id',
    ];

    protected $casts = [
        'allow_weight' => 'boolean',
        'allow_unit_price' => 'boolean',
        'allow_price' => 'boolean',
        'allow_package_type' => 'boolean',
        'allow_nbr_box' => 'boolean',
        'allow_warehouse' => 'boolean',
        'allow_detail_truck_id' => 'boolean',
        'include_in_invoice' => 'boolean',
        'allow_secondary_packaging' => 'boolean',
    ];

    public function productionCategory()
    {
        return $this->belongsTo(ProductionCategory::class);
    }
}
