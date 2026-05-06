<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientFiscalYearClosingLine extends Model
{
    protected $fillable = [
        'client_fiscal_year_closing_id', 'client_id',
        'total_invoices', 'total_payments',
        'carry_forward', 'balance',
    ];

    protected $casts = [
        'total_invoices' => 'decimal:2',
        'total_payments' => 'decimal:2',
        'carry_forward' => 'decimal:2',
        'balance' => 'decimal:2',
    ];

    public function closing()
    {
        return $this->belongsTo(ClientFiscalYearClosing::class, 'client_fiscal_year_closing_id');
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
