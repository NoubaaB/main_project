<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceLayoutSetting extends Model
{
    use HasFactory;

    protected $table = 'invoice_layout_settings';

    protected $guarded = [];

    protected $casts = [
        'footer_show_director' => 'boolean',
        'options' => 'array',
    ];

    /**
     * System default presets for client and supplier invoices.
     */
    public static function getDefaultSettings(string $type = 'client'): array
    {
        $isClient = $type === 'client';
        return [
            'invoice_type' => $type,
            'header_divided_height' => '3cm',
            'header_strick_height' => '0.3cm',
            'header_primary_color' => $isClient ? '#006064' : '#01579B',
            'header_secondary_color' => $isClient ? '#4DD0E1' : '#4FC3F7',
            'header_text_color' => $isClient ? '#006064' : '#01579B',
            'header_bg_color' => '#FFFFFF',
            'header_style' => 'curved',
            'header_logo_width' => '6cm',
            'header_title_size' => 'xxx-large',
            'header_title_text' => null,
            'footer_divided_height' => '3cm',
            'footer_strick_height' => '0.3cm',
            'footer_primary_color' => $isClient ? '#006064' : '#01579B',
            'footer_secondary_color' => $isClient ? '#4DD0E1' : '#4FC3F7',
            'footer_text_color' => $isClient ? '#006064' : '#01579B',
            'footer_bg_color' => '#FFFFFF',
            'footer_style' => 'curved',
            'footer_director_title' => null,
            'footer_director_size' => 'x-large',
            'footer_line_style' => 'dashed',
            'footer_show_director' => true,
            'options' => [],
        ];
    }

    /**
     * Get or create settings for a given invoice type.
     */
    public static function getForType(string $type): self
    {
        return static::firstOrCreate(
            ['invoice_type' => $type],
            static::getDefaultSettings($type)
        );
    }
}
