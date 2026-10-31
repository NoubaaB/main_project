<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Services\PlafondService;
use App\Traits\BroadcastsModelChanges;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    use BroadcastsModelChanges;

    /**
     * Display a listing of suppliers.
     */
    public function index()
    {
        $suppliers = Supplier::withCount(['charges', 'productions'])
            ->orderBy('name')
            ->get()
            ->map(function ($supplier) {
                $supplier->usage_count = $supplier->charges_count + $supplier->productions_count;
                return $supplier;
            });

        return response()->json(['suppliers' => $suppliers]);
    }

    /**
     * Store a newly created supplier.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|min:2|unique:suppliers,name',
            'phone' => 'nullable|string|max:20',
            'ice' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:50',
            'address' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:50',
            'country' => 'nullable|string|max:200',
            'postal_code' => 'nullable|string|max:10',
            'website' => 'nullable|string|max:200',
            'description' => 'nullable|string|max:200',
            'active_status' => 'nullable|boolean',
            'enable_plafond' => 'nullable|boolean',
            'plafond_amount' => 'nullable|numeric|min:0',
            'block_on_plafond' => 'nullable|boolean',
            'plafond_mode' => 'nullable|string|in:monthly,yearly',
            'plafond_blocking_scope' => 'nullable|string|in:past_unpaid,current_month_only',
        ]);

        $supplier = Supplier::create($validated);
        $this->broadcastChange('Supplier', 'created', $supplier->id, $supplier->toArray());

        return response()->json(['supplier' => $supplier], 201);
    }

    /**
     * Display the specified supplier.
     */
    public function show(Supplier $supplier)
    {
        $supplier->loadCount(['charges', 'productions']);
        $supplier->usage_count = $supplier->charges_count + $supplier->productions_count;
        return response()->json(['supplier' => $supplier]);
    }

    /**
     * Update the specified supplier.
     */
    public function update(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|min:2|unique:suppliers,name,' . $supplier->id,
            'phone' => 'nullable|string|max:20',
            'ice' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:50',
            'address' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:50',
            'country' => 'nullable|string|max:200',
            'postal_code' => 'nullable|string|max:10',
            'website' => 'nullable|string|max:200',
            'description' => 'nullable|string|max:200',
            'active_status' => 'nullable|boolean',
            'enable_plafond' => 'nullable|boolean',
            'plafond_amount' => 'nullable|numeric|min:0',
            'block_on_plafond' => 'nullable|boolean',
            'plafond_mode' => 'nullable|string|in:monthly,yearly',
            'plafond_blocking_scope' => 'nullable|string|in:past_unpaid,current_month_only',
        ]);

        $supplier->update($validated);
        $this->broadcastChange('Supplier', 'updated', $supplier->id, $supplier->toArray());

        return response()->json(['supplier' => $supplier]);
    }

    /**
     * Remove the specified supplier.
     */
    public function destroy(Supplier $supplier)
    {
        $supplierId = $supplier->id;
        $supplier->delete();
        $this->broadcastChange('Supplier', 'deleted', $supplierId);

        return response()->json(['message' => 'Supplier deleted successfully']);
    }

    /**
     * Check supplier plafond status for a given date and new amount.
     */
    public function checkPlafond(Request $request, Supplier $supplier, PlafondService $service)
    {
        $date = $request->input('date');
        $amount = (float) $request->input('amount', 0);

        $result = $service->checkSupplierPlafond($supplier, $date, $amount);

        return response()->json($result);
    }
}
