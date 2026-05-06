<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class ClientPayment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'date', 'client_invoice_id', 'client_id',
        'payment_type', 'amount', 'reference',
        'bank_name', 'due_date', 'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
        'due_date' => 'date',
    ];

    public function clientInvoice()
    {
        return $this->belongsTo(ClientInvoice::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
