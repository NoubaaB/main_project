<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class ClientFiscalYearClosing extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'year', 'closing_date', 'total_invoices',
        'total_payments', 'carried_forward', 'is_closed',
    ];

    protected $casts = [
        'closing_date' => 'date',
        'total_invoices' => 'decimal:2',
        'total_payments' => 'decimal:2',
        'carried_forward' => 'decimal:2',
        'is_closed' => 'boolean',
    ];

    public function lines()
    {
        return $this->hasMany(ClientFiscalYearClosingLine::class);
    }
}
