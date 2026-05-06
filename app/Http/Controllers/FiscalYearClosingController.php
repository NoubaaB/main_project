<?php

namespace App\Http\Controllers;

use App\Models\FiscalYearClosing;
use App\Models\FiscalYearClosingLine;
use App\Models\SupplierInvoice;
use App\Models\SupplierPayment;
use App\Traits\BroadcastsModelChanges;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FiscalYearClosingController extends Controller
{
    use BroadcastsModelChanges;

    /**
     * List all fiscal year closings.
     */
    public function index()
    {
        $closings = FiscalYearClosing::orderBy('year', 'desc')->get();
        return response()->json(['closings' => $closings]);
    }

    /**
     * Show a closing with its per-supplier lines.
     */
    public function show(FiscalYearClosing $fiscalYearClosing)
    {
        $fiscalYearClosing->load('lines.supplier');
        return response()->json(['closing' => $fiscalYearClosing]);
    }

    /**
     * Execute fiscal year closing.
     * Computes per-supplier balances and creates closing + lines.
     */
    public function close(Request $request)
    {
        $data = $request->validate([
            'year' => 'required|integer|min:2020',
        ]);

        $year = $data['year'];

        // Check if already closed
        $existing = FiscalYearClosing::where('year', $year)->first();
        if ($existing && $existing->is_closed) {
            return response()->json(['message' => 'Year is already closed.'], 422);
        }

        // Previous year carry-forward (only from closed years)
        $prevClosing = FiscalYearClosing::where('year', $year - 1)->where('is_closed', true)->first();
        $carryForward = [];
        if ($prevClosing) {
            $carryForward = $prevClosing->lines()->pluck('balance', 'supplier_id')->toArray();
        }

        // Compute per-supplier invoice totals (resolving supplier from charges/productions)
        $yearInvoices = SupplierInvoice::whereYear('date', $year)
            ->with(['charges.suppliers', 'productions.suppliers'])
            ->get();

        $invoices = [];
        foreach ($yearInvoices as $inv) {
            $sids = collect();
            if ($inv->supplier_id) {
                $sids->push($inv->supplier_id);
            } else {
                foreach ($inv->charges as $c) {
                    foreach ($c->suppliers as $s) { $sids->push($s->id); }
                }
                foreach ($inv->productions as $p) {
                    foreach ($p->suppliers as $s) { $sids->push($s->id); }
                }
            }
            $sids = $sids->unique();
            $count = $sids->count();
            if ($count === 0) continue;
            $share = (float) $inv->total_price / $count;
            foreach ($sids as $sid) {
                $invoices[$sid] = ($invoices[$sid] ?? 0) + $share;
            }
        }

        // Compute per-supplier payment totals for the year
        $payments = DB::table('supplier_payments')
            ->whereYear('date', $year)
            ->whereNull('deleted_at')
            ->select('supplier_id', DB::raw('SUM(amount) as total'))
            ->groupBy('supplier_id')
            ->pluck('total', 'supplier_id')
            ->toArray();

        $allSupplierIds = array_unique(array_merge(
            array_keys($invoices),
            array_keys($payments),
            array_keys($carryForward)
        ));

        $totalInvoices = 0;
        $totalPayments = 0;
        $totalCarryForward = 0;

        $lines = [];
        foreach ($allSupplierIds as $sid) {
            $cf = (float) ($carryForward[$sid] ?? 0);
            $inv = (float) ($invoices[$sid] ?? 0);
            $pay = (float) ($payments[$sid] ?? 0);
            $balance = $cf + $inv - $pay;

            $totalInvoices += $inv;
            $totalPayments += $pay;
            $totalCarryForward += $balance;

            $lines[] = [
                'supplier_id' => $sid,
                'invoice_total' => round($inv, 2),
                'payment_total' => round($pay, 2),
                'balance' => round($balance, 2),
            ];
        }

        return DB::transaction(function () use ($year, $totalInvoices, $totalPayments, $totalCarryForward, $lines, $existing) {
            // Update or create the closing
            $closing = $existing ?: new FiscalYearClosing();
            $closing->fill([
                'year' => $year,
                'closing_date' => now()->toDateString(),
                'total_invoices' => round($totalInvoices, 2),
                'total_payments' => round($totalPayments, 2),
                'carried_forward' => round($totalCarryForward, 2),
                'is_closed' => true,
            ]);
            $closing->save();

            // Force-delete old lines (not soft-delete) to avoid unique constraint violations
            $closing->lines()->forceDelete();
            foreach ($lines as $line) {
                $closing->lines()->create($line);
            }

            $closing->load('lines.supplier');

            $this->broadcastChange('FiscalYearClosing', 'created', $closing->id, $closing->toArray());

            return response()->json(['closing' => $closing], 201);
        });
    }

    /**
     * Reopen a closed fiscal year.
     */
    public function reopen(FiscalYearClosing $fiscalYearClosing)
    {
        if (!$fiscalYearClosing->is_closed) {
            return response()->json(['message' => 'Year is not closed.'], 422);
        }

        $fiscalYearClosing->update(['is_closed' => false]);

        $this->broadcastChange('FiscalYearClosing', 'updated', $fiscalYearClosing->id, $fiscalYearClosing->toArray());

        return response()->json(['closing' => $fiscalYearClosing]);
    }
}
