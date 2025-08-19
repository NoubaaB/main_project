<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ReceiptController extends Controller
{
    //
    public function receipt(Request $request)
    {
        $data  = $request->validate([
            'date' => 'required|date|format:Y-m-d',
            'tracking_numbers' => 'required|array',
            'tracking_numbers.*' => 'string|max:50',
            'note' => 'string|max:500|nullable',
            'is_stockable' => 'required|boolean',
            'fishery_id' => 'required|exists:fisheries,id',
            'origin_id' => 'required|exists:origins,id',
            'charge_type_id' => 'required|string|max:50',
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
        ]);
        dd($data);
    }
}
