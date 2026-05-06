<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'date', 'inventory_tracking_number', 'description',
        'total_weight', 'total_price',
    ];

    protected $casts = [
        'date' => 'date',
        'total_weight' => 'decimal:2',
        'total_price' => 'decimal:2',
        'inventory_tracking_number' => 'array',
    ];

    public function detailInventories()
    {
        return $this->hasMany(DetailInventory::class);
    }
}
