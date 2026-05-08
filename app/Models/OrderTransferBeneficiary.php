<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderTransferBeneficiary extends Model
{
    protected $guarded = [];

    public function orderTransfer(): BelongsTo
    {
        return $this->belongsTo(OrderTransfer::class);
    }

    /**
     * Resolve the beneficiary model (Supplier or Client).
     */
    public function getBeneficiaryAttribute()
    {
        if ($this->beneficiary_type === 'supplier') {
            return Supplier::find($this->beneficiary_id);
        }
        if ($this->beneficiary_type === 'client') {
            return Client::find($this->beneficiary_id);
        }
        return null;
    }
}
