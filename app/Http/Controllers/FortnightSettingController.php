<?php

namespace App\Http\Controllers;

use App\Models\FortnightSetting;
use App\Traits\BroadcastsModelChanges;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FortnightSettingController extends Controller
{
    use BroadcastsModelChanges;

    /**
     * Return all fortnight settings ordered by date_start desc.
     */
    public function index(): JsonResponse
    {
        $settings = FortnightSetting::orderBy('date_start', 'desc')->get();
        return response()->json(['settings' => $settings]);
    }

    /**
     * Create a new fortnight setting entry.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'q1_max_salary' => 'required|numeric|min:0',
            'q2_max_salary' => 'required|numeric|min:0',
            'date_start'    => 'required|date',
            'date_end'      => 'nullable|date|after_or_equal:date_start',
        ]);

        $setting = FortnightSetting::create($data);

        $this->broadcastChange('FortnightSetting', 'created', $setting->id, $setting->toArray());

        return response()->json([
            'message' => 'Fortnight setting created successfully',
            'setting' => $setting,
        ], 201);
    }

    /**
     * Update an existing fortnight setting.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $setting = FortnightSetting::findOrFail($id);

        $data = $request->validate([
            'q1_max_salary' => 'required|numeric|min:0',
            'q2_max_salary' => 'required|numeric|min:0',
            'date_start'    => 'required|date',
            'date_end'      => 'nullable|date|after_or_equal:date_start',
        ]);

        $setting->update($data);

        $this->broadcastChange('FortnightSetting', 'updated', $setting->id, $setting->toArray());

        return response()->json([
            'message' => 'Fortnight setting updated successfully',
            'setting' => $setting,
        ]);
    }

    /**
     * Delete a fortnight setting.
     */
    public function destroy($id): JsonResponse
    {
        $setting = FortnightSetting::findOrFail($id);
        $settingData = $setting->toArray();
        $setting->delete();

        $this->broadcastChange('FortnightSetting', 'deleted', $id, $settingData);

        return response()->json([
            'message' => 'Fortnight setting deleted successfully',
        ]);
    }
}
