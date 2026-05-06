<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class DetailWarehouseTransfer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'warehouse_transfer_id', 'warehouse_transfer_category_id',
        'stockable_type', 'stockable_id',
        'weight', 'source_warehouse_id', 'source_tracking_number',
        'destination_warehouse_id', 'package_type_id', 'nbr_box', 'truck_id',
    ];

    protected $casts = [
        'weight' => 'decimal:2',
        'nbr_box' => 'decimal:2',
    ];

    public function warehouseTransfer()
    {
        return $this->belongsTo(WarehouseTransfer::class);
    }

    public function warehouseTransferCategory()
    {
        return $this->belongsTo(WarehouseTransferCategory::class);
    }

    public function stockable()
    {
        return $this->morphTo();
    }

    public function sourceWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'source_warehouse_id');
    }

    public function destinationWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'destination_warehouse_id');
    }

    public function packageType()
    {
        return $this->belongsTo(PackageType::class);
    }

    public function truck()
    {
        return $this->belongsTo(Truck::class);
    }
}
