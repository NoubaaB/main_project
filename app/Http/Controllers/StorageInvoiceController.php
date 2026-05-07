<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Warehouse;
use App\Models\WarehouseBalance;
use App\Models\WarehouseBalanceOperation;
use App\Models\SejourRate;
use App\Models\StorageInvoice;
use App\Models\StorageInvoiceLine;
use App\Traits\BroadcastsModelChanges;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StorageInvoiceController extends Controller
{
    use BroadcastsModelChanges;

    /**
     * List storage invoices, filtered by warehouse_id and/or year.
     */
    public function index(Request $request)
    {
        $query = StorageInvoice::with(['warehouse', 'generatedByUser', 'lines']);

        if ($request->warehouse_id) {
            $query->where('warehouse_id', $request->warehouse_id);
        }
        if ($request->year) {
            $query->where('year', $request->year);
        }

        $invoices = $query->orderBy('year', 'desc')->orderBy('month', 'desc')->get();

        return response()->json(['storage_invoices' => $invoices]);
    }

    /**
     * Show a single storage invoice with lines.
     */
    public function show($id)
    {
        $invoice = StorageInvoice::with(['warehouse', 'generatedByUser', 'lines'])->findOrFail($id);
        return response()->json(['storage_invoice' => $invoice]);
    }

    /**
     * Preview the invoice calculation for a warehouse+year+month without saving.
     */
    public function preview(Request $request)
    {
        $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'year' => 'required|integer|min:2020|max:2099',
            'month' => 'required|integer|min:1|max:12',
        ]);

        $warehouse = Warehouse::findOrFail($request->warehouse_id);

        if (!$warehouse->has_sejour) {
            return response()->json(['error' => 'warehouse_no_sejour'], 422);
        }

        $calculation = $this->calculateInvoice($warehouse, $request->year, $request->month);

        return response()->json(['preview' => $calculation]);
    }

    /**
     * Generate (create) a storage invoice for a warehouse+year+month.
     */
    public function generate(Request $request)
    {
        $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'year' => 'required|integer|min:2020|max:2099',
            'month' => 'required|integer|min:1|max:12',
        ]);

        $warehouse = Warehouse::findOrFail($request->warehouse_id);

        if (!$warehouse->has_sejour) {
            return response()->json(['error' => 'warehouse_no_sejour'], 422);
        }

        // If invoice already exists for this warehouse+month, delete it so we can regenerate
        $existing = DB::table('storage_invoices')
            ->where('warehouse_id', $warehouse->id)
            ->where('year', $request->year)
            ->where('month', $request->month)
            ->first();

        if ($existing) {
            DB::table('storage_invoice_lines')->where('storage_invoice_id', $existing->id)->delete();
            DB::table('storage_invoices')->where('id', $existing->id)->delete();
        }

        $calculation = $this->calculateInvoice($warehouse, $request->year, $request->month);

        // Generate invoice number: {MM}V{SEQ}
        $invoiceNumber = $this->generateInvoiceNumber($request->year, $request->month);

        DB::beginTransaction();
        try {
            $invoice = StorageInvoice::create([
                'warehouse_id' => $warehouse->id,
                'invoice_number' => $invoiceNumber,
                'year' => $request->year,
                'month' => $request->month,
                'total_stock_in_kg' => $calculation['total_stock_in_kg'],
                'total_stock_out_kg' => $calculation['total_stock_out_kg'],
                'manipulation_in_amount' => $calculation['manipulation_in_amount'],
                'manipulation_out_amount' => $calculation['manipulation_out_amount'],
                'sejour_amount' => $calculation['sejour_amount'],
                'total_amount' => $calculation['total_amount'],
                'service_unit_price' => $calculation['service_unit_price'],
                'daily_rate_per_kg' => $calculation['daily_rate_per_kg'],
                'weight_unit' => $calculation['weight_unit'],
                'status' => 'finalized',
                'print_counter' => 0,
                'generated_by' => Auth::id(),
            ]);

            // Create line items
            foreach ($calculation['lines'] as $i => $line) {
                StorageInvoiceLine::create([
                    'storage_invoice_id' => $invoice->id,
                    'label' => $line['label'],
                    'description' => $line['description'] ?? null,
                    'quantity_kg' => $line['quantity_kg'],
                    'quantity_days' => $line['quantity_days'],
                    'unit_price' => $line['unit_price'],
                    'total_amount' => $line['total_amount'],
                    'sort_order' => $i,
                ]);
            }

            DB::commit();

            $invoice->load(['warehouse', 'generatedByUser', 'lines']);
            $this->broadcastChange('StorageInvoice', 'created', $invoice->id, $invoice->toArray());

            return response()->json(['storage_invoice' => $invoice], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete a storage invoice.
     */
    public function destroy($id)
    {
        $invoice = StorageInvoice::findOrFail($id);
        $invoiceId = $invoice->id;
        // Force delete (not soft) because the unique constraint (warehouse_id, year, month)
        // would block re-creating an invoice for the same period if a soft-deleted row exists.
        $invoice->forceDelete();
        $this->broadcastChange('StorageInvoice', 'deleted', $invoiceId);

        return response()->json(['message' => 'Deleted']);
    }

    /**
     * Increment print counter.
     */
    public function incrementPrintCounter($id)
    {
        $invoice = StorageInvoice::findOrFail($id);
        $invoice->increment('print_counter');

        return response()->json(['print_counter' => $invoice->print_counter]);
    }

    // ===================================================================
    //  CORE CALCULATION LOGIC
    // ===================================================================

    /**
     * Calculate the storage invoice for a given warehouse, year, month.
     *
     * Logic:
     * - Manipulation IN: total stock_in KG this month × service_unit_price
     * - Manipulation OUT: total stock_out KG this month × service_unit_price
     * - Séjour: For each stock_out operation this month, calculate the days
     *   the goods stayed (from their stock_in date or month start, whichever is later,
     *   to the stock_out date). For remaining stock at end of month, calculate
     *   full month days.
     */
    private function calculateInvoice(Warehouse $warehouse, int $year, int $month): array
    {
        $monthStart = Carbon::create($year, $month, 1)->startOfDay();
        $monthEnd = $monthStart->copy()->endOfMonth();
        $daysInMonth = $monthEnd->day;

        // Get the active séjour rate for this period
        $rate = $this->getActiveRate($warehouse->id, $monthStart, $monthEnd);

        $serviceUnitPrice = $rate ? (float)$rate->service_unit_price : 0;
        $dailyRate = $rate ? (float)$rate->daily_rate_per_kg : 0;
        $weightUnit = $rate ? (int)$rate->weight_unit : 1000;

        // ── 1. STOCK IN this month ──
        $stockInThisMonth = WarehouseBalance::where('warehouse_id', $warehouse->id)
            ->where('warehouse_operation', 'stock_in')
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->whereNull('deleted_at')
            ->sum('total_weight');

        $totalStockInKg = (float)$stockInThisMonth;

        // ── 2. STOCK OUT this month ──
        // Get all stock_out operations in this month for this warehouse
        $stockOutOps = WarehouseBalanceOperation::whereHas('warehouseBalance', function ($q) use ($warehouse) {
                $q->where('warehouse_id', $warehouse->id)->whereNull('deleted_at');
            })
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->whereNull('deleted_at')
            ->get();

        $totalStockOutKg = (float)$stockOutOps->sum('weight');

        // ── 3. MANIPULATION FEES ──
        $manipulationInAmount = round($totalStockInKg * $serviceUnitPrice, 2);
        $manipulationOutAmount = round($totalStockOutKg * $serviceUnitPrice, 2);

        // ── 4. SÉJOUR CALCULATION ──
        // For each stock_out, calculate how many days it stayed in the warehouse THIS month.
        // Then for remaining stock, calculate full month days.
        $sejourLines = [];
        $sejourTotal = 0;

        // Group stock_out operations and calculate days in warehouse for each
        foreach ($stockOutOps as $op) {
            $balance = $op->warehouseBalance;
            if (!$balance) continue;

            $stockInDate = Carbon::parse($balance->date);
            // The effective start for this month is the later of: stock_in date or month start
            $effectiveStart = $stockInDate->greaterThan($monthStart) ? $stockInDate : $monthStart;
            $exitDate = Carbon::parse($op->date);

            // Days stayed = from effective start to exit date (inclusive)
            // Use startOfDay to avoid floating-point from time components
            $days = (int)$effectiveStart->copy()->startOfDay()->diffInDays($exitDate->copy()->startOfDay()) + 1;
            $days = min($days, $daysInMonth);

            $weight = (float)$op->weight;
            $lineTotal = round($weight * $days * $dailyRate, 2);
            $sejourTotal += $lineTotal;

            $sejourLines[] = [
                'weight' => $weight,
                'days' => $days,
                'amount' => $lineTotal,
                'exit_date' => $exitDate->format('Y-m-d'),
            ];
        }

        // Remaining stock at end of month: stock that was in the warehouse for the whole month
        // = all stock_in balances that have remaining_weight > 0 at end of month
        // We need to calculate: for each balance with remaining stock, how many days it was in warehouse this month
        $activeBalances = WarehouseBalance::where('warehouse_id', $warehouse->id)
            ->where('warehouse_operation', 'stock_in')
            ->where('remaining_weight', '>', 0)
            ->where('date', '<=', $monthEnd->format('Y-m-d'))
            ->whereNull('deleted_at')
            ->get();

        $remainingKg = 0;
        $remainingSejourTotal = 0;
        $remainingLines = [];

        foreach ($activeBalances as $balance) {
            $stockInDate = Carbon::parse($balance->date);
            // effectiveStart = later of (entry date, month start)
            // e.g. entered 05-05-2026 for May invoice → start from day 5
            // e.g. entered 02-03-2026 for May invoice → start from day 1 (month start)
            $effectiveStart = $stockInDate->greaterThan($monthStart) ? $stockInDate : $monthStart;
            // Days remaining stock stayed = from effective start day to last day of month (inclusive)
            // Use simple integer arithmetic: daysInMonth - startDay + 1
            $days = $daysInMonth - $effectiveStart->day + 1;
            $days = max(1, min($days, $daysInMonth));

            $remainingWeight = (float)$balance->remaining_weight;
            $lineAmount = round($remainingWeight * $days * $dailyRate, 2);
            $remainingKg += $remainingWeight;
            $remainingSejourTotal += $lineAmount;

            $remainingLines[] = [
                'weight' => $remainingWeight,
                'days' => $days,
                'amount' => $lineAmount,
                'entry_date' => $stockInDate->format('Y-m-d'),
            ];
        }

        $sejourTotal += $remainingSejourTotal;

        $totalAmount = round($manipulationInAmount + $manipulationOutAmount + $sejourTotal, 2);

        // Build the line items for the invoice
        $lines = [];

        // Line 1: Manipulation IN
        $lines[] = [
            'label' => 'manipulation_in',
            'description' => 'MANIPULATION DE PALETTES A L\'ENTREE',
            'quantity_kg' => $totalStockInKg,
            'quantity_days' => 0,
            'unit_price' => $serviceUnitPrice,
            'total_amount' => $manipulationInAmount,
        ];

        // Line 2: Manipulation OUT
        $lines[] = [
            'label' => 'manipulation_out',
            'description' => 'MANIPULATION DE PALETTES A LA SORTIE',
            'quantity_kg' => $totalStockOutKg,
            'quantity_days' => 0,
            'unit_price' => $serviceUnitPrice,
            'total_amount' => $manipulationOutAmount,
        ];

        // Line 3+: Séjour lines for stock_out events
        foreach ($sejourLines as $sl) {
            $lines[] = [
                'label' => 'sejour',
                'description' => 'SEJOUR AU STOCKAGE (sortie ' . $sl['exit_date'] . ')',
                'quantity_kg' => $sl['weight'],
                'quantity_days' => $sl['days'],
                'unit_price' => $dailyRate,
                'total_amount' => $sl['amount'],
            ];
        }

        // Séjour for remaining stock — one line per entry so each shows correct days
        foreach ($remainingLines as $rl) {
            $lines[] = [
                'label' => 'sejour',
                'description' => 'SEJOUR AU STOCKAGE (stock restant, entrée ' . $rl['entry_date'] . ')',
                'quantity_kg' => $rl['weight'],
                'quantity_days' => $rl['days'],
                'unit_price' => $dailyRate,
                'total_amount' => $rl['amount'],
            ];
        }

        return [
            'warehouse_id' => $warehouse->id,
            'warehouse_name' => $warehouse->name,
            'year' => $year,
            'month' => $month,
            'days_in_month' => $daysInMonth,
            'total_stock_in_kg' => $totalStockInKg,
            'total_stock_out_kg' => $totalStockOutKg,
            'manipulation_in_amount' => $manipulationInAmount,
            'manipulation_out_amount' => $manipulationOutAmount,
            'sejour_amount' => $sejourTotal,
            'total_amount' => $totalAmount,
            'service_unit_price' => $serviceUnitPrice,
            'daily_rate_per_kg' => $dailyRate,
            'weight_unit' => $weightUnit,
            'remaining_stock_kg' => $remainingKg,
            'lines' => $lines,
        ];
    }

    /**
     * Get the active séjour rate for a warehouse during a given date range.
     * Returns the rate that overlaps with the month, or null.
     */
    private function getActiveRate(int $warehouseId, Carbon $monthStart, Carbon $monthEnd): ?SejourRate
    {
        return SejourRate::where('warehouse_id', $warehouseId)
            ->where('start_date', '<=', $monthEnd->format('Y-m-d'))
            ->where(function ($q) use ($monthStart) {
                $q->where('end_date', '>=', $monthStart->format('Y-m-d'))
                  ->orWhereNull('end_date');
            })
            ->whereNull('deleted_at')
            ->orderBy('start_date', 'desc')
            ->first();
    }

    /**
     * Generate invoice number format: {MM}V{SEQ}
     * e.g., 02V003 for the 3rd invoice in February.
     */
    private function generateInvoiceNumber(int $year, int $month): string
    {
        $prefix = str_pad($month, 2, '0', STR_PAD_LEFT) . 'V';

        // Find the last invoice number with this prefix for this year
        $lastInvoice = StorageInvoice::where('year', $year)
            ->where('invoice_number', 'like', $prefix . '%')
            ->orderByRaw("CAST(SUBSTRING(invoice_number, " . (strlen($prefix) + 1) . ") AS UNSIGNED) DESC")
            ->first();

        if ($lastInvoice) {
            $lastSeq = (int)substr($lastInvoice->invoice_number, strlen($prefix));
            $nextSeq = $lastSeq + 1;
        } else {
            $nextSeq = 1;
        }

        return $prefix . str_pad($nextSeq, 3, '0', STR_PAD_LEFT);
    }
}
