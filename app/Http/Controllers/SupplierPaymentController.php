<?php

namespace App\Http\Controllers;

use App\Models\SupplierPayment;
use App\Models\SupplierInvoice;
use App\Models\FiscalYearClosing;
use App\Traits\BroadcastsModelChanges;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupplierPaymentController extends Controller
{
    use BroadcastsModelChanges;

    /**
     * List all payments for a given year.
     */
    public function index(Request $request)
    {
        $year = $request->input('year', date('Y'));

        $payments = SupplierPayment::with(['supplierInvoice', 'supplier'])
            ->whereYear('date', $year)
            ->orderBy('date', 'desc')
            ->get();

        return response()->json(['payments' => $payments]);
    }

    /**
     * Create a new payment.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'date' => 'required|date',
            'supplier_invoice_id' => 'nullable|exists:supplier_invoices,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'payment_type' => 'required|in:cash,check,bank_transfer,bill_of_exchange,offset',
            'amount' => 'required|numeric|min:0.01',
            'reference' => 'nullable|string|max:255',
            'bank_name' => 'nullable|string|max:255',
            'due_date' => 'nullable|date',
            'notes' => 'nullable|string|max:2000',
        ]);

        // Check fiscal year is not closed
        $year = date('Y', strtotime($data['date']));
        $closing = FiscalYearClosing::where('year', $year)->where('is_closed', true)->first();
        if ($closing) {
            return response()->json(['message' => 'Fiscal year is closed. Cannot add payments.'], 403);
        }

        $payment = SupplierPayment::create($data);
        $payment->load('supplierInvoice', 'supplier');

        $this->broadcastChange('SupplierPayment', 'created', $payment->id, $payment->toArray());

        return response()->json(['payment' => $payment], 201);
    }

    /**
     * Update a payment.
     */
    public function update(Request $request, SupplierPayment $supplierPayment)
    {
        $data = $request->validate([
            'date' => 'required|date',
            'supplier_invoice_id' => 'nullable|exists:supplier_invoices,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'payment_type' => 'required|in:cash,check,bank_transfer,bill_of_exchange,offset',
            'amount' => 'required|numeric|min:0.01',
            'reference' => 'nullable|string|max:255',
            'bank_name' => 'nullable|string|max:255',
            'due_date' => 'nullable|date',
            'notes' => 'nullable|string|max:2000',
        ]);

        // Check fiscal year is not closed
        $year = date('Y', strtotime($data['date']));
        $closing = FiscalYearClosing::where('year', $year)->where('is_closed', true)->first();
        if ($closing) {
            return response()->json(['message' => 'Fiscal year is closed. Cannot edit payments.'], 403);
        }

        $supplierPayment->update($data);
        $supplierPayment->load('supplierInvoice', 'supplier');

        $this->broadcastChange('SupplierPayment', 'updated', $supplierPayment->id, $supplierPayment->toArray());

        return response()->json(['payment' => $supplierPayment]);
    }

    /**
     * Delete payments by IDs.
     */
    public function destroy(Request $request)
    {
        $ids = $request->input('ids', []);
        if (empty($ids))
            return response()->json(['message' => 'No IDs provided'], 400);

        // Check none belong to a closed year
        $payments = SupplierPayment::whereIn('id', $ids)->get();
        foreach ($payments as $p) {
            $year = $p->date->format('Y');
            $closing = FiscalYearClosing::where('year', $year)->where('is_closed', true)->first();
            if ($closing) {
                return response()->json(['message' => "Cannot delete payments from closed year {$year}."], 403);
            }
        }

        SupplierPayment::whereIn('id', $ids)->delete();

        foreach ($ids as $id) {
            $this->broadcastChange('SupplierPayment', 'deleted', (int) $id);
        }

        return response()->json(['message' => 'Deleted', 'count' => count($ids)]);
    }

    /**
     * Monthly supplier balance for a year.
     * Returns per-supplier, per-month: invoice total, payment total, running balance.
     * Includes carry-forward from previous year closing.
     */
    public function supplierBalance(Request $request)
    {
        $year = $request->input('year', date('Y'));

        // Get carry-forward from previous year
        $prevClosing = FiscalYearClosing::where('year', $year - 1)->where('is_closed', true)->first();
        $carryForwardLines = [];
        if ($prevClosing) {
            $carryForwardLines = $prevClosing->lines()->with('supplier')->get()
                ->mapWithKeys(fn($l) => [$l->supplier_id => (float) $l->balance])
                ->toArray();
        }

        // Get all invoices for the year (direct supplier_id FK)
        $yearInvoices = SupplierInvoice::whereYear('date', $year)->get();

        // Build monthly invoices per supplier
        $invoiceMap = []; // [supplier_id][month] => total
        foreach ($yearInvoices as $inv) {
            $month = (int) $inv->date->format('m');
            $sid = $inv->supplier_id;
            if (!$sid) continue;

            if (!isset($invoiceMap[$sid])) $invoiceMap[$sid] = [];
            $invoiceMap[$sid][$month] = ($invoiceMap[$sid][$month] ?? 0) + (float) $inv->total_price;
        }

        $isSqlite = DB::connection()->getDriverName() === 'sqlite';
        $monthExpr = $isSqlite ? "CAST(strftime('%m', date) AS INTEGER)" : "MONTH(date)";

        // Monthly payments per supplier (exclude soft-deleted)
        $payments = DB::table('supplier_payments')
            ->whereYear('date', $year)
            ->whereNull('deleted_at')
            ->select('supplier_id', DB::raw("{$monthExpr} as month"), DB::raw('SUM(amount) as total'))
            ->groupBy('supplier_id', DB::raw($monthExpr))
            ->get();

        // Build balance matrix
        $supplierIds = collect(array_keys($invoiceMap))
            ->merge(collect($payments)->pluck('supplier_id'))
            ->merge(array_keys($carryForwardLines))
            ->unique()->values();

        $balance = [];
        foreach ($supplierIds as $sid) {
            $row = [
                'supplier_id' => $sid,
                'carry_forward' => $carryForwardLines[$sid] ?? 0,
                'months' => [],
            ];
            $running = $carryForwardLines[$sid] ?? 0;
            for ($m = 1; $m <= 12; $m++) {
                $invTotal = $invoiceMap[$sid][$m] ?? 0;
                $pay = $payments->first(fn($p) => $p->supplier_id == $sid && $p->month == $m);
                $payTotal = $pay ? (float) $pay->total : 0;
                $running += $invTotal - $payTotal;
                $row['months'][$m] = [
                    'invoices' => round($invTotal, 2),
                    'payments' => round($payTotal, 2),
                    'balance' => round($running, 2),
                ];
            }
            $balance[] = $row;
        }

        // Get supplier names
        $suppliers = DB::table('suppliers')->whereIn('id', $supplierIds)->pluck('name', 'id');

        return response()->json([
            'balance' => $balance,
            'suppliers' => $suppliers,
            'carry_forward_total' => array_sum($carryForwardLines),
        ]);
    }

    /**
     * Year summary aggregates per supplier.
     */
    public function yearSummary(Request $request)
    {
        $year = $request->input('year', date('Y'));

        // Resolve invoices per supplier (direct FK)
        $yearInvoices = SupplierInvoice::whereYear('date', $year)->get();

        $invoiceTotals = []; // [supplier_id] => { total, count }
        foreach ($yearInvoices as $inv) {
            $sid = $inv->supplier_id;
            if (!$sid) continue;
            if (!isset($invoiceTotals[$sid])) {
                $invoiceTotals[$sid] = (object) ['total_invoices' => 0, 'invoice_count' => 0];
            }
            $invoiceTotals[$sid]->total_invoices += (float) $inv->total_price;
            $invoiceTotals[$sid]->invoice_count++;
        }

        $payments = DB::table('supplier_payments')
            ->whereYear('date', $year)
            ->whereNull('deleted_at')
            ->select('supplier_id', DB::raw('SUM(amount) as total_payments'), DB::raw('COUNT(*) as payment_count'))
            ->groupBy('supplier_id')
            ->get()
            ->keyBy('supplier_id');

        // Previous year carry-forward
        $prevClosing = FiscalYearClosing::where('year', $year - 1)->where('is_closed', true)->first();
        $carryForward = [];
        if ($prevClosing) {
            $carryForward = $prevClosing->lines()->pluck('balance', 'supplier_id')->toArray();
        }

        $supplierIds = collect(array_keys($invoiceTotals))
            ->merge($payments->keys())
            ->merge(array_keys($carryForward))
            ->unique();
        $suppliersData = DB::table('suppliers')->whereIn('id', $supplierIds)
            ->select('id', 'name', 'enable_plafond', 'plafond_amount', 'block_on_plafond', 'plafond_mode')
            ->get()
            ->keyBy('id');

        $summary = [];
        foreach ($supplierIds as $sid) {
            $invData = $invoiceTotals[$sid] ?? null;
            $payData = $payments[$sid] ?? null;
            $invTotal = $invData ? (float) $invData->total_invoices : 0;
            $invCount = $invData ? $invData->invoice_count : 0;
            $payTotal = $payData ? (float) $payData->total_payments : 0;
            $payCount = $payData ? $payData->payment_count : 0;
            $cf = (float) ($carryForward[$sid] ?? 0);
            $sData = $suppliersData[$sid] ?? null;
            $summary[] = [
                'supplier_id' => $sid,
                'supplier_name' => $sData->name ?? '—',
                'enable_plafond' => (bool) ($sData->enable_plafond ?? false),
                'plafond_amount' => $sData && $sData->plafond_amount !== null ? (float) $sData->plafond_amount : null,
                'block_on_plafond' => (bool) ($sData->block_on_plafond ?? false),
                'plafond_mode' => $sData->plafond_mode ?? 'monthly',
                'carry_forward' => round($cf, 2),
                'total_invoices' => round($invTotal, 2),
                'total_payments' => round($payTotal, 2),
                'balance' => round($cf + $invTotal - $payTotal, 2),
                'invoice_count' => $invCount,
                'payment_count' => $payCount,
            ];
        }

        // Current year closing status
        $closing = FiscalYearClosing::where('year', $year)->first();

        return response()->json([
            'summary' => $summary,
            'closing' => $closing,
        ]);
    }
}
