<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Shipping;
use App\Models\Production;
use App\Models\ClientInvoice;
use App\Models\ClientFiscalYearClosing;
use App\Traits\BroadcastsModelChanges;
use Illuminate\Support\Facades\DB;

class ClientInvoiceController extends Controller
{
    use BroadcastsModelChanges;

    public function index(Request $request)
    {
        $date_start = $request->query('date_start');
        $date_end = $request->query('date_end');
        $client_invoices = ClientInvoice::whereBetween('date', [$date_start, $date_end])
            ->with([
                'client',
                'shippings.client',
                'shippings.shippingCategory',
                'shippings.detailShippings',
                'productions.client',
                'productions.productionCategory',
                'productions.detailProductions',
            ])
            ->orderBy('date', 'desc')
            ->get();
        return response()->json(['client_invoices' => $client_invoices], 200);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'date' => 'required|date',
            'type_invoice' => 'nullable|string|in:local,export',
            'tracking_numbers' => 'required|array',
            'client_id' => 'required|exists:clients,id',
            'shipping_ids' => 'nullable|array',
            'shipping_ids.*' => 'exists:shippings,id',
            'production_ids' => 'nullable|array',
            'production_ids.*' => 'exists:productions,id',
            // Export-specific fields
            'incoterm' => 'nullable|string|max:10',
            'currency' => 'nullable|string|max:10',
            'total_gross_weight' => 'nullable|numeric|min:0',
            'intermediary_company' => 'nullable|string|max:255',
            'intermediary_address' => 'nullable|string|max:500',
            'intermediary_country' => 'nullable|string|max:255',
            'destination_company' => 'nullable|string|max:255',
            'destination_address' => 'nullable|string|max:500',
            'destination_country' => 'nullable|string|max:255',
            'shipment_country' => 'nullable|string|max:255',
            'tax_percentage' => 'nullable|numeric|min:0|max:100',
            'export_details' => 'nullable|array',
            'export_details.*.description' => 'nullable|string',
            'export_details.*.container_or_truck_id' => 'nullable|string',
            'export_details.*.weight_mt' => 'nullable|numeric|min:0',
            'export_details.*.unit_price_per_ton' => 'nullable|numeric|min:0',
            'export_details.*.total_price' => 'nullable|numeric|min:0',
        ]);

        $shipping_ids = $data['shipping_ids'] ?? [];
        $production_ids = $data['production_ids'] ?? [];

        // Check fiscal year is not closed
        $invoiceYear = date('Y', strtotime($data['date']));
        $closing = ClientFiscalYearClosing::where('year', $invoiceYear)->where('is_closed', true)->first();
        if ($closing) {
            return response()->json(['message' => "Fiscal year {$invoiceYear} is closed. Cannot create invoices."], 403);
        }

        // Compute totals from shippings
        $shipTotals = DB::table('shippings')
            ->whereIn('id', $shipping_ids)
            ->where('allow_invoice', true)
            ->whereNull('invoice_id')
            ->selectRaw('COALESCE(SUM(total_price),0) as total_price, COALESCE(SUM(total_weight),0) as total_weight')
            ->first();

        // Compute totals from productions
        $prodTotals = DB::table('productions')
            ->whereIn('id', $production_ids)
            ->where('allow_invoice', true)
            ->whereNull('client_invoice_id')
            ->selectRaw('COALESCE(SUM(total_price),0) as total_price, COALESCE(SUM(total_weight),0) as total_weight')
            ->first();

        $total_price = ($shipTotals->total_price ?? 0) + ($prodTotals->total_price ?? 0);
        $total_weight = ($shipTotals->total_weight ?? 0) + ($prodTotals->total_weight ?? 0);

        $invoiceData = [
            'date' => $data['date'],
            'type_invoice' => $data['type_invoice'] ?? 'local',
            'total_price' => $total_price,
            'total_weight' => $total_weight,
            'tracking_numbers' => $data['tracking_numbers'],
            'client_id' => $data['client_id'],
            'print_counter' => 0,
        ];

        // Persist export-specific fields
        if (($data['type_invoice'] ?? 'local') === 'export') {
            $invoiceData = array_merge($invoiceData, [
                'incoterm' => $data['incoterm'] ?? null,
                'currency' => $data['currency'] ?? null,
                'total_gross_weight' => $data['total_gross_weight'] ?? null,
                'intermediary_company' => $data['intermediary_company'] ?? null,
                'intermediary_address' => $data['intermediary_address'] ?? null,
                'intermediary_country' => $data['intermediary_country'] ?? null,
                'destination_company' => $data['destination_company'] ?? null,
                'destination_address' => $data['destination_address'] ?? null,
                'destination_country' => $data['destination_country'] ?? null,
                'shipment_country' => $data['shipment_country'] ?? null,
                'tax_percentage' => $data['tax_percentage'] ?? 0,
                'export_details' => $data['export_details'] ?? null,
            ]);
        }

        $client_invoice = ClientInvoice::create($invoiceData);

        // Link shippings
        if (count($shipping_ids)) {
            Shipping::whereIn('id', $shipping_ids)
                ->where('allow_invoice', true)
                ->whereNull('invoice_id')
                ->update(['invoice_id' => $client_invoice->id]);
        }

        // Link productions
        if (count($production_ids)) {
            Production::whereIn('id', $production_ids)
                ->where('allow_invoice', true)
                ->whereNull('client_invoice_id')
                ->update(['client_invoice_id' => $client_invoice->id]);
        }

        $client_invoice->load('client', 'shippings', 'productions');
        $this->broadcastChange('ClientInvoice', 'created', $client_invoice->id, $client_invoice->toArray());

        return response()->json(['client_invoice' => $client_invoice], 200);
    }

    public function destroy(Request $request)
    {
        $data = $request->validate(['ids' => 'required|array']);
        $invoices = ClientInvoice::whereIn('id', $data['ids'])->get();
        $deleted_tracking_numbers = [];

        foreach ($invoices as $invoice) {
            // Unlink shippings
            Shipping::where('invoice_id', $invoice->id)->update(['invoice_id' => null]);
            // Unlink productions
            Production::where('client_invoice_id', $invoice->id)->update(['client_invoice_id' => null]);

            $deleted_tracking_numbers = array_merge($deleted_tracking_numbers, $invoice->tracking_numbers ?? []);
            $invoice->delete();
            $this->broadcastChange('ClientInvoice', 'deleted', $invoice->id);
        }

        return response()->json([
            'message' => 'Invoices deleted',
            'deleted_tracking_numbers' => $deleted_tracking_numbers,
        ], 200);
    }

    public function checkTrackingNumber(Request $request)
    {
        $tracking = $request->query('tracking_number');
        $exists = ClientInvoice::whereJsonContains('tracking_numbers', $tracking)->exists();
        return response()->json(['exists' => $exists]);
    }

    /**
     * Get next available tracking number for a given invoice type.
     * Local:  YY-0001, YY-0002 ...
     * Export: YY-E001, YY-E002 ...
     */
    public function nextTrackingNumber(Request $request)
    {
        $type = $request->query('type', 'local');
        $year = date('y'); // 2-digit year

        if ($type === 'export') {
            $prefix = $year . '-E';
        } else {
            $prefix = $year . '-';
        }

        // Find all tracking numbers from invoices of this type in the current year
        $invoices = ClientInvoice::where('type_invoice', $type)->get();
        $maxNum = 0;

        foreach ($invoices as $inv) {
            $trackingNumbers = $inv->tracking_numbers ?? [];
            foreach ($trackingNumbers as $tn) {
                if (str_starts_with($tn, $prefix)) {
                    $numPart = substr($tn, strlen($prefix));
                    $num = intval($numPart);
                    if ($num > $maxNum) $maxNum = $num;
                }
            }
        }

        $nextNum = $maxNum + 1;

        if ($type === 'export') {
            $next = $prefix . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
        } else {
            $next = $prefix . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
        }

        return response()->json(['next_tracking_number' => $next]);
    }

    /**
     * Fetch specific client invoices by their IDs (for view/print page).
     */
    public function clientInvoicesByIds(Request $request)
    {
        $ids = $request->query('ids', []);
        $ids = is_array($ids) ? $ids : [$ids];
        if (empty($ids)) {
            return response()->json(['client_invoices' => []], 200);
        }

        $client_invoices = ClientInvoice::whereIn('id', $ids)
            ->with([
                'client',
                'shippings.client',
                'shippings.shippingCategory',
                'shippings.detailShippings',
                'productions.client',
                'productions.productionCategory',
                'productions.detailProductions',
            ])
            ->get();

        return response()->json(['client_invoices' => $client_invoices], 200);
    }

    /**
     * Increment print counter for a client invoice.
     */
    public function incrementPrintCounter(Request $request)
    {
        $data = $request->validate(['id' => 'required|exists:client_invoices,id']);
        $invoice = ClientInvoice::findOrFail($data['id']);
        $invoice->increment('print_counter');

        return response()->json(['print_counter' => $invoice->print_counter], 200);
    }
}
