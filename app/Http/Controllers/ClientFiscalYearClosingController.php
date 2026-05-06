<?php

namespace App\Http\Controllers;

use App\Models\ClientInvoice;
use App\Models\ClientPayment;
use App\Models\ClientFiscalYearClosing;
use App\Models\ClientFiscalYearClosingLine;
use App\Traits\BroadcastsModelChanges;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClientFiscalYearClosingController extends Controller
{
    use BroadcastsModelChanges;

    /**
     * List all client fiscal year closings.
     */
    public function index()
    {
        $closings = ClientFiscalYearClosing::orderBy('year', 'desc')->get();
        return response()->json(['closings' => $closings]);
    }

    /**
     * Close a fiscal year for clients.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'year' => 'required|integer|min:2000|max:2100',
        ]);

        $year = $data['year'];

        // Check not already closed
        $existing = ClientFiscalYearClosing::where('year', $year)->first();
        if ($existing && $existing->is_closed) {
            return response()->json(['message' => "Year {$year} is already closed."], 422);
        }

        // Compute totals per client
        $invoiceTotals = [];
        $yearInvoices = ClientInvoice::whereYear('date', $year)->get();
        foreach ($yearInvoices as $inv) {
            $cid = $inv->client_id;
            if (!$cid) continue;
            $invoiceTotals[$cid] = ($invoiceTotals[$cid] ?? 0) + (float) $inv->total_price;
        }

        $paymentTotals = DB::table('client_payments')
            ->whereYear('date', $year)
            ->whereNull('deleted_at')
            ->select('client_id', DB::raw('SUM(amount) as total'))
            ->groupBy('client_id')
            ->pluck('total', 'client_id')
            ->toArray();

        // Get carry-forward from previous year (only from closed years)
        $prevClosing = ClientFiscalYearClosing::where('year', $year - 1)->where('is_closed', true)->first();
        $prevCarry = [];
        if ($prevClosing) {
            $prevCarry = $prevClosing->lines()->pluck('balance', 'client_id')->toArray();
        }

        $clientIds = collect(array_keys($invoiceTotals))
            ->merge(array_keys($paymentTotals))
            ->merge(array_keys($prevCarry))
            ->unique();

        $grandInvoices = 0;
        $grandPayments = 0;
        $grandCarry = 0;

        DB::transaction(function () use (
            $year, $existing, $clientIds, $invoiceTotals, $paymentTotals, $prevCarry,
            &$grandInvoices, &$grandPayments, &$grandCarry, &$closing
        ) {
            $closing = $existing ?? ClientFiscalYearClosing::create([
                'year' => $year,
                'is_closed' => true,
                'closing_date' => now(),
            ]);

            if ($existing) {
                $closing->update(['is_closed' => true, 'closing_date' => now()]);
                $closing->lines()->forceDelete();
            }

            foreach ($clientIds as $cid) {
                $inv = (float) ($invoiceTotals[$cid] ?? 0);
                $pay = (float) ($paymentTotals[$cid] ?? 0);
                $cf = (float) ($prevCarry[$cid] ?? 0);
                $balance = $cf + $inv - $pay;

                ClientFiscalYearClosingLine::create([
                    'client_fiscal_year_closing_id' => $closing->id,
                    'client_id' => $cid,
                    'total_invoices' => round($inv, 2),
                    'total_payments' => round($pay, 2),
                    'carry_forward' => round($cf, 2),
                    'balance' => round($balance, 2),
                ]);

                $grandInvoices += $inv;
                $grandPayments += $pay;
                $grandCarry += $cf;
            }

            $closing->update([
                'total_invoices' => round($grandInvoices, 2),
                'total_payments' => round($grandPayments, 2),
                'carried_forward' => round($grandCarry, 2),
            ]);
        });

        $this->broadcastChange('ClientFiscalYearClosing', 'created', $closing->id, $closing->toArray());

        return response()->json(['closing' => $closing], 201);
    }

    /**
     * Reopen a closed fiscal year.
     */
    public function reopen(ClientFiscalYearClosing $clientFiscalYearClosing)
    {
        if (!$clientFiscalYearClosing->is_closed) {
            return response()->json(['message' => 'Year is not closed.'], 422);
        }

        $clientFiscalYearClosing->update(['is_closed' => false, 'closing_date' => null]);

        $this->broadcastChange('ClientFiscalYearClosing', 'updated', $clientFiscalYearClosing->id, $clientFiscalYearClosing->toArray());

        return response()->json(['closing' => $clientFiscalYearClosing]);
    }
}
