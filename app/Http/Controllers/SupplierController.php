<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Traits\BroadcastsModelChanges;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    use BroadcastsModelChanges;
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
            "name" => "required|string|max:100|min:2"
        ]);
        $supplier = Supplier::create($data);
        $this->broadcastChange('Supplier', 'created', $supplier->id, $supplier->toArray());
        return response()->json(["supplier" => $supplier], 200);
    }

    /**
     * Display the specified resource.
     */
    public function show(Supplier $supplier)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Supplier $supplier)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Supplier $supplier)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Supplier $supplier)
    {
        //
    }
}
