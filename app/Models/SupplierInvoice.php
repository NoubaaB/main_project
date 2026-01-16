<?php

namespace App\Models;

use App\Models\Charge;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SupplierInvoice extends Model
{
    /** @use HasFactory<\Database\Factories\SupplierInvoiceFactory> */
    use HasFactory ,SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        "tracking_numbers"=>"array",
        "supplier_ids"=>"array"
    ];

    protected $appends = ['charge_ids'];

    function charges() : HasMany {
        return $this->hasMany(Charge::class,"invoice_id");
    }

    protected function chargeIds(): Attribute
    {
        return Attribute::get(fn() => $this->charges->pluck('id')->toArray());
    }

    /**
     * Generate next $count tracking numbers from latest invoice.
     */
    protected function generateNextTrackingNumbers(int $count = 1): array
    {
        // Try DB-level query for invoices with non-empty JSON (MySQL/PG may support)
        try {
            // MySQL: JSON_LENGTH(tracking_numbers) > 0
            $latest = SupplierInvoice::whereRaw('JSON_LENGTH(tracking_numbers) > 0')
                ->orderByDesc('id')
                ->first();
        } catch (\Throwable $e) {
            // Fallback if DB doesn't support JSON_LENGTH or query fails
            $latest = SupplierInvoice::orderByDesc('id')->get()->first(function ($inv) {
                return is_array($inv->tracking_numbers) && count($inv->tracking_numbers) > 0;
            });
        }

        if (! $latest) {
            // No previous value — choose startup format. Adjust start format as needed.
            // Default: "1", "2", ... (string)
            return array_map(fn($i) => (string)$i, range(1, $count));
        }

        // Get last issued tracking number from the latest invoice's array
        $last = collect($latest->tracking_numbers)->last();

        // Ensure it's a string
        $current = (string) ($last ?? '');

        $result = [];
        for ($i = 0; $i < $count; $i++) {
            $current = $this->incrementTrackingNumber($current);
            $result[] = $current;
        }

        return $result;
    }

    /**
     * Increment a single tracking string.
     * - If string ends with digits: increment that numeric suffix and preserve zero padding & prefix.
     * - If string has no trailing digits: append "1".
     *
     * Examples:
     *  "000001" => "000002"
     *  "1" => "2"
     *  "in-01" => "in-02"
     *  "INV99" => "INV100"
     *  "NO_NUM" => "NO_NUM1"
     */
    protected function incrementTrackingNumber(string $value): string
    {
        if ($value === '') {
            return '1';
        }

        // Match last group of digits if present
        if (preg_match('/^(.*?)(\d+)$/', $value, $m)) {
            $prefix = $m[1];
            $digits = $m[2];

            $len = strlen($digits);
            // Use bc or int depending on size — for typical lengths int is fine
            $num = (int)$digits;
            $num++;

            $newDigits = str_pad((string)$num, $len, '0', STR_PAD_LEFT);

            return $prefix . $newDigits;
        }

        // No trailing digits: append '1'
        return $value . '1';
    }
}
