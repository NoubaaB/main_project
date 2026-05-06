<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class DetailInventory extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'inventory_id', 'detail_inventory_category_id', 'article_description',
        'stockable_type', 'stockable_id', 'weight', 'unit_price', 'total_price',
        'package_type_id', 'nbr_box', 'warehouse_id', 'warehouse_operation',
    ];

    protected $casts = [
        'weight' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'nbr_box' => 'decimal:2',
    ];

    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }

    public function detailInventoryCategory()
    {
        return $this->belongsTo(DetailInventoryCategory::class);
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
}
