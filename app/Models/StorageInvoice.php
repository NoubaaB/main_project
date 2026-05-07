<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class StorageInvoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'warehouse_id', 'invoice_number', 'year', 'month',
        'total_stock_in_kg', 'total_stock_out_kg',
        'manipulation_in_amount', 'manipulation_out_amount',
        'sejour_amount', 'total_amount',
        'service_unit_price', 'daily_rate_per_kg', 'weight_unit',
        'status', 'print_counter', 'generated_by',
    ];

    protected $casts = [
        'year' => 'integer',
        'month' => 'integer',
        'total_stock_in_kg' => 'decimal:2',
        'total_stock_out_kg' => 'decimal:2',
        'manipulation_in_amount' => 'decimal:2',
        'manipulation_out_amount' => 'decimal:2',
        'sejour_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'service_unit_price' => 'decimal:4',
        'daily_rate_per_kg' => 'decimal:4',
        'weight_unit' => 'integer',
        'print_counter' => 'integer',
    ];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function generatedByUser()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function lines()
    {
        return $this->hasMany(StorageInvoiceLine::class)->orderBy('sort_order');
    }
}
