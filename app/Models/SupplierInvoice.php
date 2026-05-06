<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupplierInvoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'date',
        'tracking_numbers',
        'total_price',
        'total_weight',
        'print_counter',
        'supplier_id',
    ];

    protected $casts = [
        'date' => 'date',
        'tracking_numbers' => 'array',
        'total_price' => 'decimal:2',
        'total_weight' => 'decimal:2',
        'print_counter' => 'integer',
    ];

    // ─── Relations ─────────────────────────────────────────────

    public function charges()
    {
        return $this->hasMany(Charge::class, 'invoice_id');
    }

    public function productions()
    {
        return $this->hasMany(Production::class, 'supplier_invoice_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function payments()
    {
        return $this->hasMany(SupplierPayment::class);
    }
}
