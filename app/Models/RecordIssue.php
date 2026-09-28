<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecordIssue extends Model
{
    protected $fillable = [
        'record_type',
        'record_id',
        'description',
        'status',
        'resolution_note',
        'reported_by',
        'resolved_by',
        'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    /* ── Relationships ─────────────────────────────── */

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    /* ── Scopes ────────────────────────────────────── */

    public function scopeForRecord($query, string $type, int $id)
    {
        return $query->where('record_type', $type)->where('record_id', $id);
    }

    public function scopeOpen($query)
    {
        return $query->where('status', '!=', 'resolved');
    }

    /* ── Helpers ───────────────────────────────────── */

    /**
     * Map record_type to the permission key needed to flag issues.
     */
    public static function permissionKeyForType(string $type): string
    {
        return match ($type) {
            'Charge'     => 'charges.view',
            'Production' => 'production.view',
            'Shipping'   => 'shippings.view',
            default      => '',
        };
    }

    /**
     * Map record_type to the Eloquent model class.
     */
    public static function modelClassForType(string $type): ?string
    {
        return match ($type) {
            'Charge'     => Charge::class,
            'Production' => Production::class,
            'Shipping'   => Shipping::class,
            default      => null,
        };
    }
}
