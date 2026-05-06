<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Charge;
use App\Models\Production;
use Illuminate\Http\Request;
use App\Models\SupplierInvoice;
use Illuminate\Support\Facades\DB;
use App\Traits\BroadcastsModelChanges;

class SupplierInvoiceController extends Controller
{
    use BroadcastsModelChanges;
    /**
     * List supplier invoices within a date range.
     * Eagerly loads charges (with suppliers, trucks, chargeCategory, fishery, ship, origin, detailCharges)
     * and productions (with productionCategory, suppliers).
     */
    public function index()
    {
        $date_start = request()->query('date_start');
        $date_end = request()->query('date_end');

        $supplier_invoices = SupplierInvoice::whereBetween('date', [$date_start, $date_end])
            ->with([
                'charges.supplier',
                'charges.trucks',
                'charges.chargeCategory',
                'charges.fishery',
                'charges.ship',
                'charges.origin',
                'charges.detailCharges',
                'productions.productionCategory',
                'productions.supplier',
                'productions.detailProductions',
                'supplier',
            ])
            ->orderBy('date', 'desc')
            ->get();

        return response()->json(['supplier_invoices' => $supplier_invoices], 200);
    }

    /**
     * Create a new supplier invoice from selected charges and/or productions.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'date' => 'required|date',
            'tracking_numbers' => 'required|array',
            'supplier_id' => 'required|exists:suppliers,id',
            'charge_ids' => 'nullable|array',
            'charge_ids.*' => 'exists:charges,id',
            'production_ids' => 'nullable|array',
            'production_ids.*' => 'exists:productions,id',
        ]);

        $charge_ids = $data['charge_ids'] ?? [];
        $production_ids = $data['production_ids'] ?? [];

        // Check fiscal year is not closed
        $invoiceYear = date('Y', strtotime($data['date']));
        $closing = \App\Models\FiscalYearClosing::where('year', $invoiceYear)->where('is_closed', true)->first();
        if ($closing) {
            return response()->json(['message' => "Fiscal year {$invoiceYear} is closed. Cannot create invoices."], 403);
        }

        // Compute totals from charges
        $chargeTotals = DB::table('charges')
            ->whereIn('id', $charge_ids)
            ->where('allow_invoice', true)
            ->whereNull('invoice_id')
            ->selectRaw('COALESCE(SUM(total_price),0) as total_price, COALESCE(SUM(total_weight),0) as total_weight')
            ->first();

        // Compute totals from productions
        $prodTotals = DB::table('productions')
            ->whereIn('id', $production_ids)
            ->where('allow_invoice', true)
            ->whereNull('supplier_invoice_id')
            ->selectRaw('COALESCE(SUM(total_price),0) as total_price, COALESCE(SUM(total_weight),0) as total_weight')
            ->first();

        $total_price = ($chargeTotals->total_price ?? 0) + ($prodTotals->total_price ?? 0);
        $total_weight = ($chargeTotals->total_weight ?? 0) + ($prodTotals->total_weight ?? 0);

        $supplier_invoice = SupplierInvoice::create([
            'date' => $data['date'],
            'tracking_numbers' => $data['tracking_numbers'],
            'total_price' => $total_price,
            'total_weight' => $total_weight,
            'print_counter' => 0,
            'supplier_id' => $data['supplier_id'],
        ]);

        // Link charges to this invoice
        if (count($charge_ids)) {
            Charge::whereIn('id', $charge_ids)
                ->where('allow_invoice', true)
                ->whereNull('invoice_id')
                ->update(['invoice_id' => $supplier_invoice->id]);
        }

        // Link productions to this invoice
        if (count($production_ids)) {
            Production::whereIn('id', $production_ids)
                ->where('allow_invoice', true)
                ->whereNull('supplier_invoice_id')
                ->update(['supplier_invoice_id' => $supplier_invoice->id]);
        }

        // Reload with relations
        $supplier_invoice->load([
            'charges.supplier',
            'charges.trucks',
            'charges.chargeCategory',
            'charges.fishery',
            'charges.ship',
            'charges.origin',
            'charges.detailCharges',
            'productions.productionCategory',
            'productions.supplier',
            'productions.detailProductions',
            'supplier',
        ]);

        $this->broadcastChange('SupplierInvoice', 'created', $supplier_invoice->id, $supplier_invoice->toArray());

        return response()->json(['supplier_invoice' => $supplier_invoice], 200);
    }

    /**
     * Delete supplier invoices by IDs and unlink their charges/productions.
     */
    public function destroy()
    {
        $ids = request()->query('ids', []);
        if (empty($ids)) {
            return response()->json(['message' => 'No IDs provided'], 400);
        }

        $invoices = SupplierInvoice::whereIn('id', $ids)->get();
        $deleted_tracking_numbers = [];

        foreach ($invoices as $invoice) {
            // Unlink charges
            Charge::where('invoice_id', $invoice->id)->update(['invoice_id' => null]);
            // Unlink productions
            Production::where('supplier_invoice_id', $invoice->id)->update(['supplier_invoice_id' => null]);

            $deleted_tracking_numbers = array_merge($deleted_tracking_numbers, $invoice->tracking_numbers ?? []);
            $invoiceId = $invoice->id;
            $invoice->delete();
            $this->broadcastChange('SupplierInvoice', 'deleted', $invoiceId);
        }

        return response()->json([
            'message' => 'Invoices deleted',
            'deleted_tracking_numbers' => $deleted_tracking_numbers,
        ], 200);
    }

    /**
     * Fetch specific supplier invoices by their IDs (for view/print page).
     */
    public function supplierInvoicesByIds()
    {
        $ids = request()->query('ids', []);
        if (empty($ids)) {
            return response()->json(['supplier_invoices' => []], 200);
        }

        $supplier_invoices = SupplierInvoice::whereIn('id', $ids)
            ->with([
                'charges.supplier',
                'charges.trucks',
                'charges.chargeCategory',
                'charges.fishery',
                'charges.ship',
                'charges.origin',
                'charges.detailCharges',
                'productions.productionCategory',
                'productions.supplier',
                'productions.detailProductions',
                'supplier',
            ])
            ->get();

        return response()->json(['supplier_invoices' => $supplier_invoices], 200);
    }

    /**
     * Increment print counter for a supplier invoice.
     */
    public function incrementPrintCounter(Request $request)
    {
        $data = $request->validate(['id' => 'required|exists:supplier_invoices,id']);
        $invoice = SupplierInvoice::findOrFail($data['id']);
        $invoice->increment('print_counter');

        return response()->json(['print_counter' => $invoice->print_counter], 200);
    }

    /**
     * Check if a tracking number already exists in any supplier invoice.
     */
    public function checkTrackingNumber()
    {
        $trackingNumber = request()->query('tracking_number');
        $exists = SupplierInvoice::whereJsonContains('tracking_numbers', $trackingNumber)->exists();

        return response()->json(['exists' => $exists], 200);
    }
}
