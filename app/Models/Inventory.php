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

    protected $appends = [
        'inventory_tracking_numbers',
    ];

    public function getInventoryTrackingNumbersAttribute()
    {
        return $this->inventory_tracking_number ?? [];
    }

    public function detailInventories()
    {
        return $this->hasMany(DetailInventory::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
