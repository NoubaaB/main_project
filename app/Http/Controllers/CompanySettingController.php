<?php

namespace App\Http\Controllers;

use App\Models\CompanySetting;
use App\Traits\BroadcastsModelChanges;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CompanySettingController extends Controller
{
    use BroadcastsModelChanges;

    /**
     * Return all company settings as a key-value object.
     */
    public function index()
    {
        $settings = CompanySetting::getAllSettings();

        // Convert logo paths to full URLs
        if (!empty($settings['logo_invoice_path'])) {
            $settings['logo_invoice_url'] = asset('storage/' . $settings['logo_invoice_path']);
        }
        if (!empty($settings['logo_invoice_body_path'])) {
            $settings['logo_invoice_body_url'] = asset('storage/' . $settings['logo_invoice_body_path']);
        }

        return response()->json(['settings' => $settings]);
    }

    /**
     * Bulk update company settings from form data.
     */
    public function update(Request $request)
    {
        $allowed = [
            'company_name', 'director_name', 'director_gender',
            'company_origin', 'company_address', 'swift_code',
            'phone_number', 'fax_number', 'email',
            'bank_name', 'bank_account_number', 'capital',
            'ice', 'rc', 'cnss', 'tva_number',
        ];

        $data = $request->only($allowed);

        foreach ($data as $key => $value) {
            CompanySetting::setValue($key, $value);
        }

        $allSettings = CompanySetting::getAllSettings();
        // Resolve logo URLs for broadcast
        if (!empty($allSettings['logo_invoice_path'])) {
            $allSettings['logo_invoice_url'] = asset('storage/' . $allSettings['logo_invoice_path']);
        }
        if (!empty($allSettings['logo_invoice_body_path'])) {
            $allSettings['logo_invoice_body_url'] = asset('storage/' . $allSettings['logo_invoice_body_path']);
        }

        // Broadcast to other users
        $this->broadcastChange('CompanySetting', 'updated', 0, $allSettings);

        return response()->json([
            'message'  => 'Settings updated successfully',
            'settings' => $allSettings,
        ]);
    }

    /**
     * Handle logo file uploads.
     */
    public function uploadLogo(Request $request)
    {
        $request->validate([
            'logo_type' => 'required|in:logo_invoice,logo_invoice_body',
            'logo'      => 'required|image|mimes:png,jpg,jpeg,svg,webp|max:2048',
        ]);

        $type = $request->logo_type;
        $key  = $type . '_path';

        // Delete old logo if exists
        $oldPath = CompanySetting::getValue($key);
        if ($oldPath && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }

        // Store new logo
        $path = $request->file('logo')->store('images/company', 'public');
        CompanySetting::setValue($key, $path);

        // Broadcast updated settings to other users
        $allSettings = CompanySetting::getAllSettings();
        if (!empty($allSettings['logo_invoice_path'])) {
            $allSettings['logo_invoice_url'] = asset('storage/' . $allSettings['logo_invoice_path']);
        }
        if (!empty($allSettings['logo_invoice_body_path'])) {
            $allSettings['logo_invoice_body_url'] = asset('storage/' . $allSettings['logo_invoice_body_path']);
        }
        $this->broadcastChange('CompanySetting', 'updated', 0, $allSettings);

        return response()->json([
            'message' => 'Logo uploaded successfully',
            'path'    => $path,
            'url'     => asset('storage/' . $path),
        ]);
    }

    /**
     * Public endpoint – returns logo URLs without authentication.
     * Used by login page and other unauthenticated views.
     */
    public function publicLogos()
    {
        $logos = [];

        $invoicePath = CompanySetting::getValue('logo_invoice_path');
        if ($invoicePath) {
            $logos['logo_invoice_url'] = asset('storage/' . $invoicePath);
        }

        $bodyPath = CompanySetting::getValue('logo_invoice_body_path');
        if ($bodyPath) {
            $logos['logo_invoice_body_url'] = asset('storage/' . $bodyPath);
        }

        return response()->json($logos);
    }
}
