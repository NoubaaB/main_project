<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\FreshFishReceive;
use App\Models\FreshFishReceiveDetail;

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
            'supplier_id' => 'sometimes|array',
            'supplier_id.*' => 'exists:suppliers,id',
            'fresh_fish_receive_details.*' => 'array',
            'fresh_fish_receive_details.*.weight' => 'sometimes|numeric|min:0',
            'fresh_fish_receive_details.*.price' => 'sometimes|numeric|min:0',
            'fresh_fish_receive_details.*.unit_price' => 'sometimes|numeric|min:0',
            'fresh_fish_receive_details.*.package_count' => 'sometimes|numeric|min:0',
            'fresh_fish_receive_details.*.fish_type_id' => 'sometimes|exists:fish_types,id',
            'fresh_fish_receive_details.*.stock_id' => 'nullable|exists:stocks,id',
            'fresh_fish_receive_details.*.package_id' => 'nullable|exists:packages,id',
            'freeze_fish_process_details.*' => 'array',
            'freeze_fish_process_details.*.weight' => 'sometimes|numeric|min:0',
            'freeze_fish_process_details.*.price' => 'sometimes|numeric|min:0',
            'freeze_fish_process_details.*.unit_price' => 'sometimes|numeric|min:0',
            'freeze_fish_process_details.*.package_count' => 'sometimes|numeric|min:0',
            'freeze_fish_process_details.*.fish_type_id' => 'sometimes|exists:fish_types,id',
            'freeze_fish_process_details.*.stock_id' => 'nullable|exists:stocks,id',
            'freeze_fish_process_details.*.package_id' => 'nullable|exists:packages,id',
        ]);
        // ✅ 1. Create parent record
        $freshFishReceive = FreshFishReceive::create([
            'date' => Carbon::parse($data['date'])->format('Y-m-d'),
            'note' => $data['note'],
            'is_stockable' => $data['is_stockable'],
            'fishery_id' => $data['fishery_id'],
            'origin_id' => $data['origin_id'],
            'charge_type_id' => $data['charge_type_id'],
        ]);

        // ✅ 2. Create fresh_fish_receive_details children (details)
        foreach ($data['fresh_fish_receive_details'] as $detail) {
            $freshFishReceive->freshFishReceiveDetails()->create($detail);
        }

        // ✅ 3. Create freeze_fish_process_details children (details)
        foreach ($data['freeze_fish_process_details'] as $detail) {
            $freshFishReceive->freezeFishProcessDetails()->create($detail);
        }

        return response()->json([
            'message' => 'Fresh Fish Receive created successfully',
            'data' => $freshFishReceive->load(['freshFishReceiveDetails', 'freezeFishProcessDetails']),
        ]);
    }
}
