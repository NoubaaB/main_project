<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('invoice_layout_settings', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_type', 30)->unique(); // 'client' or 'supplier'

            // Header wrap parameters
            $table->string('header_divided_height', 30)->default('3cm');
            $table->string('header_strick_height', 30)->default('0.3cm');
            $table->string('header_primary_color', 30)->default('#006064');
            $table->string('header_secondary_color', 30)->default('#4DD0E1');
            $table->string('header_text_color', 30)->default('#006064');
            $table->string('header_bg_color', 30)->default('#FFFFFF');
            $table->string('header_style', 30)->default('curved'); // curved, slant, straight, rounded, gradient, minimal
            $table->string('header_logo_width', 30)->default('6cm');
            $table->string('header_title_size', 30)->default('xxx-large');
            $table->string('header_title_text', 100)->nullable();

            // Footer wrap parameters
            $table->string('footer_divided_height', 30)->default('3cm');
            $table->string('footer_strick_height', 30)->default('0.3cm');
            $table->string('footer_primary_color', 30)->default('#006064');
            $table->string('footer_secondary_color', 30)->default('#4DD0E1');
            $table->string('footer_text_color', 30)->default('#006064');
            $table->string('footer_bg_color', 30)->default('#FFFFFF');
            $table->string('footer_style', 30)->default('curved'); // curved, slant, straight, rounded, gradient, minimal
            $table->string('footer_director_title', 100)->nullable();
            $table->string('footer_director_size', 30)->default('x-large');
            $table->string('footer_line_style', 30)->default('dashed'); // dashed, solid, dotted, none
            $table->boolean('footer_show_director')->default(true);

            // Optional extra JSON options
            $table->json('options')->nullable();

            $table->timestamps();
        });

        // Seed initial default layouts
        DB::table('invoice_layout_settings')->insert([
            [
                'invoice_type' => 'client',
                'header_divided_height' => '3cm',
                'header_strick_height' => '0.3cm',
                'header_primary_color' => '#006064',
                'header_secondary_color' => '#4DD0E1',
                'header_text_color' => '#006064',
                'header_bg_color' => '#FFFFFF',
                'header_style' => 'curved',
                'header_logo_width' => '6cm',
                'header_title_size' => 'xxx-large',
                'header_title_text' => null,
                'footer_divided_height' => '3cm',
                'footer_strick_height' => '0.3cm',
                'footer_primary_color' => '#006064',
                'footer_secondary_color' => '#4DD0E1',
                'footer_text_color' => '#006064',
                'footer_bg_color' => '#FFFFFF',
                'footer_style' => 'curved',
                'footer_director_title' => null,
                'footer_director_size' => 'x-large',
                'footer_line_style' => 'dashed',
                'footer_show_director' => true,
                'options' => json_encode([]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'invoice_type' => 'supplier',
                'header_divided_height' => '3cm',
                'header_strick_height' => '0.3cm',
                'header_primary_color' => '#01579B',
                'header_secondary_color' => '#4FC3F7',
                'header_text_color' => '#01579B',
                'header_bg_color' => '#FFFFFF',
                'header_style' => 'curved',
                'header_logo_width' => '6cm',
                'header_title_size' => 'xxx-large',
                'header_title_text' => null,
                'footer_divided_height' => '3cm',
                'footer_strick_height' => '0.3cm',
                'footer_primary_color' => '#01579B',
                'footer_secondary_color' => '#4FC3F7',
                'footer_text_color' => '#01579B',
                'footer_bg_color' => '#FFFFFF',
                'footer_style' => 'curved',
                'footer_director_title' => null,
                'footer_director_size' => 'x-large',
                'footer_line_style' => 'dashed',
                'footer_show_director' => true,
                'options' => json_encode([]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_layout_settings');
    }
};
