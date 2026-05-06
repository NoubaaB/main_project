<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class DetailShipping extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'shipping_id', 'detail_shipping_category_id', 'article_description',
        'stockable_type', 'stockable_id', 'weight', 'unit_price', 'total_price',
        'package_type_id', 'nbr_box', 'warehouse_id', 'warehouse_operation', 'truck_id',
    ];

    protected $casts = [
        'weight' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'nbr_box' => 'decimal:2',
    ];

    public function shipping()
    {
        return $this->belongsTo(Shipping::class);
    }

    public function detailShippingCategory()
    {
        return $this->belongsTo(DetailShippingCategory::class);
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
