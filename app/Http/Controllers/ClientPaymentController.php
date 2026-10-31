<?php

namespace App\Http\Controllers;

use App\Models\ClientPayment;
use App\Models\ClientInvoice;
use App\Models\ClientFiscalYearClosing;
use App\Traits\BroadcastsModelChanges;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClientPaymentController extends Controller
{
    use BroadcastsModelChanges;

    /**
     * List all client payments for a given year.
     */
    public function index(Request $request)
    {
        $year = $request->input('year', date('Y'));

        $payments = ClientPayment::with(['clientInvoice', 'client'])
            ->whereYear('date', $year)
            ->orderBy('date', 'desc')
            ->get();

        return response()->json(['payments' => $payments]);
    }

    /**
     * Create a new client payment.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'date' => 'required|date',
            'client_invoice_id' => 'nullable|exists:client_invoices,id',
            'client_id' => 'required|exists:clients,id',
            'payment_type' => 'required|in:cash,check,bank_transfer,bill_of_exchange,offset',
            'amount' => 'required|numeric|min:0.01',
            'reference' => 'nullable|string|max:255',
            'bank_name' => 'nullable|string|max:255',
            'due_date' => 'nullable|date',
            'notes' => 'nullable|string|max:2000',
        ]);

        // Check fiscal year is not closed
        $year = date('Y', strtotime($data['date']));
        $closing = ClientFiscalYearClosing::where('year', $year)->where('is_closed', true)->first();
        if ($closing) {
            return response()->json(['message' => 'Fiscal year is closed. Cannot add payments.'], 403);
        }

        $payment = ClientPayment::create($data);
        $payment->load('clientInvoice', 'client');

        $this->broadcastChange('ClientPayment', 'created', $payment->id, $payment->toArray());

        return response()->json(['payment' => $payment], 201);
    }

    /**
     * Update a client payment.
     */
    public function update(Request $request, ClientPayment $clientPayment)
    {
        $data = $request->validate([
            'date' => 'required|date',
            'client_invoice_id' => 'nullable|exists:client_invoices,id',
            'client_id' => 'required|exists:clients,id',
            'payment_type' => 'required|in:cash,check,bank_transfer,bill_of_exchange,offset',
            'amount' => 'required|numeric|min:0.01',
            'reference' => 'nullable|string|max:255',
            'bank_name' => 'nullable|string|max:255',
            'due_date' => 'nullable|date',
            'notes' => 'nullable|string|max:2000',
        ]);

        // Check fiscal year is not closed
        $year = date('Y', strtotime($data['date']));
        $closing = ClientFiscalYearClosing::where('year', $year)->where('is_closed', true)->first();
        if ($closing) {
            return response()->json(['message' => 'Fiscal year is closed. Cannot edit payments.'], 403);
        }

        $clientPayment->update($data);
        $clientPayment->load('clientInvoice', 'client');

        $this->broadcastChange('ClientPayment', 'updated', $clientPayment->id, $clientPayment->toArray());

        return response()->json(['payment' => $clientPayment]);
    }

    /**
     * Delete client payments by IDs.
     */
    public function destroy(Request $request)
    {
        $ids = $request->input('ids', []);
        if (empty($ids))
            return response()->json(['message' => 'No IDs provided'], 400);

        // Check none belong to a closed year
        $payments = ClientPayment::whereIn('id', $ids)->get();
        foreach ($payments as $p) {
            $year = $p->date->format('Y');
            $closing = ClientFiscalYearClosing::where('year', $year)->where('is_closed', true)->first();
            if ($closing) {
                return response()->json(['message' => "Cannot delete payments from closed year {$year}."], 403);
            }
        }

        ClientPayment::whereIn('id', $ids)->delete();

        foreach ($ids as $id) {
            $this->broadcastChange('ClientPayment', 'deleted', (int) $id);
        }

        return response()->json(['message' => 'Deleted', 'count' => count($ids)]);
    }

    /**
     * Monthly client balance for a year.
     */
    public function clientBalance(Request $request)
    {
        $year = $request->input('year', date('Y'));

        // Get carry-forward from previous year
        $prevClosing = ClientFiscalYearClosing::where('year', $year - 1)->where('is_closed', true)->first();
        $carryForwardLines = [];
        if ($prevClosing) {
            $carryForwardLines = $prevClosing->lines()->with('client')->get()
                ->mapWithKeys(fn($l) => [$l->client_id => (float) $l->balance])
                ->toArray();
        }

        // Get all client invoices for the year
        $yearInvoices = ClientInvoice::whereYear('date', $year)->get();

        // Build monthly invoices per client
        $invoiceMap = []; // [client_id][month] => total
        foreach ($yearInvoices as $inv) {
            $month = (int) $inv->date->format('m');
            $clientId = $inv->client_id;
            if (!$clientId) continue;

            if (!isset($invoiceMap[$clientId])) $invoiceMap[$clientId] = [];
            $invoiceMap[$clientId][$month] = ($invoiceMap[$clientId][$month] ?? 0) + (float) $inv->total_price;
        }

        $isSqlite = DB::connection()->getDriverName() === 'sqlite';
        $monthExpr = $isSqlite ? "CAST(strftime('%m', date) AS INTEGER)" : "MONTH(date)";

        // Monthly payments per client
        $payments = DB::table('client_payments')
            ->whereYear('date', $year)
            ->whereNull('deleted_at')
            ->select('client_id', DB::raw("{$monthExpr} as month"), DB::raw('SUM(amount) as total'))
            ->groupBy('client_id', DB::raw($monthExpr))
            ->get();

        // Build balance matrix
        $clientIds = collect(array_keys($invoiceMap))
            ->merge(collect($payments)->pluck('client_id'))
            ->merge(array_keys($carryForwardLines))
            ->unique()->values();

        $balance = [];
        foreach ($clientIds as $cid) {
            $row = [
                'client_id' => $cid,
                'carry_forward' => $carryForwardLines[$cid] ?? 0,
                'months' => [],
            ];
            $running = $carryForwardLines[$cid] ?? 0;
            for ($m = 1; $m <= 12; $m++) {
                $invTotal = $invoiceMap[$cid][$m] ?? 0;
                $pay = $payments->first(fn($p) => $p->client_id == $cid && $p->month == $m);
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

        // Get client names
        $clients = DB::table('clients')->whereIn('id', $clientIds)->pluck('name', 'id');

        return response()->json([
            'balance' => $balance,
            'clients' => $clients,
            'carry_forward_total' => array_sum($carryForwardLines),
        ]);
    }

    /**
     * Year summary aggregates per client.
     */
    public function yearSummary(Request $request)
    {
        $year = $request->input('year', date('Y'));

        // Get invoices per client
        $yearInvoices = ClientInvoice::whereYear('date', $year)->get();

        $invoiceTotals = [];
        foreach ($yearInvoices as $inv) {
            $cid = $inv->client_id;
            if (!$cid) continue;
            if (!isset($invoiceTotals[$cid])) {
                $invoiceTotals[$cid] = (object) ['total_invoices' => 0, 'invoice_count' => 0];
            }
            $invoiceTotals[$cid]->total_invoices += (float) $inv->total_price;
            $invoiceTotals[$cid]->invoice_count++;
        }

        $payments = DB::table('client_payments')
            ->whereYear('date', $year)
            ->whereNull('deleted_at')
            ->select('client_id', DB::raw('SUM(amount) as total_payments'), DB::raw('COUNT(*) as payment_count'))
            ->groupBy('client_id')
            ->get()
            ->keyBy('client_id');

        // Previous year carry-forward
        $prevClosing = ClientFiscalYearClosing::where('year', $year - 1)->where('is_closed', true)->first();
        $carryForward = [];
        if ($prevClosing) {
            $carryForward = $prevClosing->lines()->pluck('balance', 'client_id')->toArray();
        }

        $clientIds = collect(array_keys($invoiceTotals))
            ->merge($payments->keys())
            ->merge(array_keys($carryForward))
            ->unique();
        $clientsData = DB::table('clients')->whereIn('id', $clientIds)
            ->select('id', 'name', 'enable_plafond', 'plafond_amount', 'block_on_plafond', 'plafond_mode')
            ->get()
            ->keyBy('id');

        $summary = [];
        foreach ($clientIds as $cid) {
            $invData = $invoiceTotals[$cid] ?? null;
            $payData = $payments[$cid] ?? null;
            $invTotal = $invData ? (float) $invData->total_invoices : 0;
            $invCount = $invData ? $invData->invoice_count : 0;
            $payTotal = $payData ? (float) $payData->total_payments : 0;
            $payCount = $payData ? $payData->payment_count : 0;
            $cf = (float) ($carryForward[$cid] ?? 0);
            $cData = $clientsData[$cid] ?? null;
            $summary[] = [
                'client_id' => $cid,
                'client_name' => $cData->name ?? '—',
                'enable_plafond' => (bool) ($cData->enable_plafond ?? false),
                'plafond_amount' => $cData && $cData->plafond_amount !== null ? (float) $cData->plafond_amount : null,
                'block_on_plafond' => (bool) ($cData->block_on_plafond ?? false),
                'plafond_mode' => $cData->plafond_mode ?? 'monthly',
                'carry_forward' => round($cf, 2),
                'total_invoices' => round($invTotal, 2),
                'total_payments' => round($payTotal, 2),
                'balance' => round($cf + $invTotal - $payTotal, 2),
                'invoice_count' => $invCount,
                'payment_count' => $payCount,
            ];
        }

        // Current year closing status
        $closing = ClientFiscalYearClosing::where('year', $year)->first();

        return response()->json([
            'summary' => $summary,
            'closing' => $closing,
        ]);
    }
}
