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
        if (!empty($settings['invoice_header_image_path'])) {
            $settings['invoice_header_image_url'] = asset('storage/' . $settings['invoice_header_image_path']);
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
        if (!empty($allSettings['invoice_header_image_path'])) {
            $allSettings['invoice_header_image_url'] = asset('storage/' . $allSettings['invoice_header_image_path']);
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
            'logo_type' => 'required|in:logo_invoice,logo_invoice_body,invoice_header_image',
            'logo'      => 'required|image|mimes:png,jpg,jpeg,svg,webp|max:4096',
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

        // Automatically convert solid white/near-white background to transparent
        // so the logo matches the applied theme mode (dark or light) seamlessly
        $this->ensureTransparentBackground($path);

        CompanySetting::setValue($key, $path);

        // Broadcast updated settings to other users
        $allSettings = CompanySetting::getAllSettings();
        if (!empty($allSettings['logo_invoice_path'])) {
            $allSettings['logo_invoice_url'] = asset('storage/' . $allSettings['logo_invoice_path']);
        }
        if (!empty($allSettings['logo_invoice_body_path'])) {
            $allSettings['logo_invoice_body_url'] = asset('storage/' . $allSettings['logo_invoice_body_path']);
        }
        if (!empty($allSettings['invoice_header_image_path'])) {
            $allSettings['invoice_header_image_url'] = asset('storage/' . $allSettings['invoice_header_image_path']);
        }
        $this->broadcastChange('CompanySetting', 'updated', 0, $allSettings);

        return response()->json([
            'message' => 'Logo uploaded successfully',
            'path'    => $path,
            'url'     => asset('storage/' . $path),
        ]);
    }

    /**
     * Delete an uploaded logo or invoice header image.
     */
    public function deleteLogo($type)
    {
        if (!in_array($type, ['logo_invoice', 'logo_invoice_body', 'invoice_header_image'])) {
            return response()->json(['message' => 'Invalid logo type'], 422);
        }

        $key = $type . '_path';
        $oldPath = CompanySetting::getValue($key);
        if ($oldPath && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }
        CompanySetting::setValue($key, null);

        $allSettings = CompanySetting::getAllSettings();
        if (!empty($allSettings['logo_invoice_path'])) {
            $allSettings['logo_invoice_url'] = asset('storage/' . $allSettings['logo_invoice_path']);
        }
        if (!empty($allSettings['logo_invoice_body_path'])) {
            $allSettings['logo_invoice_body_url'] = asset('storage/' . $allSettings['logo_invoice_body_path']);
        }
        if (!empty($allSettings['invoice_header_image_path'])) {
            $allSettings['invoice_header_image_url'] = asset('storage/' . $allSettings['invoice_header_image_path']);
        }
        $this->broadcastChange('CompanySetting', 'updated', 0, $allSettings);

        return response()->json([
            'message'  => 'Logo deleted successfully',
            'settings' => $allSettings,
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

        $headerPath = CompanySetting::getValue('invoice_header_image_path');
        if ($headerPath) {
            $logos['invoice_header_image_url'] = asset('storage/' . $headerPath);
        }

        return response()->json($logos);
    }

    /**
     * Checks if an uploaded logo has a solid white/near-white background and makes it transparent.
     * This ensures the logo matches the application theme mode (dark or light) seamlessly.
     */
    protected function ensureTransparentBackground(string $relativePath): void
    {
        try {
            $fullPath = Storage::disk('public')->path($relativePath);
            if (!file_exists($fullPath)) {
                return;
            }

            $info = @getimagesize($fullPath);
            if (!$info) {
                return;
            }

            $mime = $info['mime'] ?? '';
            $src = null;

            if ($mime === 'image/png') {
                $src = @imagecreatefrompng($fullPath);
            } elseif ($mime === 'image/jpeg') {
                $src = @imagecreatefromjpeg($fullPath);
            } elseif ($mime === 'image/webp' && function_exists('imagecreatefromwebp')) {
                $src = @imagecreatefromwebp($fullPath);
            }

            if (!$src) {
                return;
            }

            $w = imagesx($src);
            $h = imagesy($src);

            if ($w <= 0 || $h <= 0) {
                @imagedestroy($src);
                return;
            }

            // Check corner and perimeter samples
            $samples = [
                [0, 0], [$w - 1, 0], [0, $h - 1], [$w - 1, $h - 1],
                [(int)($w / 2), 0], [(int)($w / 2), $h - 1],
                [0, (int)($h / 2)], [$w - 1, (int)($h / 2)],
                [min(4, $w - 1), min(4, $h - 1)],
                [max(0, $w - 5), min(4, $h - 1)],
                [min(4, $w - 1), max(0, $h - 5)],
                [max(0, $w - 5), max(0, $h - 5)],
            ];

            $whiteCount = 0;
            foreach ($samples as $pt) {
                $rgba = imagecolorat($src, $pt[0], $pt[1]);
                $c = imagecolorsforindex($src, $rgba);
                // In GD, alpha 0 = fully opaque. Check for high RGB and opaque.
                if (($c['alpha'] ?? 0) === 0 && $c['red'] >= 235 && $c['green'] >= 235 && $c['blue'] >= 235) {
                    $whiteCount++;
                }
            }

            // If majority of sampled border points are white, transparentize
            if ($whiteCount >= 6) {
                $out = imagecreatetruecolor($w, $h);
                imagealphablending($out, false);
                imagesavealpha($out, true);

                for ($x = 0; $x < $w; $x++) {
                    for ($y = 0; $y < $h; $y++) {
                        $rgba = imagecolorat($src, $x, $y);
                        $c = imagecolorsforindex($src, $rgba);
                        $minC = min($c['red'], $c['green'], $c['blue']);

                        if ($minC >= 250) {
                            $col = imagecolorallocatealpha($out, 0, 0, 0, 127);
                        } elseif ($minC >= 220) {
                            $factor = ($minC - 220) / (250 - 220);
                            $alpha = (int)($factor * 127);
                            $col = imagecolorallocatealpha($out, $c['red'], $c['green'], $c['blue'], $alpha);
                        } else {
                            $col = imagecolorallocatealpha($out, $c['red'], $c['green'], $c['blue'], $c['alpha'] ?? 0);
                        }
                        imagesetpixel($out, $x, $y, $col);
                    }
                }

                imagepng($out, $fullPath);
                @imagedestroy($out);
            }

            @imagedestroy($src);
        } catch (\Throwable $e) {
            \Log::warning('Failed to transparentize logo background: ' . $e->getMessage());
        }
    }
}
