<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class WarehouseTransfer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'date', 'total_weight', 'transfer_tracking_number', 'description',
    ];

    protected $casts = [
        'date' => 'date',
        'total_weight' => 'decimal:2',
        'transfer_tracking_number' => 'array',
    ];

    public function detailWarehouseTransfers()
    {
        return $this->hasMany(DetailWarehouseTransfer::class);
    }

    public function trucks()
    {
        return $this->belongsToMany(Truck::class, 'warehouse_transfer_truck');
    }
}
