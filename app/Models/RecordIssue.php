<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecordIssue extends Model
{
    protected $fillable = [
        'record_type',
        'record_id',
        'bon_reference',
        'bon_date',
        'description',
        'status',
        'resolution_note',
        'reported_by',
        'bon_creator_id',
        'resolved_by',
        'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
        'bon_date'    => 'date',
    ];

    /* ── Relationships ─────────────────────────────── */

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function bonCreator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'bon_creator_id');
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

    public function scopeForCreator($query, int $userId)
    {
        return $query->where('bon_creator_id', $userId);
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

    /**
     * Safely format a tracking number that may be a string or array.
     */
    public static function formatTracking($val): ?string
    {
        if (is_array($val)) {
            $filtered = array_filter(array_map('strval', $val));
            return !empty($filtered) ? implode(', ', $filtered) : null;
        }
        return is_string($val) && trim($val) !== '' ? trim($val) : (is_numeric($val) ? (string) $val : null);
    }

    /**
     * Resolve the bon reference title, bon date, and creator id for a given record.
     */
    public static function resolveBonInfo(string $type, int $id): array
    {
        $ref = "Bon #{$id}";
        $date = null;
        $creatorId = null;

        $modelClass = self::modelClassForType($type);
        if ($modelClass) {
            $record = $modelClass::find($id);
            if ($record) {
                $date = $record->date ?? null;
                if ($type === 'Charge') {
                    $tracking = self::formatTracking($record->reception_tracking_number) ?: self::formatTracking($record->tracking_onp_numbers);
                    $ref = $tracking ? "Bon Charge #{$id} ({$tracking})" : "Bon Charge #{$id}";
                } elseif ($type === 'Production') {
                    $tracking = self::formatTracking($record->production_tracking_number)
                        ?: (self::formatTracking($record->reception_tracking_number) ?: self::formatTracking($record->tracking_onp_numbers));
                    $ref = $tracking ? "Bon Production #{$id} ({$tracking})" : "Bon Production #{$id}";
                } elseif ($type === 'Shipping') {
                    $tracking = self::formatTracking($record->shipping_tracking_number) ?: self::formatTracking($record->tracking_onp_numbers);
                    $ref = $tracking ? "Bon Expédition #{$id} ({$tracking})" : "Bon Expédition #{$id}";
                }
            }
        }

        // Try to identify creator (User B) from AuditLog
        $creatorLog = AuditLog::forRecord($type, $id)
            ->where('action', 'created')
            ->first();

        if ($creatorLog && $creatorLog->user_id) {
            $creatorId = $creatorLog->user_id;
        } else {
            // Fallback to any log on this record
            $anyLog = AuditLog::forRecord($type, $id)->first();
            if ($anyLog && $anyLog->user_id) {
                $creatorId = $anyLog->user_id;
            }
        }

        return [
            'reference'  => $ref,
            'date'       => $date,
            'creator_id' => $creatorId,
        ];
    }
}
