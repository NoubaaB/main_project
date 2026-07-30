<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class DetailProduction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'production_id', 'detail_production_category_id', 'article_description',
        'stockable_type', 'stockable_id', 'weight', 'unit_price', 'total_price',
        'secondary_packaging_cost', 'secondary_packaging_details',
        'package_type_id', 'nbr_box', 'warehouse_id', 'warehouse_operation', 'truck_id',
    ];

    protected $casts = [
        'weight' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'secondary_packaging_cost' => 'decimal:2',
        'secondary_packaging_details' => 'array',
        'nbr_box' => 'decimal:2',
    ];

    public function production()
    {
        return $this->belongsTo(Production::class);
    }

    public function detailProductionCategory()
    {
        return $this->belongsTo(DetailProductionCategory::class);
    }

    public function stockable()
    {
        return $this->morphTo();
    }

    public function packageType()
    {
        return $this->belongsTo(PackageType::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function truck()
    {
        return $this->belongsTo(Truck::class);
    }
}
