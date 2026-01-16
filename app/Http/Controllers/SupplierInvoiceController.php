<?php

namespace App\Http\Controllers;

use App\Models\Charge;
use Illuminate\Http\Request;
use App\Models\SupplierInvoice;

class SupplierInvoiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
        $data = $request->validate([
                'date' => 'required|date',
                'type_invoice' => 'required|string|in:local,export',
                'tracking_numbers' => 'required|array',
                'invoice_invoicer_ids' => 'required|array',
                'invoice_invoicer_ids.*' => 'exists:suppliers,id',
                'charge_ids' => 'required|array',
                'charge_ids.*' => 'exists:charges,id'
            ]);
        
        $supplier_invoice = SupplierInvoice::create([
            "date"=> $data["date"],
            "type_invoice"=> $data["type_invoice"],
            "tracking_numbers"=> $data["tracking_numbers"],
            "supplier_ids"=> $data["invoice_invoicer_ids"],
        ]);

        Charge::whereIn("id",$data["charge_ids"])
        ->where('allow_invoice', true)
        ->update(["invoice_id"=>$supplier_invoice->id]);
        $tracking_numbers = SupplierInvoice::generateNextTrackingNumbers();

        return response()->json(["supplier_invoice"=>$supplier_invoice, "tracking_numbers"=> $tracking_numbers],200);
    }

    /**
     * Display the specified resource.
     */
    public function show(SupplierInvoice $supplierInvoice)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SupplierInvoice $supplierInvoice)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, SupplierInvoice $supplierInvoice)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SupplierInvoice $supplierInvoice)
    {
        //
    }
}
