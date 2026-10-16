<template>
  <v-dialog v-model="internalOpen" max-width="1320" width="96vw" scrollable>
    <v-card class="rounded-xl overflow-hidden invoice-customizer-card d-flex flex-column" style="height: 90vh; max-height: 860px;">
      <!-- HERO HEADER -->
      <div class="customizer-hero px-5 py-3 text-white d-flex align-center justify-space-between flex-shrink-0" :style="{ background: heroGradient }">
        <div class="d-flex align-center">
          <div class="hero-icon-wrap mr-3">
            <v-icon size="24" color="white">mdi-palette-swatch-outline</v-icon>
          </div>
          <div>
            <h2 class="text-subtitle-1 font-weight-bold mb-0 d-flex align-center">
              {{ $t('invoice_layout_settings') }}
              <v-chip size="x-small" variant="flat" class="ml-2 font-weight-bold text-uppercase" :style="{ background: 'rgba(255,255,255,0.22)', color: '#fff' }">
                {{ invoiceType === 'client' ? $t('client_invoice') : $t('supplier_invoice') }}
              </v-chip>
            </h2>
            <p class="text-caption mb-0 text-white" style="opacity: 0.9; font-size: 11px !important;">
              {{ $t('customize_header_footer_desc') }}
            </p>
          </div>
        </div>
        <v-btn icon variant="text" color="white" size="small" @click="close">
          <v-icon>mdi-close</v-icon>
        </v-btn>
      </div>

      <!-- SPLIT MAIN BODY: CONTROLS (LEFT) + LIVE PREVIEW (RIGHT) -->
      <div class="customizer-split-body d-flex flex-column flex-md-row flex-grow-1 overflow-hidden" style="min-height: 0;">
        <!-- LEFT PANE: CONTROLS & PINNED TABS -->
        <div class="customizer-controls-pane d-flex flex-column" style="width: 490px; min-width: 430px; max-width: 520px; flex-shrink: 0; border-right: 1px solid rgba(0,0,0,0.1); background: #ffffff; min-height: 0;">
          <!-- TABS PINNED AT TOP OF CONTROLS (ALWAYS 100% VISIBLE) -->
          <div class="tabs-pin-bar bg-grey-lighten-5 border-b flex-shrink-0">
            <v-tabs v-model="tab" density="compact" :color="moduleColor" grow class="customizer-tabs px-1" :show-arrows="false">
              <v-tab value="header" class="text-none font-weight-bold" prepend-icon="mdi-page-layout-header">
                {{ $t('header_wrap_settings') }}
              </v-tab>
              <v-tab value="footer" class="text-none font-weight-bold" prepend-icon="mdi-page-layout-footer">
                {{ $t('footer_wrap_settings') }}
              </v-tab>
              <v-tab value="presets" class="text-none font-weight-bold" prepend-icon="mdi-auto-fix">
                {{ $t('color_presets') }}
              </v-tab>
            </v-tabs>
          </div>

          <!-- CONTROLS SCROLLABLE AREA -->
          <div class="controls-scroll pa-5 overflow-y-auto flex-grow-1" style="min-height: 0;">
        <!-- TAB 1: HEADER WRAP -->
        <div v-show="tab === 'header'">
          <div class="text-subtitle-2 font-weight-bold mb-3 d-flex align-center" :style="{ color: moduleColor }">
            <v-icon class="mr-2" size="18" :color="moduleColor">mdi-tune-variant</v-icon>
            {{ $t('header_size_and_dimensions') }}
          </div>

          <v-row dense>
            <v-col cols="12" md="6">
              <div class="mb-1 text-caption font-weight-medium">
                {{ $t('header_divided_height') }}: <strong>{{ form.header_divided_height }}</strong>
              </div>
              <v-slider
                v-model="headerDividedVal"
                min="2.0"
                max="5.0"
                step="0.1"
                thumb-label
                :color="moduleColor"
                track-color="grey-lighten-2"
                @update:model-value="onHeaderDividedSlider"
              >
                <template #append>
                  <span class="text-caption font-weight-bold">{{ form.header_divided_height }}</span>
                </template>
              </v-slider>
            </v-col>

            <v-col cols="12" md="6">
              <div class="mb-1 text-caption font-weight-medium">
                {{ $t('header_strick_height') }}: <strong>{{ form.header_strick_height }}</strong>
              </div>
              <v-slider
                v-model="headerStrickVal"
                min="0.1"
                max="1.0"
                step="0.05"
                thumb-label
                :color="moduleColor"
                track-color="grey-lighten-2"
                @update:model-value="onHeaderStrickSlider"
              >
                <template #append>
                  <span class="text-caption font-weight-bold">{{ form.header_strick_height }}</span>
                </template>
              </v-slider>
            </v-col>
          </v-row>

          <v-row dense>
            <v-col cols="12" md="6">
              <div class="mb-1 text-caption font-weight-medium">
                {{ $t('header_logo_width') }}: <strong>{{ form.header_logo_width }}</strong>
              </div>
              <v-slider
                v-model="headerLogoWidthVal"
                min="3.0"
                max="10.0"
                step="0.5"
                thumb-label
                :color="moduleColor"
                track-color="grey-lighten-2"
                @update:model-value="onHeaderLogoSlider"
              >
                <template #append>
                  <span class="text-caption font-weight-bold">{{ form.header_logo_width }}</span>
                </template>
              </v-slider>
            </v-col>

            <v-col cols="12" md="6">
              <v-select
                v-model="form.header_title_size"
                :items="fontSizeOptions"
                :label="$t('title_font_size')"
                variant="outlined"
                density="compact"
                rounded
                :color="moduleColor"
                hide-details
              ></v-select>
            </v-col>
          </v-row>

          <v-row dense class="mt-2">
            <v-col cols="12" md="6">
              <v-text-field
                v-model="form.header_title_text"
                :label="$t('header_title_override')"
                :placeholder="$t('invoice')"
                variant="outlined"
                density="compact"
                rounded
                :color="moduleColor"
                clearable
                hide-details
              ></v-text-field>
            </v-col>

            <v-col cols="12" md="6">
              <v-select
                v-model="form.header_style"
                :items="styleOptions"
                :label="$t('header_stylish_variant')"
                variant="outlined"
                density="compact"
                rounded
                :color="moduleColor"
                hide-details
              ></v-select>
            </v-col>
          </v-row>

          <v-divider class="my-4"></v-divider>

          <!-- Colors -->
          <div class="text-subtitle-2 font-weight-bold mb-3 d-flex align-center" :style="{ color: moduleColor }">
            <v-icon class="mr-2" size="18" :color="moduleColor">mdi-palette-outline</v-icon>
            {{ $t('header_colors') }}
          </div>

          <!-- Line 1: 2 columns (Primary Color & Secondary Color) -->
          <v-row dense class="mb-2">
            <v-col cols="6">
              <label class="text-caption font-weight-bold d-block mb-1 text-grey-darken-3">{{ $t('primary_color') }}</label>
              <div class="d-flex align-center">
                <input type="color" v-model="form.header_primary_color" class="color-picker-input mr-2 flex-shrink-0" />
                <v-text-field v-model="form.header_primary_color" density="compact" variant="outlined" rounded :color="moduleColor" hide-details></v-text-field>
              </div>
            </v-col>

            <v-col cols="6">
              <label class="text-caption font-weight-bold d-block mb-1 text-grey-darken-3">{{ $t('secondary_color') }}</label>
              <div class="d-flex align-center">
                <input type="color" v-model="form.header_secondary_color" class="color-picker-input mr-2 flex-shrink-0" />
                <v-text-field v-model="form.header_secondary_color" density="compact" variant="outlined" rounded :color="moduleColor" hide-details></v-text-field>
              </div>
            </v-col>
          </v-row>

          <!-- Line 2: 2 columns (Title Text Color & Title Background Color) -->
          <v-row dense>
            <v-col cols="6">
              <label class="text-caption font-weight-bold d-block mb-1 text-grey-darken-3">{{ $t('title_text_color') }}</label>
              <div class="d-flex align-center">
                <input type="color" v-model="form.header_text_color" class="color-picker-input mr-2 flex-shrink-0" />
                <v-text-field v-model="form.header_text_color" density="compact" variant="outlined" rounded :color="moduleColor" hide-details></v-text-field>
              </div>
            </v-col>

            <v-col cols="6">
              <label class="text-caption font-weight-bold d-block mb-1 text-grey-darken-3">{{ $t('title_bg_color') }}</label>
              <div class="d-flex align-center">
                <input type="color" v-model="form.header_bg_color" class="color-picker-input mr-2 flex-shrink-0" />
                <v-text-field v-model="form.header_bg_color" density="compact" variant="outlined" rounded :color="moduleColor" hide-details></v-text-field>
              </div>
            </v-col>
          </v-row>
        </div>

        <!-- TAB 2: FOOTER WRAP -->
        <div v-show="tab === 'footer'">
          <div class="text-subtitle-2 font-weight-bold mb-3 d-flex align-center" :style="{ color: moduleColor }">
            <v-icon class="mr-2" size="18" :color="moduleColor">mdi-tune-variant</v-icon>
            {{ $t('footer_size_and_dimensions') }}
          </div>

          <v-row dense>
            <v-col cols="12" md="6">
              <div class="mb-1 text-caption font-weight-medium">
                {{ $t('footer_divided_height') }}: <strong>{{ form.footer_divided_height }}</strong>
              </div>
              <v-slider
                v-model="footerDividedVal"
                min="2.0"
                max="5.0"
                step="0.1"
                thumb-label
                :color="moduleColor"
                track-color="grey-lighten-2"
                @update:model-value="onFooterDividedSlider"
              >
                <template #append>
                  <span class="text-caption font-weight-bold">{{ form.footer_divided_height }}</span>
                </template>
              </v-slider>
            </v-col>

            <v-col cols="12" md="6">
              <div class="mb-1 text-caption font-weight-medium">
                {{ $t('footer_strick_height') }}: <strong>{{ form.footer_strick_height }}</strong>
              </div>
              <v-slider
                v-model="footerStrickVal"
                min="0.1"
                max="1.0"
                step="0.05"
                thumb-label
                :color="moduleColor"
                track-color="grey-lighten-2"
                @update:model-value="onFooterStrickSlider"
              >
                <template #append>
                  <span class="text-caption font-weight-bold">{{ form.footer_strick_height }}</span>
                </template>
              </v-slider>
            </v-col>
          </v-row>

          <v-row dense>
            <v-col cols="12" md="6">
              <v-select
                v-model="form.footer_style"
                :items="styleOptions"
                :label="$t('footer_stylish_variant')"
                variant="outlined"
                density="compact"
                rounded
                :color="moduleColor"
                hide-details
              ></v-select>
            </v-col>

            <v-col cols="12" md="6">
              <v-select
                v-model="form.footer_line_style"
                :items="lineStyleOptions"
                :label="$t('signature_line_style')"
                variant="outlined"
                density="compact"
                rounded
                :color="moduleColor"
                hide-details
              ></v-select>
            </v-col>
          </v-row>

          <v-row dense class="mt-2">
            <v-col cols="12" md="6">
              <v-text-field
                v-model="form.footer_director_title"
                :label="$t('director_title_override')"
                :placeholder="$t('director')"
                variant="outlined"
                density="compact"
                rounded
                :color="moduleColor"
                clearable
                hide-details
              ></v-text-field>
            </v-col>

            <v-col cols="12" md="6">
              <v-select
                v-model="form.footer_director_size"
                :items="fontSizeOptions"
                :label="$t('director_font_size')"
                variant="outlined"
                density="compact"
                rounded
                :color="moduleColor"
                hide-details
              ></v-select>
            </v-col>
          </v-row>

          <v-row dense class="mt-2">
            <v-col cols="12">
              <v-switch
                v-model="form.footer_show_director"
                :label="$t('show_director_signature_block')"
                :color="moduleColor"
                inset
                density="compact"
                hide-details
              ></v-switch>
            </v-col>
          </v-row>

          <v-divider class="my-4"></v-divider>

          <!-- Colors -->
          <div class="text-subtitle-2 font-weight-bold mb-3 d-flex align-center" :style="{ color: moduleColor }">
            <v-icon class="mr-2" size="18" :color="moduleColor">mdi-palette-outline</v-icon>
            {{ $t('footer_colors') }}
          </div>

          <!-- Line 1: 2 columns (Primary Color & Secondary Color) -->
          <v-row dense class="mb-2">
            <v-col cols="6">
              <label class="text-caption font-weight-bold d-block mb-1 text-grey-darken-3">{{ $t('primary_color') }}</label>
              <div class="d-flex align-center">
                <input type="color" v-model="form.footer_primary_color" class="color-picker-input mr-2 flex-shrink-0" />
                <v-text-field v-model="form.footer_primary_color" density="compact" variant="outlined" rounded :color="moduleColor" hide-details></v-text-field>
              </div>
            </v-col>

            <v-col cols="6">
              <label class="text-caption font-weight-bold d-block mb-1 text-grey-darken-3">{{ $t('secondary_color') }}</label>
              <div class="d-flex align-center">
                <input type="color" v-model="form.footer_secondary_color" class="color-picker-input mr-2 flex-shrink-0" />
                <v-text-field v-model="form.footer_secondary_color" density="compact" variant="outlined" rounded :color="moduleColor" hide-details></v-text-field>
              </div>
            </v-col>
          </v-row>

          <!-- Line 2: 2 columns (Director Text Color & Director Background Color) -->
          <v-row dense>
            <v-col cols="6">
              <label class="text-caption font-weight-bold d-block mb-1 text-grey-darken-3">{{ $t('director_text_color') }}</label>
              <div class="d-flex align-center">
                <input type="color" v-model="form.footer_text_color" class="color-picker-input mr-2 flex-shrink-0" />
                <v-text-field v-model="form.footer_text_color" density="compact" variant="outlined" rounded :color="moduleColor" hide-details></v-text-field>
              </div>
            </v-col>

            <v-col cols="6">
              <label class="text-caption font-weight-bold d-block mb-1 text-grey-darken-3">{{ $t('director_bg_color') }}</label>
              <div class="d-flex align-center">
                <input type="color" v-model="form.footer_bg_color" class="color-picker-input mr-2 flex-shrink-0" />
                <v-text-field v-model="form.footer_bg_color" density="compact" variant="outlined" rounded :color="moduleColor" hide-details></v-text-field>
              </div>
            </v-col>
          </v-row>
        </div>

        <!-- TAB 3: PRESETS -->
        <div v-show="tab === 'presets'">
          <div class="text-subtitle-2 font-weight-bold mb-3 d-flex align-center" :style="{ color: moduleColor }">
            <v-icon class="mr-2" size="18" :color="moduleColor">mdi-brush</v-icon>
            {{ $t('curated_color_palettes') }}
          </div>
          <p class="text-caption text-medium-emphasis mb-3">
            {{ $t('apply_preset_desc') }}
          </p>

          <v-row dense>
            <v-col cols="12" sm="6" v-for="preset in colorPresets" :key="preset.name">
              <div
                class="preset-card pa-3 rounded-lg border d-flex align-center justify-space-between"
                @click="applyPreset(preset)"
              >
                <div class="d-flex align-center">
                  <div class="preset-swatch mr-3 d-flex overflow-hidden rounded">
                    <div :style="{ background: preset.primary, width: '22px', height: '22px' }"></div>
                    <div :style="{ background: preset.secondary, width: '22px', height: '22px' }"></div>
                  </div>
                  <div>
                    <div class="text-subtitle-2 font-weight-bold">{{ $t(preset.nameKey) }}</div>
                    <div class="text-caption text-grey">{{ preset.primary }} / {{ preset.secondary }}</div>
                  </div>
                </div>
                <v-btn size="x-small" variant="tonal" rounded :color="moduleColor">
                  {{ $t('apply') }}
                </v-btn>
              </div>
            </v-col>
          </v-row>
        </div>
      </div>
    </div>

    <!-- RIGHT PANE: LIVE PREVIEW & VISIBLE NICER TABLE -->
    <div class="customizer-preview-pane d-flex flex-column flex-grow-1" style="background: #eef2f6; min-width: 0; min-height: 0;">
          <!-- PREVIEW TOOLBAR -->
          <div class="preview-toolbar px-4 py-2 bg-white border-b d-flex align-center justify-space-between flex-shrink-0">
            <div class="d-flex align-center">
              <v-icon size="18" class="mr-2" :color="moduleColor">mdi-eye-outline</v-icon>
              <span class="text-caption font-weight-bold text-uppercase text-medium-emphasis">
                {{ $t('live_preview') }}
              </span>
              <v-chip size="x-small" variant="tonal" :color="moduleColor" class="ml-2 font-weight-bold text-uppercase">
                {{ invoiceType === 'client' ? $t('client_invoice') : $t('supplier_invoice') }}
              </v-chip>
            </div>
            <div class="d-flex align-center ga-2">
              <v-chip size="x-small" variant="outlined" color="grey-darken-1" class="font-weight-medium">
                {{ form.header_style || 'curved' }} • {{ form.header_divided_height }} / {{ form.footer_divided_height }}
              </v-chip>
            </div>
          </div>

          <!-- PREVIEW VIEWPORT CANVAS -->
          <div class="preview-canvas-viewport pa-4 overflow-y-auto overflow-x-hidden flex-grow-1 d-flex justify-center" style="min-height: 0;">
            <div class="invoice-preview-sheet elevation-3 rounded-lg overflow-hidden bg-white mb-2" style="width: 100%; max-width: 740px; align-self: flex-start;">
              <!-- Real Header Wrap -->
              <div class="header-wrap">
                <v-row no-gutters>
                  <v-col cols="12" class="header-wrap-strick" :style="{ backgroundColor: form.header_primary_color || moduleColor, height: form.header_strick_height || '0.3cm' }">
                  </v-col>
                </v-row>
                <v-row no-gutters class="header-wrap-divided" :style="{ background: previewHeaderDividedBg, height: form.header_divided_height || '3cm' }">
                  <v-col cols="4" class="text-center-h-v pl-3" :style="{ backgroundColor: form.header_primary_color || moduleColor }">
                    <img class="header-img" :style="{ width: form.header_logo_width || '6cm', maxWidth: '100%', maxHeight: '100%', objectFit: 'contain' }" :src="'/storage/images/invoice_title.png'"/>
                  </v-col>
                  <template v-if="form.header_style === 'curved' || !form.header_style">
                    <v-col cols="2" class="header-cursev-dark" :style="{ backgroundColor: form.header_primary_color || moduleColor }"></v-col>
                    <v-col cols="2" class="header-cursev-light" :style="{ backgroundColor: form.header_bg_color || '#FFFFFF' }"></v-col>
                  </template>
                  <template v-else-if="form.header_style === 'slant'">
                    <v-col cols="3" class="header-slant-dark" :style="{ backgroundColor: form.header_primary_color || moduleColor }"></v-col>
                    <v-col cols="1" class="header-slant-light" :style="{ backgroundColor: form.header_bg_color || '#FFFFFF' }"></v-col>
                  </template>
                  <template v-else-if="form.header_style === 'straight'">
                    <v-col cols="auto" style="width: 14px; background: rgba(0,0,0,0.12);"></v-col>
                  </template>
                  <template v-else-if="form.header_style === 'rounded'">
                    <v-col cols="2" style="border-top-right-radius: 40px; border-bottom-right-radius: 40px;" :style="{ backgroundColor: form.header_primary_color || moduleColor }"></v-col>
                    <v-col cols="1"></v-col>
                  </template>
                  <template v-else-if="form.header_style === 'minimal'">
                    <v-col cols="auto" style="width: 4px; background: rgba(0,0,0,0.08);"></v-col>
                  </template>
                  <v-col cols="*" class="text-center-h-v invoice-title px-2" :style="{ backgroundColor: form.header_bg_color || '#FFFFFF', color: form.header_text_color || moduleColor, fontSize: previewTitleFontSize }">
                    {{ form.header_title_text || $t('invoice') }}
                  </v-col>
                </v-row>
              </div>

              <!-- Nicer and Visible Middle Invoice Body Section -->
              <div class="preview-middle-body px-5 py-4 bg-white border-y">
                <!-- Meta strip: Company & Client info -->
                <div class="preview-meta-grid pa-3 rounded-lg mb-3 bg-grey-lighten-5 border d-flex justify-space-between align-center flex-wrap ga-2">
                  <div>
                    <div class="d-flex align-center">
                      <div class="d-flex align-center mr-2" :style="{ color: form.header_primary_color || moduleColor }">
                        <span v-for="n in 3" :key="n" style="font-weight: 900; font-size: 13px; margin-right: -2px; line-height: 1;">›</span>
                      </div>
                      <v-icon size="15" :color="form.header_primary_color || moduleColor" class="mr-1">mdi-domain</v-icon>
                      <span class="text-caption font-weight-bold text-grey-darken-3">CONGELATION VILLAMAR S.A.R.L</span>
                    </div>
                    <div class="text-caption text-grey-darken-1 pl-4" style="font-size: 10px !important;">
                      Zone Portuaire, Mehdia, Kenitra • Maroc
                    </div>
                  </div>
                  <div class="text-right">
                    <div class="d-flex align-center justify-end px-3 py-1 rounded-s-xl text-white font-weight-bold" :style="{ backgroundColor: form.header_secondary_color || (invoiceType === 'client' ? '#4DD0E1' : '#4FC3F7') }">
                      <span class="text-caption mr-1" style="font-size: 10.5px;">{{ $t('invoice_n°') }}:</span>
                      <strong class="text-caption font-weight-bold" style="font-size: 11px;">INV-2026-0042</strong>
                    </div>
                    <div class="text-caption text-grey-darken-1 mt-1" style="font-size: 10px !important;">
                      {{ $t('date') }}: 2026-10-16 • {{ invoiceType === 'client' ? $t('client') : $t('supplier') }}: ATLANTIC TRADING
                    </div>
                  </div>
                </div>

                <!-- Visible & Nicer Invoice Table -->
                <div class="preview-table-container rounded-lg overflow-hidden border mb-3">
                  <table class="preview-table w-100" style="border-collapse: collapse; font-size: 11px;">
                    <thead>
                      <tr class="text-white text-caption font-weight-bold" :style="{ backgroundColor: form.header_primary_color || moduleColor }">
                        <th class="py-2 px-2 text-center" style="width: 32px;">#</th>
                        <th class="py-2 px-2 text-center" style="width: 80px;">{{ $t('date') }}</th>
                        <th class="py-2 px-3 text-left">{{ $t('description') }}</th>
                        <th class="py-2 px-2 text-right" style="width: 95px;">{{ $t('weight') }} (Kg)</th>
                        <th class="py-2 px-2 text-right" style="width: 85px;">{{ $t('price') }}</th>
                        <th class="py-2 px-3 text-right" style="width: 105px;">{{ $t('total_price') }}</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="(item, idx) in previewItems" :key="idx" class="preview-table-row" :style="{ backgroundColor: idx % 2 === 1 ? '#f8fafc' : '#ffffff' }">
                        <td class="py-2 px-2 text-center text-grey-darken-1 font-weight-medium border-b">{{ idx + 1 }}</td>
                        <td class="py-2 px-2 text-center text-grey-darken-2 border-b font-weight-medium">{{ item.date }}</td>
                        <td class="py-2 px-3 text-left border-b">
                          <span class="font-weight-bold text-grey-darken-3 d-block">{{ item.desc }}</span>
                          <span class="text-grey-darken-1 d-block" style="font-size: 9.5px;">{{ item.sub }}</span>
                        </td>
                        <td class="py-2 px-2 text-right text-grey-darken-3 font-weight-medium border-b">{{ item.weight }}</td>
                        <td class="py-2 px-2 text-right text-grey-darken-2 border-b">{{ item.price }}</td>
                        <td class="py-2 px-3 text-right font-weight-bold text-grey-darken-4 border-b">{{ item.total }} {{ currencyShort }}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>

                <!-- Summary Card (Amount in words & totals) -->
                <div class="preview-summary-card d-flex align-center justify-space-between flex-wrap ga-3">
                  <div class="words-pill pa-2 px-3 rounded-lg border border-dashed flex-grow-1" :style="{ borderColor: (form.header_primary_color || moduleColor) + '70', background: 'rgba(0,0,0,0.015)' }">
                    <span class="text-caption font-weight-bold d-block" :style="{ color: form.header_primary_color || moduleColor, fontSize: '10.5px !important' }">
                      {{ $t('present invoice') }}:
                    </span>
                    <span class="text-caption text-grey-darken-2 font-italic font-weight-medium d-block" style="font-size: 10px !important;">
                      Deux Cent Quatre Mille Deux Cent Cinquante Dirhams (204,250.00 MAD)
                    </span>
                  </div>
                  <div class="totals-pill-wrap d-flex flex-column ga-1" style="min-width: 175px;">
                    <div class="d-flex align-center justify-space-between px-3 py-1 rounded text-white font-weight-bold" :style="{ backgroundColor: form.header_secondary_color || (invoiceType === 'client' ? '#4DD0E1' : '#4FC3F7') }">
                      <span class="text-caption" style="font-size: 10.5px;">{{ $t('total_weight') }}:</span>
                      <strong style="font-size: 11px;">5,350.00 Kg</strong>
                    </div>
                    <div class="d-flex align-center justify-space-between px-3 py-1 rounded text-white font-weight-bold" :style="{ backgroundColor: form.header_primary_color || moduleColor }">
                      <span class="text-caption" style="font-size: 11px;">{{ $t('total_price') }}:</span>
                      <strong style="font-size: 12px;">204,250.00 {{ currencyShort }}</strong>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Real Footer Wrap -->
              <div class="footer-wrap">
                <v-row no-gutters class="footer-wrap-divided" :style="{ background: previewFooterDividedBg, height: form.footer_divided_height || '3cm' }">
                  <v-col v-if="form.footer_show_director !== false" cols="4" class="text-center px-8 invoice-director" :style="{ backgroundColor: form.footer_bg_color || '#FFFFFF', color: form.footer_text_color || moduleColor }">
                    <div class="mb-2" :style="{ fontSize: previewDirectorFontSize }">
                      {{ form.footer_director_title || $t("director") }}
                    </div>
                    <v-divider opacity=".7" thickness="2" class="mb-6" :style="{ borderColor: (form.footer_primary_color || moduleColor) + ' !important' }"></v-divider>
                    <v-divider v-if="form.footer_line_style !== 'none'" opacity=".5" thickness="2" :variant="form.footer_line_style || 'dashed'" class="mx-5" :style="{ borderColor: (form.footer_secondary_color || (invoiceType === 'client' ? '#4DD0E1' : '#4FC3F7')) + ' !important' }"></v-divider>
                  </v-col>
                  <template v-if="form.footer_style === 'curved' || !form.footer_style">
                    <v-col cols="2" class="footer-cursev-light" :style="{ backgroundColor: form.footer_bg_color || '#FFFFFF' }"></v-col>
                    <v-col cols="2" class="footer-cursev-dark" :style="{ backgroundColor: form.footer_primary_color || moduleColor }"></v-col>
                  </template>
                  <template v-else-if="form.footer_style === 'slant'">
                    <v-col cols="1" class="footer-slant-light" :style="{ backgroundColor: form.footer_bg_color || '#FFFFFF' }"></v-col>
                    <v-col cols="3" class="footer-slant-dark" :style="{ backgroundColor: form.footer_primary_color || moduleColor }"></v-col>
                  </template>
                  <template v-else-if="form.footer_style === 'straight'">
                    <v-col cols="auto" style="width: 14px; background: rgba(0,0,0,0.12);"></v-col>
                  </template>
                  <template v-else-if="form.footer_style === 'rounded'">
                    <v-col cols="1"></v-col>
                    <v-col cols="2" style="border-top-left-radius: 40px; border-bottom-left-radius: 40px;" :style="{ backgroundColor: form.footer_primary_color || moduleColor }"></v-col>
                  </template>
                  <template v-else-if="form.footer_style === 'minimal'">
                    <v-col cols="auto" style="width: 4px; background: rgba(0,0,0,0.08);"></v-col>
                  </template>
                  <v-col cols="*" :style="{ backgroundColor: form.footer_primary_color || moduleColor }"></v-col>
                </v-row>
                <v-row no-gutters>
                  <v-col cols="12" class="footer-wrap-strick" :style="{ backgroundColor: form.footer_primary_color || moduleColor, height: form.footer_strick_height || '0.3cm' }">
                  </v-col>
                </v-row>
              </div>
            </div>
          </div>
        </div>
      </div>

      <v-divider></v-divider>

      <!-- CARD ACTIONS -->
      <v-card-actions class="pa-4 bg-grey-lighten-5 d-flex align-center justify-space-between">
        <v-btn
          variant="text"
          color="error"
          rounded
          prepend-icon="mdi-restore"
          @click="confirmResetDialog = true"
          :loading="layoutStore.saving"
        >
          {{ $t('reset_to_defaults') }}
        </v-btn>

        <div class="d-flex ga-2">
          <v-btn variant="text" rounded @click="close">
            {{ $t('cancel') }}
          </v-btn>
          <v-btn
            :style="{ background: moduleColor, color: '#fff' }"
            variant="flat"
            rounded
            prepend-icon="mdi-content-save-check"
            @click="save"
            :loading="layoutStore.saving"
            class="px-5 font-weight-bold"
          >
            {{ $t('save_parameters') }}
          </v-btn>
        </div>
      </v-card-actions>
    </v-card>

    <!-- CONFIRM RESET DIALOG -->
    <v-dialog v-model="confirmResetDialog" max-width="420">
      <v-card class="rounded-xl">
        <v-card-title class="pa-5 pb-2 d-flex align-center text-error">
          <v-icon color="error" class="mr-2">mdi-alert-circle-outline</v-icon>
          {{ $t('confirm_reset') }}
        </v-card-title>
        <v-card-text class="pa-5 pt-2 text-body-1">
          {{ $t('confirm_reset_invoice_layout_message') }}
        </v-card-text>
        <v-card-actions class="pa-5 pt-0">
          <v-spacer></v-spacer>
          <v-btn @click="confirmResetDialog = false" variant="text">{{ $t('cancel') }}</v-btn>
          <v-btn color="error" variant="flat" rounded @click="resetToDefaults" :loading="layoutStore.saving">
            {{ $t('reset') }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Summary Snackbar (Rule 5: WebSocket update and notification) -->
    <v-snackbar
      v-model="summarySnackbar.show"
      :timeout="6000"
      location="top right"
      color="surface"
      rounded="lg"
      elevation="12"
      min-width="360"
      max-width="420"
      @update:modelValue="val => { if (!val) clearInterval(snackbarTimer); }"
    >
      <v-progress-linear :model-value="snackbarProgress" :color="moduleColor" height="3" class="mb-2" rounded></v-progress-linear>
      <div class="d-flex align-center mb-2">
        <v-icon color="success" class="mr-2">mdi-check-circle</v-icon>
        <span class="text-subtitle-1 font-weight-bold">{{ summarySnackbar.title }}</span>
      </div>
      <v-divider class="mb-2"></v-divider>
      <div class="d-flex flex-column ga-1 text-body-2">
        <div>
          <v-icon size="14" :color="moduleColor" class="mr-1">mdi-page-layout-header</v-icon>
          <span class="text-medium-emphasis">{{ $t('header_style') }}:</span>
          <strong>{{ summarySnackbar.headerStyle }}</strong>
        </div>
        <div>
          <v-icon size="14" :color="moduleColor" class="mr-1">mdi-arrow-expand-vertical</v-icon>
          <span class="text-medium-emphasis">{{ $t('header_divided_height') }}:</span>
          <strong>{{ summarySnackbar.headerHeight }}</strong>
        </div>
        <div>
          <v-icon size="14" :color="moduleColor" class="mr-1">mdi-palette</v-icon>
          <span class="text-medium-emphasis">{{ $t('colors') }}:</span>
          <span class="d-inline-flex align-center ml-1">
            <span class="d-inline-block rounded-circle mr-1" :style="{ background: summarySnackbar.primaryColor, width: '10px', height: '10px' }"></span>
            <strong>{{ summarySnackbar.primaryColor }}</strong>
          </span>
        </div>
      </div>
      <template #actions>
        <v-btn variant="text" size="small" icon="mdi-close" @click="summarySnackbar.show = false"></v-btn>
      </template>
    </v-snackbar>
  </v-dialog>
</template>

<script>
import { useInvoiceLayout } from '@/stores/InvoiceLayout';
import mitt from '@/plugins/mitt';

export default {
  name: 'InvoiceLayoutCustomizer',
  props: {
    modelValue: { type: Boolean, default: false },
    invoiceType: { type: String, default: 'client' }, // 'client' or 'supplier'
  },
  emits: ['update:modelValue', 'saved', 'reset'],
  data() {
    return {
      tab: 'header',
      confirmResetDialog: false,
      form: this.getEmptyForm(),
      headerDividedVal: 3.0,
      headerStrickVal: 0.3,
      headerLogoWidthVal: 6.0,
      footerDividedVal: 3.0,
      footerStrickVal: 0.3,
      summarySnackbar: {
        show: false,
        title: '',
        headerStyle: '',
        headerHeight: '',
        primaryColor: '',
      },
      snackbarProgress: 100,
      snackbarTimer: null,
      currencyShort: 'MAD',
      previewItems: [
        { date: '01/10/2026', desc: 'Sardina Pilchardus (IQF)', sub: 'Calibre 12-16 pcs/kg • 150 Cartons', weight: '1,500.00', price: '30.00', total: '45,000.00' },
        { date: '02/10/2026', desc: 'Scomber Scombrus (Whole Frozen)', sub: 'Grading 300-500g • 120 Blocks', weight: '2,400.00', price: '22.50', total: '54,000.00' },
        { date: '02/10/2026', desc: 'Octopus Vulgaris (Cleaned Flower)', sub: 'T2 Flower IQF • 70 Cartons', weight: '850.00', price: '85.00', total: '72,250.00' },
        { date: '03/10/2026', desc: 'Sepia Officinalis (Block Frozen)', sub: 'Size 400-600g • 30 Blocks', weight: '600.00', price: '55.00', total: '33,000.00' },
      ],
      colorPresets: [
        { nameKey: 'preset_cyan_ocean', primary: '#006064', secondary: '#4DD0E1' },
        { nameKey: 'preset_deep_navy', primary: '#01579B', secondary: '#4FC3F7' },
        { nameKey: 'preset_warm_amber', primary: '#E65100', secondary: '#FFB74D' },
        { nameKey: 'preset_emerald_mint', primary: '#004D40', secondary: '#26A69A' },
        { nameKey: 'preset_royal_indigo', primary: '#1A237E', secondary: '#7986CB' },
        { nameKey: 'preset_slate_modern', primary: '#263238', secondary: '#78909C' },
        { nameKey: 'preset_crimson_rose', primary: '#880E4F', secondary: '#EC407A' },
        { nameKey: 'preset_purple_luxe', primary: '#4A148C', secondary: '#AB47BC' },
      ],
    };
  },
  computed: {
    styleOptions() {
      return [
        { title: this.$t('style_curved_wave'), value: 'curved' },
        { title: this.$t('style_diagonal_slant'), value: 'slant' },
        { title: this.$t('style_straight_split'), value: 'straight' },
        { title: this.$t('style_rounded_pill'), value: 'rounded' },
        { title: this.$t('style_luxe_gradient'), value: 'gradient' },
        { title: this.$t('style_minimal_accent'), value: 'minimal' },
      ];
    },
    fontSizeOptions() {
      return [
        { title: this.$t('font_size_medium'), value: 'medium' },
        { title: this.$t('font_size_large'), value: 'large' },
        { title: this.$t('font_size_xlarge'), value: 'x-large' },
        { title: this.$t('font_size_xxlarge'), value: 'xx-large' },
        { title: this.$t('font_size_xxxlarge'), value: 'xxx-large' },
      ];
    },
    lineStyleOptions() {
      return [
        { title: this.$t('line_style_dashed'), value: 'dashed' },
        { title: this.$t('line_style_solid'), value: 'solid' },
        { title: this.$t('line_style_dotted'), value: 'dotted' },
        { title: this.$t('line_style_none'), value: 'none' },
      ];
    },
    layoutStore() {
      return useInvoiceLayout();
    },
    internalOpen: {
      get() { return this.modelValue; },
      set(val) { this.$emit('update:modelValue', val); },
    },
    isClient() {
      return this.invoiceType === 'client';
    },
    moduleColor() {
      // Claude.md: Client invoice uses #00838F; Supplier invoice uses #E65100
      return this.isClient ? '#00838F' : '#E65100';
    },
    heroGradient() {
      return this.isClient
        ? 'linear-gradient(135deg, #006064 0%, #00838F 40%, #26C6DA 100%)'
        : 'linear-gradient(135deg, #E65100 0%, #F57C00 40%, #FFB74D 100%)';
    },
    previewHeaderDividedBg() {
      if (this.form.header_style === 'gradient') {
        return `linear-gradient(135deg, ${this.form.header_primary_color || this.moduleColor} 0%, ${this.form.header_secondary_color || (this.invoiceType === 'client' ? '#4DD0E1' : '#4FC3F7')} 100%)`;
      }
      return this.form.header_secondary_color || (this.invoiceType === 'client' ? '#4DD0E1' : '#4FC3F7');
    },
    previewFooterDividedBg() {
      if (this.form.footer_style === 'gradient') {
        return `linear-gradient(135deg, ${this.form.footer_primary_color || this.moduleColor} 0%, ${this.form.footer_secondary_color || (this.invoiceType === 'client' ? '#4DD0E1' : '#4FC3F7')} 100%)`;
      }
      return this.form.footer_secondary_color || (this.invoiceType === 'client' ? '#4DD0E1' : '#4FC3F7');
    },
    previewTitleFontSize() {
      const map = {
        'medium': '1.5rem',
        'large': '2rem',
        'x-large': '2.4rem',
        'xx-large': '3rem',
        'xxx-large': 'xxx-large',
      };
      return map[this.form.header_title_size] || this.form.header_title_size || 'xxx-large';
    },
    previewDirectorFontSize() {
      const map = {
        'medium': '1.1rem',
        'large': '1.3rem',
        'x-large': 'x-large',
        'xx-large': '1.8rem',
      };
      return map[this.form.footer_director_size] || this.form.footer_director_size || 'x-large';
    },
  },
  watch: {
    modelValue(isOpen) {
      if (isOpen) {
        this.loadCurrentSettings();
      }
    },
    invoiceType() {
      this.loadCurrentSettings();
    },
    form: {
      deep: true,
      handler() {
        this.syncLiveToStore();
      },
    },
  },
  mounted() {
    this.layoutStore.initSync();
    if (this.modelValue) {
      this.loadCurrentSettings();
    }
  },
  methods: {
    getEmptyForm() {
      return {
        header_divided_height: '3cm',
        header_strick_height: '0.3cm',
        header_primary_color: '#006064',
        header_secondary_color: '#4DD0E1',
        header_text_color: '#006064',
        header_bg_color: '#FFFFFF',
        header_style: 'curved',
        header_logo_width: '6cm',
        header_title_size: 'xxx-large',
        header_title_text: '',
        footer_divided_height: '3cm',
        footer_strick_height: '0.3cm',
        footer_primary_color: '#006064',
        footer_secondary_color: '#4DD0E1',
        footer_text_color: '#006064',
        footer_bg_color: '#FFFFFF',
        footer_style: 'curved',
        footer_director_title: '',
        footer_director_size: 'x-large',
        footer_line_style: 'dashed',
        footer_show_director: true,
        options: {},
      };
    },
    loadCurrentSettings() {
      const current = this.layoutStore.getLayout(this.invoiceType);
      if (current) {
        this.form = JSON.parse(JSON.stringify(current));
        this.headerDividedVal = parseFloat(current.header_divided_height) || 3.0;
        this.headerStrickVal = parseFloat(current.header_strick_height) || 0.3;
        this.headerLogoWidthVal = parseFloat(current.header_logo_width) || 6.0;
        this.footerDividedVal = parseFloat(current.footer_divided_height) || 3.0;
        this.footerStrickVal = parseFloat(current.footer_strick_height) || 0.3;
      }
    },
    onHeaderDividedSlider(val) {
      this.form.header_divided_height = `${val.toFixed(1)}cm`;
      this.syncLiveToStore();
    },
    onHeaderStrickSlider(val) {
      this.form.header_strick_height = `${val.toFixed(2)}cm`;
      this.syncLiveToStore();
    },
    onHeaderLogoSlider(val) {
      this.form.header_logo_width = `${val.toFixed(1)}cm`;
      this.syncLiveToStore();
    },
    onFooterDividedSlider(val) {
      this.form.footer_divided_height = `${val.toFixed(1)}cm`;
      this.syncLiveToStore();
    },
    onFooterStrickSlider(val) {
      this.form.footer_strick_height = `${val.toFixed(2)}cm`;
      this.syncLiveToStore();
    },
    applyPreset(preset) {
      this.form.header_primary_color = preset.primary;
      this.form.header_secondary_color = preset.secondary;
      this.form.header_text_color = preset.primary;
      this.form.footer_primary_color = preset.primary;
      this.form.footer_secondary_color = preset.secondary;
      this.form.footer_text_color = preset.primary;
      this.syncLiveToStore();
    },
    syncLiveToStore() {
      // Temporarily reflect in local store for instant live preview behind the dialog
      this.layoutStore.settings[this.invoiceType] = {
        ...this.layoutStore.settings[this.invoiceType],
        ...this.form,
      };
    },
    async save() {
      try {
        const payload = { ...this.form };
        const res = await this.layoutStore.saveSettings(this.invoiceType, payload);
        this.triggerSummarySnackbar(this.$t('invoice_layout_saved_success'));
        this.$emit('saved', res?.setting);
      } catch (e) {
        console.error('Save layout failed:', e);
      }
    },
    async resetToDefaults() {
      try {
        const res = await this.layoutStore.resetSettings(this.invoiceType);
        this.loadCurrentSettings();
        this.confirmResetDialog = false;
        this.triggerSummarySnackbar(this.$t('invoice_layout_reset_success'));
        this.$emit('reset', res?.setting);
      } catch (e) {
        console.error('Reset layout failed:', e);
      }
    },
    triggerSummarySnackbar(title) {
      this.summarySnackbar = {
        show: true,
        title,
        headerStyle: this.form.header_style,
        headerHeight: this.form.header_divided_height,
        primaryColor: this.form.header_primary_color,
      };
      this.snackbarProgress = 100;
      clearInterval(this.snackbarTimer);
      const startTime = Date.now();
      const duration = 6000;
      this.snackbarTimer = setInterval(() => {
        const elapsed = Date.now() - startTime;
        this.snackbarProgress = Math.max(0, 100 - (elapsed / duration) * 100);
        if (this.snackbarProgress <= 0) {
          clearInterval(this.snackbarTimer);
          this.summarySnackbar.show = false;
        }
      }, 50);
    },
    close() {
      // Revert store to database version in case changes weren't saved
      this.layoutStore.fetchSettings();
      this.internalOpen = false;
    },
  },
};
</script>

<style scoped>
.hero-icon-wrap {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.18);
  backdrop-filter: blur(6px);
  display: flex;
  align-items: center;
  justify-content: center;
}
.color-picker-input {
  width: 40px;
  height: 40px;
  min-width: 40px;
  border: 1.5px solid rgba(0, 0, 0, 0.15);
  border-radius: 8px;
  cursor: pointer;
  padding: 2px;
  background: #ffffff;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
  transition: border-color 0.2s, box-shadow 0.2s;
}
.color-picker-input:hover {
  border-color: rgba(0, 0, 0, 0.35);
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.12);
}
.preset-card {
  cursor: pointer;
  transition: all 0.2s ease;
  background: white;
}
.preset-card:hover {
  border-color: rgba(var(--v-theme-primary), 0.6) !important;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

/* ════ Real Invoice Layout Preview Sheet ════ */
.live-preview-section {
  background: #f8fafc;
}
.invoice-preview-sheet {
  background: white;
  border: 1px solid rgba(0, 0, 0, 0.12);
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
  border-radius: 8px;
  overflow: hidden;
  position: relative;
  width: 100%;
}
.header-wrap, .footer-wrap {
  position: relative;
  width: 100%;
}
.header-wrap-strick, .footer-wrap-strick {
  width: 100%;
  transition: background-color 0.15s ease, height 0.15s ease;
}
.header-wrap-divided, .footer-wrap-divided {
  display: flex;
  align-items: stretch;
  flex-wrap: nowrap;
  overflow: hidden;
  position: relative;
  width: 100%;
  transition: background 0.15s ease, height 0.15s ease;
}
.header-cursev-dark {
  clip-path: polygon(100% 0%, -1% -1% , -1.00% 100.00%, 1.00% 99.98%, 2.00% 99.90%, 3.00% 99.78%, 4.00% 99.61%, 5.00% 99.38%, 6.00% 99.11%, 7.00% 98.80%, 8.00% 98.43%, 9.00% 98.01%, 10.00% 97.55%, 11.00% 97.04%, 12.00% 96.49%, 13.00% 95.89%, 14.00% 95.24%, 15.00% 94.55%, 16.00% 93.82%, 17.00% 93.04%, 18.00% 92.22%, 19.00% 91.35%, 20.00% 90.45%, 21.00% 89.51%, 22.00% 88.53%, 23.00% 87.51%, 24.00% 86.45%, 25.00% 85.36%, 26.00% 84.23%, 27.00% 83.07%, 28.00% 81.87%, 29.00% 80.65%, 30.00% 79.39%, 31.00% 78.10%, 32.00% 76.79%, 33.00% 75.45%, 34.00% 74.09%, 35.00% 72.70%, 36.00% 71.29%, 37.00% 69.86%, 38.00% 68.41%, 39.00% 66.94%, 40.00% 65.45%, 41.00% 63.95%, 42.00% 62.43%, 43.00% 60.91%, 44.00% 59.37%, 45.00% 57.82%, 46.00% 56.27%, 47.00% 54.71%, 48.00% 53.14%, 49.00% 51.57%, 50.00% 50.00%, 51.00% 48.43%, 52.00% 46.86%, 53.00% 45.29%, 54.00% 43.73%, 55.00% 42.18%, 56.00% 40.63%, 57.00% 39.09%, 58.00% 37.57%, 59.00% 36.05%, 60.00% 34.55%, 61.00% 33.06%, 62.00% 31.59%, 63.00% 30.14%, 64.00% 28.71%, 65.00% 27.30%, 66.00% 25.91%, 67.00% 24.55%, 68.00% 23.21%, 69.00% 21.90%, 70.00% 20.61%, 71.00% 19.35%, 72.00% 18.13%, 73.00% 16.93%, 74.00% 15.77%, 75.00% 14.64%, 76.00% 13.55%, 77.00% 12.49%, 78.00% 11.47%, 79.00% 10.49%, 80.00% 9.55%, 81.00% 8.65%, 82.00% 7.78%, 83.00% 6.96%, 84.00% 6.18%, 85.00% 5.45%, 86.00% 4.76%, 87.00% 4.11%, 88.00% 3.51%, 89.00% 2.96%, 90.00% 2.45%, 91.00% 1.99%, 92.00% 1.57%, 93.00% 1.20%, 94.00% 0.89%, 95.00% 0.62%, 96.00% 0.39%, 97.00% 0.22%, 98.00% 0.10%, 99.00% 0.02%, 100.00% 0.00%);
}
.header-cursev-light {
  clip-path: polygon(115% 108%, 26% 101% , -9% 119.38%, 77% 120.35%, -0.5% 100.27%, 4% 99.15%, 4.5% 98.97%, 5% 98.74%, 6% 98.47%, 7% 98.14%, 8% 97.76%, 9.00% 97.34%, 10.00% 96.87%, 11.00% 96.35%, 12.00% 95.78%, 13.00% 95.16%, 14.00% 94.50%, 15.00% 93.79%, 16.00% 93.04%, 17.00% 92.24%, 18.00% 91.40%, 19.00% 90.51%, 20.00% 89.59%, 21.00% 88.62%, 22.00% 87.61%, 23.00% 86.57%, 24.00% 85.48%, 25.00% 84.36%, 26.00% 83.21%, 27.00% 82.02%, 28.00% 80.79%, 29.00% 79.54%, 30.00% 78.25%, 31.00% 76.93%, 32.00% 75.59%, 33.00% 74.21%, 34.00% 72.81%, 35.00% 71.39%, 36.00% 69.95%, 37.00% 68.48%, 38.00% 66.99%, 39.00% 65.49%, 40.00% 63.96%, 41.00% 62.42%, 42.00% 60.87%, 43.00% 59.30%, 44.00% 57.73%, 45.00% 56.14%, 46.00% 54.55%, 47.00% 52.95%, 48.00% 51.34%, 49.00% 49.73%, 50.00% 48.12%, 51.00% 46.52%, 52.00% 44.91%, 53.00% 43.30%, 54.00% 41.70%, 55.00% 40.11%, 56.00% 38.52%, 57.00% 36.95%, 58.00% 35.38%, 59.00% 33.83%, 60.00% 32.29%, 61.00% 30.76%, 62.00% 29.26%, 63.00% 27.77%, 64.00% 26.30%, 65.00% 24.86%, 66.00% 23.44%, 67.00% 22.04%, 68.00% 20.66%, 69.00% 19.32%, 70.00% 18.00%, 71.00% 16.71%, 72.00% 15.46%, 73.00% 14.23%, 74.00% 13.04%, 75.00% 11.89%, 76.00% 10.77%, 77.00% 9.68%, 78.00% 8.64%, 79.00% 7.63%, 80.00% 6.66%, 81.00% 5.74%, 82.00% 4.85%, 83.00% 4.01%, 84.00% 3.21%, 85.00% 2.46%, 86.00% 1.75%, 87.00% 1.09%, 88.00% 0.47%, 89.00% -0.10%, 90.00% -0.62%, 91.00% -1.09%, 92.00% -1.51%, 93.00% -1.89%, 94.00% -2.22%, 95.00% -2.49%, 96.00% -2.72%, 97.00% -2.90%, 98.00% -3.02%, 99.00% -3.10%, 100.00% -3.13%);
}
.header-slant-dark {
  clip-path: polygon(0 0, 100% 0, 45% 100%, 0 100%);
}
.header-slant-light {
  clip-path: polygon(45% 0, 100% 0, 100% 100%, 0 100%);
}
.footer-slant-light {
  clip-path: polygon(0 0, 100% 0, 55% 100%, 0 100%);
}
.footer-slant-dark {
  clip-path: polygon(55% 0, 100% 0, 100% 100%, 0 100%);
}
.footer-cursev-dark {
  clip-path: polygon(109% 158%, -4% 101% , -7% 101.14%, 3% 99.35%, 3% 99.27%, 3% 99.15%, 4% 98.97%, 5% 98.74%, 6% 98.47%, 7% 98.14%, 8% 97.76%, 9.00% 97.34%, 10.00% 96.87%, 11.00% 96.35%, 12.00% 95.78%, 13.00% 95.16%, 14.00% 94.50%, 15.00% 93.79%, 16.00% 93.04%, 17.00% 92.24%, 18.00% 91.40%, 19.00% 90.51%, 20.00% 89.59%, 21.00% 88.62%, 22.00% 87.61%, 23.00% 86.57%, 24.00% 85.48%, 25.00% 84.36%, 26.00% 83.21%, 27.00% 82.02%, 28.00% 80.79%, 29.00% 79.54%, 30.00% 78.25%, 31.00% 76.93%, 32.00% 75.59%, 33.00% 74.21%, 34.00% 72.81%, 35.00% 71.39%, 36.00% 69.95%, 37.00% 68.48%, 38.00% 66.99%, 39.00% 65.49%, 40.00% 63.96%, 41.00% 62.42%, 42.00% 60.87%, 43.00% 59.30%, 44.00% 57.73%, 45.00% 56.14%, 46.00% 54.55%, 47.00% 52.95%, 48% 51.34%, 49.00% 49.73%, 50.00% 48.12%, 51.00% 46.52%, 52.00% 44.91%, 53% 43.30%, 54.00% 41.70%, 55.00% 40.11%, 56.00% 38.52%, 57.00% 36.95%, 58.00% 35.38%, 59.00% 33.83%, 60.00% 32.29%, 61.00% 30.76%, 62.00% 29.26%, 63.00% 27.77%, 64.00% 26.30%, 65.00% 24.86%, 66.00% 23.44%, 67.00% 22.04%, 68.00% 20.66%, 69.00% 19.32%, 70.00% 18.00%, 71.00% 16.71%, 72.00% 15.46%, 73.00% 14.23%, 74.00% 13.04%, 75.00% 11.89%, 76.00% 10.77%, 77.00% 9.68%, 78.00% 8.64%, 79.00% 7.63%, 80.00% 6.66%, 81.00% 5.74%, 82.00% 4.85%, 83.00% 4.01%, 84.00% 3.21%, 85.00% 2.46%, 86.00% 1.75%, 87.00% 1.09%, 88.00% 0.47%, 89.00% -0.10%, 90.00% -0.62%, 91.00% -1.09%, 92.00% -1.51%, 93.00% -1.89%, 94.00% -2.22%, 95.00% -2.49%, 96.00% -2.72%, 98% 0%, 100% 0%, 99% 0%, 114% 0%);
}
.footer-cursev-light {
  clip-path: polygon(100% 0%, -3% -41% , -3% 101%, 1.00% 99.98%, 2.00% 100.9%, 3.00% 99.78%, 4.00% 99.61%, 5.00% 99.38%, 6.00% 99.11%, 7.00% 98.80%, 8.00% 98.43%, 9.00% 98.01%, 10.00% 97.55%, 11.00% 97.04%, 12.00% 96.49%, 13.00% 95.89%, 14.00% 95.24%, 15.00% 94.55%, 16.00% 93.82%, 17.00% 93.04%, 18.00% 92.22%, 19.00% 91.35%, 20.00% 90.45%, 21.00% 89.51%, 22.00% 88.53%, 23.00% 87.51%, 24.00% 86.45%, 25.00% 85.36%, 26.00% 84.23%, 27.00% 83.07%, 28.00% 81.87%, 29.00% 80.65%, 30.00% 79.39%, 31.00% 78.10%, 32.00% 76.79%, 33.00% 75.45%, 34.00% 74.09%, 35.00% 72.70%, 36.00% 71.29%, 37.00% 69.86%, 38.00% 68.41%, 39.00% 66.94%, 40.00% 65.45%, 41.00% 63.95%, 42.00% 62.43%, 43.00% 60.91%, 44.00% 59.37%, 45.00% 57.82%, 46.00% 56.27%, 47.00% 54.71%, 48.00% 53.14%, 49.00% 51.57%, 50.00% 50.00%, 51.00% 48.43%, 52.00% 46.86%, 53.00% 45.29%, 54.00% 43.73%, 55.00% 42.18%, 56.00% 40.63%, 57.00% 39.09%, 58.00% 37.57%, 59.00% 36.05%, 60.00% 34.55%, 61.00% 33.06%, 62.00% 31.59%, 63.00% 30.14%, 64.00% 28.71%, 65.00% 27.30%, 66.00% 25.91%, 67.00% 24.55%, 68.00% 23.21%, 69.00% 21.90%, 70.00% 20.61%, 71.00% 19.35%, 72.00% 18.13%, 73.00% 16.93%, 74.00% 15.77%, 75.00% 14.64%, 76.00% 13.55%, 77.00% 12.49%, 78.00% 11.47%, 79.00% 10.49%, 80.00% 9.55%, 81.00% 8.65%, 82.00% 7.78%, 83.00% 6.96%, 84.00% 6.18%, 85.00% 5.45%, 86.00% 4.76%, 87.00% 4.11%, 88.00% 3.51%, 89.00% 2.96%, 90.00% 2.45%, 91.00% 1.99%, 92.00% 1.57%, 93.00% 1.20%, 94.00% 0.89%, 95.00% 0.62%, 96.00% 0.39%, 97.00% 0.22%, 98.00% 0.10%, 100% -1.98%, 100.00% 0.00%);
}
.text-center-h-v {
  display: flex;
  justify-content: center;
  align-items: center;
}
.invoice-title {
  font-family: math;
  font-weight: 900;
  letter-spacing: 0.1rem;
  text-transform: uppercase;
}
.invoice-director {
  font-family: math;
  font-weight: 900;
  letter-spacing: 0.1rem;
}
.header-img {
  width: 6cm;
}

/* Split Layout Styling */
.customizer-split-body {
  position: relative;
  width: 100%;
}
.customizer-controls-pane {
  position: relative;
  display: flex;
  flex-direction: column;
}
.tabs-pin-bar {
  z-index: 2;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
}
.controls-scroll {
  scrollbar-width: thin;
}
.customizer-preview-pane {
  position: relative;
  display: flex;
  flex-direction: column;
}
.preview-canvas-viewport {
  scrollbar-width: thin;
}
.preview-table th {
  letter-spacing: 0.03rem;
  border: 1px solid rgba(255, 255, 255, 0.2);
}
.preview-table td {
  border-color: rgba(0, 0, 0, 0.08) !important;
}
.preview-table-row:hover {
  background-color: rgba(0, 0, 0, 0.03) !important;
}
.customizer-tabs :deep(.v-tab) {
  min-width: unset !important;
  padding: 0 10px !important;
  font-size: 11.5px !important;
  letter-spacing: normal !important;
}
.customizer-tabs :deep(.v-btn__prepend) {
  margin-inline-end: 4px !important;
}
@media (max-width: 959px) {
  .customizer-split-body {
    flex-direction: column !important;
    overflow-y: auto !important;
  }
  .customizer-controls-pane {
    width: 100% !important;
    max-width: 100% !important;
    border-right: none !important;
    border-bottom: 1px solid rgba(0, 0, 0, 0.1);
  }
}
</style>
