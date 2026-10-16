<?php

namespace App\Http\Controllers;

use App\Models\InvoiceLayoutSetting;
use App\Traits\BroadcastsModelChanges;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class InvoiceLayoutSettingController extends Controller
{
    use BroadcastsModelChanges;

    /**
     * Get all invoice layout settings (client and supplier).
     */
    public function index(): JsonResponse
    {
        $client = InvoiceLayoutSetting::getForType('client');
        $supplier = InvoiceLayoutSetting::getForType('supplier');

        return response()->json([
            'settings' => [
                'client' => $client,
                'supplier' => $supplier,
            ],
        ]);
    }

    /**
     * Get layout settings for a specific invoice type.
     */
    public function show(string $type): JsonResponse
    {
        if (!in_array($type, ['client', 'supplier'])) {
            return response()->json(['message' => 'Invalid invoice type.'], 400);
        }

        $setting = InvoiceLayoutSetting::getForType($type);
        return response()->json(['setting' => $setting]);
    }

    /**
     * Update invoice layout settings for a specific type.
     */
    public function update(Request $request, string $type): JsonResponse
    {
        if (!in_array($type, ['client', 'supplier'])) {
            return response()->json(['message' => 'Invalid invoice type.'], 400);
        }

        $validated = $request->validate([
            'header_divided_height' => 'nullable|string|max:30',
            'header_strick_height' => 'nullable|string|max:30',
            'header_primary_color' => 'nullable|string|max:30',
            'header_secondary_color' => 'nullable|string|max:30',
            'header_text_color' => 'nullable|string|max:30',
            'header_bg_color' => 'nullable|string|max:30',
            'header_style' => 'nullable|string|in:curved,slant,straight,rounded,gradient,minimal',
            'header_logo_width' => 'nullable|string|max:30',
            'header_title_size' => 'nullable|string|max:30',
            'header_title_text' => 'nullable|string|max:100',
            'footer_divided_height' => 'nullable|string|max:30',
            'footer_strick_height' => 'nullable|string|max:30',
            'footer_primary_color' => 'nullable|string|max:30',
            'footer_secondary_color' => 'nullable|string|max:30',
            'footer_text_color' => 'nullable|string|max:30',
            'footer_bg_color' => 'nullable|string|max:30',
            'footer_style' => 'nullable|string|in:curved,slant,straight,rounded,gradient,minimal',
            'footer_director_title' => 'nullable|string|max:100',
            'footer_director_size' => 'nullable|string|max:30',
            'footer_line_style' => 'nullable|string|in:dashed,solid,dotted,none',
            'footer_show_director' => 'nullable|boolean',
            'options' => 'nullable|array',
        ]);

        $setting = InvoiceLayoutSetting::getForType($type);
        $setting->update($validated);

        // Broadcast changes in real-time to other users
        $this->broadcastChange('InvoiceLayoutSetting', 'updated', $setting->id, $setting->toArray());

        return response()->json([
            'message' => 'Invoice layout parameters saved successfully',
            'setting' => $setting->fresh(),
        ]);
    }

    /**
     * Reset invoice layout settings for a specific type back to default.
     */
    public function reset(string $type): JsonResponse
    {
        if (!in_array($type, ['client', 'supplier'])) {
            return response()->json(['message' => 'Invalid invoice type.'], 400);
        }

        $defaults = InvoiceLayoutSetting::getDefaultSettings($type);
        unset($defaults['invoice_type']);

        $setting = InvoiceLayoutSetting::getForType($type);
        $setting->update($defaults);

        $this->broadcastChange('InvoiceLayoutSetting', 'updated', $setting->id, $setting->toArray());

        return response()->json([
            'message' => 'Invoice layout parameters reset to defaults successfully',
            'setting' => $setting->fresh(),
        ]);
    }
}
