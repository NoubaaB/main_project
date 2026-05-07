<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StorageInvoiceLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'storage_invoice_id', 'label', 'description',
        'quantity_kg', 'quantity_days', 'unit_price',
        'total_amount', 'sort_order',
    ];

    protected $casts = [
        'quantity_kg' => 'decimal:2',
        'quantity_days' => 'decimal:2',
        'unit_price' => 'decimal:4',
        'total_amount' => 'decimal:2',
        'sort_order' => 'integer',
    ];

    public function storageInvoice()
    {
        return $this->belongsTo(StorageInvoice::class);
    }
}
