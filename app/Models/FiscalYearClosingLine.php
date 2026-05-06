<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class FiscalYearClosingLine extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'fiscal_year_closing_id', 'supplier_id',
        'invoice_total', 'payment_total', 'balance',
    ];

    protected $casts = [
        'invoice_total' => 'decimal:2',
        'payment_total' => 'decimal:2',
        'balance' => 'decimal:2',
    ];

    public function closing()
    {
        return $this->belongsTo(FiscalYearClosing::class, 'fiscal_year_closing_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
}
