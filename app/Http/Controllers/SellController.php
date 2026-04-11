<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\FishType;
use App\Models\GuanoType;
use App\Models\PackageType;
use App\Models\Sell;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SellController extends Controller
{
    /**
     * Resolve a stockable model by type and id.
     */
    public function passes($stockable_type, $stockable_id)
    {
        $modelClass = 'App\\Models\\' . Str::studly($stockable_type);
        if (!class_exists($modelClass)) {
            return false;
        }

        return $modelClass::find($stockable_id);
    }

    /**
     * Display the specified sell.
     */
    public function show(Sell $sell)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Sell $sell)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Sell $sell)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Sell $sell)
    {
        $ids = request()->query('ids');
        $status = Sell::whereIn("id", $ids)->delete();
        return response()->json([
            "message" => $status ? "Deleted successfully" : "No records deleted"
        ], 200);
    }
}
