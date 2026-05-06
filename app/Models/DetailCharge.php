<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class DetailCharge extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'charge_id', 'detail_charge_category_id', 'article_description',
        'stockable_type', 'stockable_id', 'weight', 'unit_price', 'total_price',
        'package_type_id', 'nbr_box', 'warehouse_id', 'warehouse_operation', 'truck_id', 'supplier_id',
    ];

    protected $casts = [
        'weight' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'nbr_box' => 'decimal:2',
    ];

    public function charge()
    {
        return $this->belongsTo(Charge::class);
    }

    public function detailChargeCategory()
    {
        return $this->belongsTo(DetailChargeCategory::class);
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

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
}
