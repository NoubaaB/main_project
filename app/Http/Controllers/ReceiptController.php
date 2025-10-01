<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\charge;
use Illuminate\Http\JsonResponse;

class ReceiptController extends Controller
{
    //
    public function receipt(Request $request)
    {
        $data  = $request->validate([
            'date' => 'required|date',
            'tracking_numbers' => 'required|array',
            'tracking_numbers.*' => 'string|max:50',
            'note' => 'string|max:500|nullable',
            'is_stockable' => 'required|boolean',
            'fishery_id' => 'required|exists:fisheries,id',
            'origin_id' => 'required|exists:origins,id',
            'charge_type_id' => 'required|exists:charge_types,id',
            'supplier_ids' => 'required|array',
            'supplier_ids.*' => 'exists:suppliers,id',
            'charge_details.*' => 'array',
            'charge_details.*.weight' => 'sometimes|numeric|min:0',
            'charge_details.*.price' => 'sometimes|numeric|min:0',
            'charge_details.*.unit_price' => 'sometimes|numeric|min:0',
            'charge_details.*.package_count' => 'sometimes|numeric|min:0',
            'charge_details.*.fish_type_id' => 'sometimes|exists:fish_types,id',
            'charge_details.*.stock_id' => 'nullable|exists:stocks,id',
            'charge_details.*.package_id' => 'nullable|exists:packages,id',
            'production_details.*' => 'array',
            'production_details.*.weight' => 'sometimes|numeric|min:0',
            'production_details.*.price' => 'sometimes|numeric|min:0',
            'production_details.*.unit_price' => 'sometimes|numeric|min:0',
            'production_details.*.package_count' => 'sometimes|numeric|min:0',
            'production_details.*.fish_type_id' => 'sometimes|exists:fish_types,id',
            'production_details.*.stock_id' => 'nullable|exists:stocks,id',
            'production_details.*.package_id' => 'nullable|exists:packages,id',
        ]);
        // ✅ 1. Create parent record
        $charge = Charge::create([
            'date' => Carbon::parse($data['date'])->format('Y-m-d'),
            'note' => $data['note'],
            'is_stockable' => $data['is_stockable'],
            'fishery_id' => $data['fishery_id'],
            'origin_id' => $data['origin_id'],
            'charge_type_id' => $data['charge_type_id'],
        ]);

        // ✅ 2. Create charge_details children (details)
        foreach ($data['charge_details'] as $detail) {
            $charge->charge_details()->create($detail);
        }

        // ✅ 3. Create production_details children (details)
        foreach ($data['production_details'] as $detail) {
            $charge->production_details()->create($detail);
        }

        $charge->suppliers()->attach($data['supplier_ids']);

        return response()->json([
            'message' => 'Fresh Fish Receive created successfully',
            'data' => $charge->load(['suppliers', 'chargeDetails', 'productionDetails']),
        ],200);
    }

    public function get_receipts() : JsonResponse
    {
        $date_start = request()->query('date_start');
        $date_end = request()->query('date_end');
        $receipts = Charge::whereBetween('date', [$date_start, $date_end])
            ->with(['suppliers', 'chargeDetails', 'productionDetails'])
            ->get();
        return response()->json(["receipts"=> $receipts],200);
        
    }
}
