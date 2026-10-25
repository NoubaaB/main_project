<template>
  <v-card class="fm-card" elevation="0">
    <!-- Hero Header -->
    <div class="fm-hero">
      <div class="fm-hero-inner">
        <div class="d-flex align-center ga-4">
          <div class="fm-hero-icon">
            <v-icon size="28" color="white">mdi-cash-lock-open</v-icon>
          </div>
          <div>
            <div class="fm-hero-title">{{ $t('static_charge_management') }}</div>
            <div class="text-white text-caption" style="opacity:.7">{{ $t('static_charge_management_desc') }}</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Toolbar with SegmentedPillToggle (Fish Management style) -->
    <div class="fm-toolbar-card mx-4 mt-3 mb-2 pa-2 rounded-xl border d-flex flex-wrap align-center justify-space-between ga-3">
      <div class="d-flex flex-wrap align-center ga-3">
        <SegmentedPillToggle
          v-model="activeTab"
          :items="[
            { value: 'charges', label: $t('static_charges'), icon: 'mdi-format-list-bulleted', badge: store.list.length },
            { value: 'info', label: $t('how_it_works'), icon: 'mdi-information-outline' }
          ]"
          color="#00BCD4"
        />
      </div>
    </div>

    <v-card-text class="pa-4">
      <v-window v-model="activeTab">
        <!-- ============= STATIC CHARGES TAB ============= -->
        <v-window-item value="charges">
          <div class="d-flex align-center ga-3 mb-3">
            <v-spacer></v-spacer>
            <v-btn
              v-if="hasActiveColumnFilters"
              variant="tonal"
              color="warning"
              rounded
              size="small"
              prepend-icon="mdi-filter-off"
              @click="clearAllColumnFilters"
            >
              {{ $t('clear_filters') }}
            </v-btn>
            <v-btn color="#00BCD4" prepend-icon="mdi-plus" rounded @click="openAddDialog">
              {{ $t('add_static_charge') }}
            </v-btn>
          </div>

          <!-- Empty State -->
          <div v-if="!store.list.length && !store.loading" class="text-center pa-8 text-grey">
            <v-icon size="56" color="grey-lighten-2">mdi-cash-lock-open</v-icon>
            <div class="mt-3 text-body-1">{{ $t('no_static_charges_found') }}</div>
            <v-btn
              class="mt-4"
              variant="flat"
              style="background-color: #00BCD4; color: #fff;"
              rounded
              prepend-icon="mdi-plus"
              @click="openAddDialog"
            >
              {{ $t('add_static_charge') }}
            </v-btn>
          </div>

          <!-- Data Table with Multi-Sort & Column Filters -->
          <v-data-table
            v-else
            :headers="headers"
            :items="filteredCharges"
            :loading="store.loading ? '#00BCD4' : false"
            :multi-sort="true"
            density="comfortable"
            class="rounded-lg param-table"
            hover
          >
            <template v-slot:headers="{ columns, isSorted, getSortIcon, toggleSort }">
              <tr>
                <th v-for="col in columns" :key="col.key" class="param-th">
                  <div
                    class="param-th-label"
                    @click="col.sortable !== false ? toggleSort(col) : null"
                    :style="{ cursor: col.sortable !== false ? 'pointer' : 'default' }"
                  >
                    {{ col.title }}
                    <v-icon v-if="isSorted(col)" size="14" class="ms-1">{{ getSortIcon(col) }}</v-icon>
                  </div>
                </th>
              </tr>
              <tr class="param-filter-row">
                <th v-for="col in columns" :key="col.key + '-f'" class="param-filter-th">
                  <v-autocomplete
                    v-if="col.key !== 'actions'"
                    v-model="columnFilters[col.key]"
                    :items="getColumnOptions(col.key)"
                    density="compact"
                    variant="outlined"
                    color="#00BCD4"
                    class="param-filter-input"
                    hide-details
                    clearable
                    multiple
                    chips
                    closable-chips
                    :placeholder="$t('filter')"
                  >
                    <template #chip="{ item, props }">
                      <v-chip v-bind="props" size="x-small" color="#00BCD4" variant="tonal" closable>
                        {{ item.title }}
                      </v-chip>
                    </template>
                  </v-autocomplete>
                </th>
              </tr>
            </template>

            <template v-slot:[`item.name`]="{ item }">
              <span class="font-weight-medium">{{ getLocalizedName(item) }}</span>
            </template>

            <template v-slot:[`item.amount`]="{ item }">
              <div class="text-center font-weight-bold fnm-currency-num" style="color: #00838F;">
                {{ formatNumber(item.amount) }} {{ main.currency }}
              </div>
            </template>

            <template v-slot:[`item.date_range_type`]="{ item }">
              <div class="text-center">
                <v-chip
                  size="small"
                  :color="dateRangeChipColor(item.date_range_type)"
                  variant="tonal"
                  label
                >
                  <v-icon start size="14">{{ dateRangeIcon(item.date_range_type) }}</v-icon>
                  {{ $t(item.date_range_type) }}
                </v-chip>
              </div>
            </template>

            <template v-slot:[`item.date_start`]="{ item }">
              <v-chip v-if="item.date_start" size="small" color="#00BCD4" variant="tonal" label>
                <v-icon start size="14">mdi-calendar-start</v-icon>
                {{ formatDate(item.date_start) }}
              </v-chip>
              <span v-else class="text-grey text-caption">—</span>
            </template>

            <template v-slot:[`item.date_end`]="{ item }">
              <v-chip v-if="item.date_end" size="small" color="warning" variant="tonal" label>
                <v-icon start size="14">mdi-calendar-end</v-icon>
                {{ formatDate(item.date_end) }}
              </v-chip>
              <v-chip v-else-if="item.date_range_type === 'ongoing'" size="small" color="success" variant="tonal" label>
                <v-icon start size="14">mdi-infinity</v-icon>
                {{ $t('ongoing') }}
              </v-chip>
              <v-chip v-else-if="item.date_range_type === 'all_time'" size="small" color="info" variant="tonal" label>
                <v-icon start size="14">mdi-infinity</v-icon>
                {{ $t('all_time') }}
              </v-chip>
              <span v-else class="text-grey text-caption">—</span>
            </template>

            <template v-slot:[`item.is_active`]="{ item }">
              <div class="text-center">
                <v-icon :color="item.is_active ? 'success' : 'grey'">
                  {{ item.is_active ? 'mdi-check-circle' : 'mdi-close-circle' }}
                </v-icon>
              </div>
            </template>

            <template v-slot:[`item.actions`]="{ item }">
              <div class="d-flex align-center justify-center">
                <v-btn icon size="x-small" variant="tonal" color="#00BCD4" @click="openEditDialog(item)" class="mr-1">
                  <v-icon size="16">mdi-pencil</v-icon>
                  <v-tooltip activator="parent">{{ $t('edit') }}</v-tooltip>
                </v-btn>
                <v-btn icon size="x-small" variant="tonal" color="error" @click="confirmDelete(item)">
                  <v-icon size="16">mdi-delete</v-icon>
                  <v-tooltip activator="parent">{{ $t('delete') }}</v-tooltip>
                </v-btn>
              </div>
            </template>
          </v-data-table>
        </v-window-item>

        <!-- ============= HOW IT WORKS TAB ============= -->
        <v-window-item value="info">
          <v-card variant="outlined" class="rounded-xl border" color="#00BCD4">
            <v-card-title class="d-flex align-center pa-4 pb-2">
              <v-icon color="#00BCD4" class="mr-2">mdi-information-outline</v-icon>
              <span class="text-subtitle-1 font-weight-bold" style="color: #00838F;">{{ $t('how_it_works') }}</span>
            </v-card-title>
            <v-card-text class="pa-4 pt-2">
              <div class="sc-info-item mb-3">
                <v-icon size="18" color="#00BCD4" class="mr-2">mdi-infinity</v-icon>
                <span class="text-body-2">{{ $t('static_charge_info_all_time') }}</span>
              </div>
              <div class="sc-info-item mb-3">
                <v-icon size="18" color="warning" class="mr-2">mdi-calendar-range</v-icon>
                <span class="text-body-2">{{ $t('static_charge_info_date_range') }}</span>
              </div>
              <div class="sc-info-item mb-3">
                <v-icon size="18" color="success" class="mr-2">mdi-calendar-arrow-right</v-icon>
                <span class="text-body-2">{{ $t('static_charge_info_ongoing') }}</span>
              </div>
              <div class="sc-info-item">
                <v-icon size="18" color="#00BCD4" class="mr-2">mdi-chart-areaspline</v-icon>
                <span class="text-body-2">{{ $t('static_charge_info_charts') }}</span>
              </div>
            </v-card-text>
          </v-card>
        </v-window-item>
      </v-window>
    </v-card-text>

    <!-- Add/Edit Dialog -->
    <v-dialog v-model="dialog.show" max-width="680" persistent scrollable>
      <v-card class="rounded-xl overflow-hidden" elevation="24">
        <!-- Dialog Header Banner -->
        <div class="sc-dialog-hero pa-5">
          <div class="d-flex align-center justify-space-between">
            <div class="d-flex align-center ga-3">
              <div class="sc-dialog-hero-icon">
                <v-icon color="white" size="26">{{ dialog.editId ? 'mdi-pencil-box-outline' : 'mdi-plus-circle-outline' }}</v-icon>
              </div>
              <div>
                <div class="sc-dialog-title text-white font-weight-black">
                  {{ dialog.editId ? $t('edit_static_charge') : $t('add_static_charge') }}
                </div>
                <div class="text-caption text-white opacity-80">
                  {{ $t('static_charge_dialog_subtitle') || 'Paramétrez les charges d\'exploitation avec horizon temporel flexible' }}
                </div>
              </div>
            </div>
            <v-btn icon variant="text" size="small" color="white" @click="dialog.show = false">
              <v-icon>mdi-close</v-icon>
            </v-btn>
          </div>
        </div>

        <v-card-text class="pa-5" style="max-height: 75vh;">
          <!-- Section 1: Multilingual Name with Tab Switcher -->
          <div class="sc-section-card rounded-xl pa-4 mb-4 border">
            <div class="d-flex align-center justify-space-between mb-2">
              <div class="d-flex align-center ga-2">
                <v-icon color="#00BCD4" size="18">mdi-translate</v-icon>
                <span class="text-caption font-weight-bold text-medium-emphasis">
                  {{ $t('charge_name_and_translations') || 'Nom de la charge & Traductions' }}
                </span>
              </div>
              <div class="d-flex align-center ga-1">
                <v-btn-toggle v-model="dialogLangTab" mandatory density="compact" rounded="lg" color="#00BCD4" variant="outlined">
                  <v-btn value="en" size="x-small" class="font-weight-bold">
                    <span class="mr-1">🇬🇧</span> EN
                    <v-icon v-if="dialog.form.name_en" size="10" color="success" class="ml-1">mdi-check</v-icon>
                  </v-btn>
                  <v-btn value="fr" size="x-small" class="font-weight-bold">
                    <span class="mr-1">🇫🇷</span> FR
                    <v-icon v-if="dialog.form.name_fr" size="10" color="success" class="ml-1">mdi-check</v-icon>
                  </v-btn>
                  <v-btn value="es" size="x-small" class="font-weight-bold">
                    <span class="mr-1">🇪🇸</span> ES
                    <v-icon v-if="dialog.form.name_es" size="10" color="success" class="ml-1">mdi-check</v-icon>
                  </v-btn>
                </v-btn-toggle>
                <v-tooltip :text="$t('copy_to_all_languages') || 'Copier vers toutes les langues'" location="top">
                  <template #activator="{ props }">
                    <v-btn
                      v-bind="props"
                      icon="mdi-content-copy"
                      size="x-small"
                      variant="tonal"
                      color="#00BCD4"
                      class="ml-1"
                      @click="copyActiveNameToAll"
                    />
                  </template>
                </v-tooltip>
              </div>
            </div>

            <!-- Active Language Name Input -->
            <v-text-field
              v-if="dialogLangTab === 'en'"
              v-model="dialog.form.name_en"
              :label="$t('name') + ' (EN)'"
              placeholder="e.g. Factory Electricity, Cold Storage Lease..."
              density="compact"
              variant="outlined"
              rounded="lg"
              color="#00BCD4"
              prepend-inner-icon="mdi-format-title"
              hide-details="auto"
            ></v-text-field>
            <v-text-field
              v-else-if="dialogLangTab === 'fr'"
              v-model="dialog.form.name_fr"
              :label="$t('name') + ' (FR)'"
              placeholder="Ex: Électricité Usine, Loyer Entrepôt..."
              density="compact"
              variant="outlined"
              rounded="lg"
              color="#00BCD4"
              prepend-inner-icon="mdi-format-title"
              hide-details="auto"
            ></v-text-field>
            <v-text-field
              v-else-if="dialogLangTab === 'es'"
              v-model="dialog.form.name_es"
              :label="$t('name') + ' (ES)'"
              placeholder="Ej: Electricidad Fábrica, Alquiler Almacén..."
              density="compact"
              variant="outlined"
              rounded="lg"
              color="#00BCD4"
              prepend-inner-icon="mdi-format-title"
              hide-details="auto"
            ></v-text-field>
          </div>

          <!-- Section 2: Amount & Active Status -->
          <v-row dense class="mb-4" align="center">
            <v-col cols="12" sm="7">
              <v-text-field
                v-model.number="dialog.form.amount"
                :label="$t('static_charge_amount')"
                density="compact"
                variant="outlined"
                rounded="lg"
                color="#00BCD4"
                type="number"
                min="0"
                step="0.01"
                :prefix="main.currency"
                prepend-inner-icon="mdi-cash-multiple"
                hide-details="auto"
              ></v-text-field>
            </v-col>
            <v-col cols="12" sm="5">
              <div class="sc-status-toggle-box rounded-lg pa-2 d-flex align-center justify-space-between border">
                <div class="d-flex align-center ga-2">
                  <v-icon :color="dialog.form.is_active ? 'success' : 'grey'" size="18">
                    {{ dialog.form.is_active ? 'mdi-check-circle' : 'mdi-close-circle' }}
                  </v-icon>
                  <span class="text-caption font-weight-bold">
                    {{ dialog.form.is_active ? ($t('active') || 'Actif') : ($t('inactive') || 'Inactif') }}
                  </span>
                </div>
                <v-switch
                  v-model="dialog.form.is_active"
                  color="#00BCD4"
                  density="compact"
                  hide-details
                ></v-switch>
              </div>
            </v-col>
          </v-row>

          <!-- Section 3: Validity Horizon (3 Cards) -->
          <div class="mb-4">
            <div class="text-caption font-weight-bold text-medium-emphasis mb-2">
              {{ $t('date_range_type') }}
            </div>
            <v-row dense>
              <v-col v-for="mode in staticDateModes" :key="mode.value" cols="12" sm="4">
                <div
                  class="sc-mode-card cursor-pointer"
                  :class="{ 'is-selected': dialog.form.date_range_type === mode.value }"
                  @click="dialog.form.date_range_type = mode.value"
                >
                  <div class="d-flex align-center justify-space-between mb-1">
                    <v-icon :color="dialog.form.date_range_type === mode.value ? '#00BCD4' : 'grey'" size="20">
                      {{ mode.icon }}
                    </v-icon>
                    <v-icon v-if="dialog.form.date_range_type === mode.value" color="#00BCD4" size="16">
                      mdi-radiobox-marked
                    </v-icon>
                    <v-icon v-else color="grey" size="16">mdi-radiobox-blank</v-icon>
                  </div>
                  <div class="mode-title font-weight-bold text-caption">{{ mode.title }}</div>
                  <div class="mode-desc text-caption text-medium-emphasis">{{ mode.desc }}</div>
                </div>
              </v-col>
            </v-row>
          </div>

          <!-- Section 4: Calendar / Presets (when not All Time) -->
          <div v-if="dialog.form.date_range_type !== 'all_time'" class="mb-4">
            <!-- Quick Presets Toolbar -->
            <div class="d-flex align-center ga-1 flex-wrap mb-2">
              <span class="text-caption text-medium-emphasis mr-1 font-weight-medium">
                {{ $t('quick_presets') || 'Périodes rapides' }}:
              </span>
              <v-btn
                v-for="p in datePresets"
                :key="p.id"
                size="x-small"
                variant="tonal"
                color="#00838F"
                rounded="lg"
                class="font-weight-medium"
                @click="applyDatePreset(p.id)"
              >
                {{ p.label }}
              </v-btn>
            </div>

            <!-- Date Display Strip with Duration -->
            <div class="d-flex align-center justify-space-between rounded-lg pa-2 mb-2 sc-date-summary-strip border">
              <div class="d-flex align-center ga-2 flex-wrap">
                <v-chip size="small" color="#00BCD4" variant="tonal" class="font-weight-bold">
                  <v-icon start size="14">mdi-calendar-start</v-icon>
                  {{ formatDate(dialog.dateRange.start) }}
                </v-chip>
                <v-icon size="14" color="grey">mdi-arrow-right</v-icon>
                <v-chip
                  size="small"
                  :color="dialog.form.date_range_type === 'ongoing' ? 'success' : '#00BCD4'"
                  variant="tonal"
                  class="font-weight-bold"
                >
                  <v-icon start size="14">{{ dialog.form.date_range_type === 'ongoing' ? 'mdi-infinity' : 'mdi-calendar-end' }}</v-icon>
                  {{ dialog.form.date_range_type === 'ongoing' ? ($t('ongoing') || 'Indéterminé') : formatDate(dialog.dateRange.end) }}
                </v-chip>
              </div>
              <v-chip size="x-small" variant="outlined" color="#00838F" class="font-weight-medium">
                {{ dialogDurationDays }}
              </v-chip>
            </div>

            <!-- Calendar Picker -->
            <div class="sc-calendar-container rounded-xl overflow-hidden border">
              <DatePicker
                v-if="dialog.form.date_range_type === 'date_range'"
                :is-dark="main.is_dark"
                v-model.range="dialog.dateRange"
                mode="date"
                :locale="main.lang?.short || 'en'"
                expanded
                color="teal"
              />
              <DatePicker
                v-if="dialog.form.date_range_type === 'ongoing'"
                :is-dark="main.is_dark"
                v-model="dialog.dateRange.start"
                mode="date"
                :locale="main.lang?.short || 'en'"
                expanded
                color="teal"
              />
            </div>
          </div>

          <!-- All Time Friendly Alert -->
          <v-alert
            v-if="dialog.form.date_range_type === 'all_time'"
            variant="tonal"
            color="#00BCD4"
            density="compact"
            rounded="lg"
            icon="mdi-infinity"
            class="text-caption mb-4"
          >
            {{ $t('static_charge_all_time_hint') }}
          </v-alert>

          <!-- Section 5: Description Note -->
          <div class="mb-3">
            <v-textarea
              v-model="dialog.form.description"
              :label="$t('description')"
              density="compact"
              variant="outlined"
              rounded="lg"
              color="#00BCD4"
              rows="2"
              auto-grow
              :counter="1000"
              :maxlength="1000"
              placeholder="Notes d'exploitation, modalités de calcul ou contrat..."
              hide-details="auto"
            ></v-textarea>
          </div>

          <!-- Section 6: Live Charge Preview Card -->
          <div class="sc-preview-card rounded-xl pa-3 border">
            <div class="d-flex align-center justify-space-between mb-2">
              <div class="d-flex align-center ga-2">
                <v-avatar size="22" color="rgba(0, 188, 212, 0.15)">
                  <v-icon color="#00838F" size="13">mdi-eye-outline</v-icon>
                </v-avatar>
                <span class="text-caption font-weight-bold text-medium-emphasis">
                  {{ $t('charge_preview') || 'Aperçu dans le tableau' }}
                </span>
              </div>
              <v-chip size="x-small" :color="dialog.form.is_active ? 'success' : 'grey'" variant="tonal" class="font-weight-bold">
                {{ dialog.form.is_active ? ($t('active') || 'Actif') : ($t('inactive') || 'Inactif') }}
              </v-chip>
            </div>
            <div class="d-flex align-center justify-space-between flex-wrap ga-2">
              <div>
                <div class="text-subtitle-2 font-weight-bold text-truncate" style="max-width: 280px;">
                  {{ activeDialogPreviewName }}
                </div>
                <div class="text-caption text-medium-emphasis">
                  <v-icon size="12" class="mr-1" :color="dateRangeChipColor(dialog.form.date_range_type)">
                    {{ dateRangeIcon(dialog.form.date_range_type) }}
                  </v-icon>
                  <span v-if="dialog.form.date_range_type === 'all_time'">{{ $t('all_time') }}</span>
                  <span v-else-if="dialog.form.date_range_type === 'ongoing'">{{ formatDate(dialog.dateRange.start) }} → {{ $t('ongoing') }}</span>
                  <span v-else>{{ formatDate(dialog.dateRange.start) }} — {{ formatDate(dialog.dateRange.end) }}</span>
                </div>
              </div>
              <div class="text-end">
                <div class="text-subtitle-1 font-weight-black" style="color: #00838F;">
                  {{ formatNumber(dialog.form.amount) }} {{ main.currency }}
                </div>
                <div class="text-caption text-medium-emphasis">
                  {{ $t(dialog.form.date_range_type) }}
                </div>
              </div>
            </div>
          </div>
        </v-card-text>

        <!-- Dialog Footer Actions -->
        <v-card-actions class="pa-4 bg-surface border-t">
          <v-btn variant="text" color="grey-darken-1" rounded="lg" @click="dialog.show = false">
            {{ $t('cancel') }}
          </v-btn>
          <v-spacer></v-spacer>
          <v-btn
            variant="flat"
            color="#00BCD4"
            class="text-white font-weight-bold px-5"
            rounded="lg"
            :loading="dialog.saving"
            :disabled="!canSave || dialog.saving"
            prepend-icon="mdi-content-save"
            @click="saveDialog"
          >
            {{ dialog.editId ? $t('update') : $t('save') }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Delete Confirm Dialog -->
    <v-dialog v-model="deleteDialog.show" max-width="420">
      <v-card class="rounded-xl">
        <v-card-title class="d-flex align-center pa-4">
          <v-icon color="error" class="mr-2">mdi-alert-circle</v-icon>
          <span>{{ $t('confirm_delete_static_charge') }}</span>
        </v-card-title>
        <v-card-text class="pa-4 pt-0">
          {{ $t('confirm_delete_static_charge_text') }}
          <div v-if="deleteDialog.charge" class="mt-3 pa-3 rounded-lg" style="background: rgba(244,67,54,.06);">
            <strong>{{ getLocalizedName(deleteDialog.charge) }}</strong>
            <br>
            {{ formatNumber(deleteDialog.charge.amount) }} {{ main.currency }}
            <span class="text-grey ml-2">·</span>
            <span class="ml-2">{{ $t(deleteDialog.charge.date_range_type) }}</span>
          </div>
        </v-card-text>
        <v-card-actions class="pa-4">
          <v-btn variant="text" rounded @click="deleteDialog.show = false">{{ $t('cancel') }}</v-btn>
          <v-spacer></v-spacer>
          <v-btn
            variant="flat"
            color="error"
            rounded
            :loading="deleteDialog.deleting"
            prepend-icon="mdi-delete"
            @click="doDelete"
          >
            {{ $t('delete') }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Summary Snackbar -->
    <v-snackbar v-model="snackbar.show" :timeout="8000" location="top right" color="surface" rounded="lg" elevation="12" min-width="360" max-width="420" @update:modelValue="val => { if (!val) clearInterval(snackbarTimer); }">
      <v-progress-linear :model-value="snackbarProgress" color="#00BCD4" height="3" class="mb-2" rounded></v-progress-linear>
      <div class="d-flex align-center mb-2">
        <v-icon :color="snackbar.success ? 'success' : 'error'" class="mr-2">{{ snackbar.success ? 'mdi-check-circle' : 'mdi-alert-circle' }}</v-icon>
        <span class="text-subtitle-1 font-weight-bold">{{ snackbar.text }}</span>
      </div>
      <v-divider class="mb-2"></v-divider>
      <div v-if="snackbar.detail" class="d-flex flex-column ga-1 text-body-2">
        <div v-if="snackbar.detail.name"><v-icon size="14" color="#00BCD4" class="mr-1">mdi-tag</v-icon><span class="text-medium-emphasis">{{ $t('static_charge_name') }}:</span> <strong>{{ snackbar.detail.name }}</strong></div>
        <div v-if="snackbar.detail.amount"><v-icon size="14" color="success" class="mr-1">mdi-cash</v-icon><span class="text-medium-emphasis">{{ $t('static_charge_amount') }}:</span> <strong>{{ snackbar.detail.amount }} {{ main.currency }}</strong></div>
        <div v-if="snackbar.detail.dateType"><v-icon size="14" color="#00BCD4" class="mr-1">mdi-calendar</v-icon><span class="text-medium-emphasis">{{ $t('date_range_type') }}:</span> <strong>{{ snackbar.detail.dateType }}</strong></div>
      </div>
      <template #actions>
        <v-btn variant="text" size="small" icon="mdi-close" @click="snackbar.show = false"></v-btn>
      </template>
    </v-snackbar>
  </v-card>
</template>

<script>
import SegmentedPillToggle from '@/components/common/SegmentedPillToggle.vue';
import { useStaticCharge } from '@/stores/StaticCharge';
import { useMain } from '@/stores/Main';

export default {
  name: 'StaticChargeManagement',
  components: { SegmentedPillToggle },
  data() {
    return {
      activeTab: 'charges',
      columnFilters: {},
      dialogLangTab: 'en',
      dialog: {
        show: false,
        editId: null,
        saving: false,
        dateRange: { start: new Date(), end: new Date() },
        form: {
          name_en: '',
          name_fr: '',
          name_es: '',
          amount: 0,
          date_range_type: 'all_time',
          description: '',
          is_active: true,
        },
      },
      deleteDialog: {
        show: false,
        charge: null,
        deleting: false,
      },
      snackbar: { show: false, text: '', success: true, detail: null },
      snackbarProgress: 100,
      snackbarTimer: null,
    };
  },
  computed: {
    store() { return useStaticCharge(); },
    main() { return useMain(); },
    lang() { return this.main.lang?.code || 'en'; },
    datePresets() {
      return [
        { id: 'this_month', label: this.$t('this_month') || 'Ce mois' },
        { id: 'this_quarter', label: this.$t('this_quarter') || 'Ce trimestre' },
        { id: 'this_year', label: this.$t('this_year') || 'Cette année' },
        { id: 'next_month', label: this.$t('next_month') || 'Mois prochain' },
      ];
    },
    staticDateModes() {
      return [
        {
          value: 'all_time',
          icon: 'mdi-infinity',
          title: this.$t('all_time') || 'Plein temps',
          desc: this.$t('no_end_date_limit') || 'Appliqué en permanence sans restriction de dates',
        },
        {
          value: 'date_range',
          icon: 'mdi-calendar-range',
          title: this.$t('date_range') || 'Période définie',
          desc: this.$t('step_dates_desc') || 'Limité entre une date de début et de fin',
        },
        {
          value: 'ongoing',
          icon: 'mdi-calendar-arrow-right',
          title: this.$t('ongoing') || 'En cours',
          desc: this.$t('ongoing_from') || 'Prend effet dès la date de début indéfiniment',
        },
      ];
    },
    dialogDurationDays() {
      if (this.dialog.form.date_range_type === 'all_time') {
        return this.$t('no_end_date_limit') || 'Plein temps';
      }
      if (this.dialog.form.date_range_type === 'ongoing') {
        return `${this.$t('ongoing_from') || 'En cours depuis'} ${this.formatDate(this.dialog.dateRange.start)}`;
      }
      if (!this.dialog.dateRange.start || !this.dialog.dateRange.end) return '—';
      const diffTime = Math.abs(new Date(this.dialog.dateRange.end) - new Date(this.dialog.dateRange.start));
      const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
      return `${diffDays} ${this.$t('duration_days') || 'jours'}`;
    },
    activeDialogPreviewName() {
      const f = this.dialog.form;
      return f[`name_${this.lang}`] || f.name_fr || f.name_en || f.name_es || (this.$t('untitled_charge') || 'Charge sans nom');
    },
    headers() {
      return [
        { title: this.$t('static_charge_name'), key: 'name', align: 'start' },
        { title: this.$t('static_charge_amount'), key: 'amount', align: 'center' },
        { title: this.$t('date_range_type'), key: 'date_range_type', align: 'center' },
        { title: this.$t('date_start'), key: 'date_start', align: 'start' },
        { title: this.$t('date_end'), key: 'date_end', align: 'start' },
        { title: this.$t('status'), key: 'is_active', align: 'center' },
        { title: this.$t('actions'), key: 'actions', align: 'center', sortable: false },
      ];
    },
    hasActiveColumnFilters() {
      return Object.values(this.columnFilters).some(v => Array.isArray(v) ? v.length > 0 : !!v);
    },
    sortedCharges() {
      return [...this.store.list].sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
    },
    filteredCharges() {
      if (!this.hasActiveColumnFilters) return this.sortedCharges;
      return this.sortedCharges.filter(item => {
        for (const [key, filterValues] of Object.entries(this.columnFilters)) {
          if (!filterValues || (Array.isArray(filterValues) && filterValues.length === 0)) continue;
          let val = '';
          if (key === 'name') val = this.getLocalizedName(item);
          else if (key === 'amount') val = `${this.formatNumber(item.amount)} ${this.main.currency}`;
          else if (key === 'date_range_type') val = this.$t(item.date_range_type);
          else if (key === 'date_start') val = item.date_start ? this.formatDate(item.date_start) : '—';
          else if (key === 'date_end') {
            val = item.date_end
              ? this.formatDate(item.date_end)
              : (item.date_range_type === 'ongoing' ? this.$t('ongoing') : (item.date_range_type === 'all_time' ? this.$t('all_time') : '—'));
          } else if (key === 'is_active') {
            val = item.is_active ? this.$t('active') : this.$t('inactive');
          } else {
            val = String(item[key] || '');
          }

          if (Array.isArray(filterValues)) {
            if (!filterValues.includes(val)) return false;
          } else if (typeof filterValues === 'string') {
            if (!val.toLowerCase().includes(filterValues.toLowerCase())) return false;
          }
        }
        return true;
      });
    },
    dateRangeTypeOptions() {
      return [
        { title: this.$t('all_time'), value: 'all_time' },
        { title: this.$t('date_range'), value: 'date_range' },
        { title: this.$t('ongoing'), value: 'ongoing' },
      ];
    },
    canSave() {
      const f = this.dialog.form;
      if (!f.name_en && !f.name_fr && !f.name_es) return false;
      if (f.amount == null || f.amount < 0) return false;
      if (f.date_range_type === 'date_range' && (!this.dialog.dateRange.start || !this.dialog.dateRange.end)) return false;
      if (f.date_range_type === 'ongoing' && !this.dialog.dateRange.start) return false;
      return true;
    },
  },
  methods: {
    getLocalizedName(charge) {
      return charge[`name_${this.lang}`] || charge.name_fr || charge.name_en || charge.name_es || `#${charge.id}`;
    },
    copyActiveNameToAll() {
      const activeName = this.dialog.form[`name_${this.dialogLangTab}`];
      if (!activeName) return;
      this.dialog.form.name_en = activeName;
      this.dialog.form.name_fr = activeName;
      this.dialog.form.name_es = activeName;
      this.showSnackbar(this.$t('copied_to_all') || 'Copié vers toutes les langues', true, null);
    },
    applyDatePreset(id) {
      const now = new Date();
      let start = new Date(now.getFullYear(), now.getMonth(), 1);
      let end = new Date(now.getFullYear(), now.getMonth() + 1, 0);

      if (id === 'this_month') {
        start = new Date(now.getFullYear(), now.getMonth(), 1);
        end = new Date(now.getFullYear(), now.getMonth() + 1, 0);
      } else if (id === 'next_month') {
        start = new Date(now.getFullYear(), now.getMonth() + 1, 1);
        end = new Date(now.getFullYear(), now.getMonth() + 2, 0);
      } else if (id === 'this_quarter') {
        const q = Math.floor(now.getMonth() / 3);
        start = new Date(now.getFullYear(), q * 3, 1);
        end = new Date(now.getFullYear(), (q + 1) * 3, 0);
      } else if (id === 'this_year') {
        start = new Date(now.getFullYear(), 0, 1);
        end = new Date(now.getFullYear(), 11, 31);
      }

      this.dialog.dateRange = { start, end };
    },
    formatNumber(n) {
      if (n == null || isNaN(n)) return '0';
      return Number(n).toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    },
    formatDate(d) {
      if (!d) return '-';
      return this.main.formateDate(d);
    },
    formatDateForApi(d) {
      if (!d) return null;
      const dt = d instanceof Date ? d : new Date(d);
      const y = dt.getFullYear();
      const m = String(dt.getMonth() + 1).padStart(2, '0');
      const day = String(dt.getDate()).padStart(2, '0');
      return `${y}-${m}-${day}`;
    },
    getColumnOptions(key) {
      const set = new Set();
      this.sortedCharges.forEach(item => {
        let v = '';
        if (key === 'name') v = this.getLocalizedName(item);
        else if (key === 'amount') v = `${this.formatNumber(item.amount)} ${this.main.currency}`;
        else if (key === 'date_range_type') v = this.$t(item.date_range_type);
        else if (key === 'date_start') v = item.date_start ? this.formatDate(item.date_start) : '—';
        else if (key === 'date_end') {
          v = item.date_end
            ? this.formatDate(item.date_end)
            : (item.date_range_type === 'ongoing' ? this.$t('ongoing') : (item.date_range_type === 'all_time' ? this.$t('all_time') : '—'));
        } else if (key === 'is_active') {
          v = item.is_active ? this.$t('active') : this.$t('inactive');
        } else {
          v = item[key];
        }
        if (v !== undefined && v !== null && v !== '') set.add(String(v));
      });
      return Array.from(set).sort();
    },
    clearAllColumnFilters() {
      this.columnFilters = {};
    },
    dateRangeChipColor(type) {
      if (type === 'all_time') return 'info';
      if (type === 'ongoing') return 'success';
      return 'warning';
    },
    dateRangeIcon(type) {
      if (type === 'all_time') return 'mdi-infinity';
      if (type === 'ongoing') return 'mdi-calendar-arrow-right';
      return 'mdi-calendar-range';
    },
    openAddDialog() {
      this.dialog.editId = null;
      this.dialog.dateRange = { start: new Date(), end: new Date() };
      this.dialogLangTab = this.lang || 'en';
      this.dialog.form = {
        name_en: '',
        name_fr: '',
        name_es: '',
        amount: 0,
        date_range_type: 'all_time',
        description: '',
        is_active: true,
      };
      this.dialog.show = true;
    },
    openEditDialog(s) {
      this.dialog.editId = s.id;
      this.dialogLangTab = this.lang || 'en';
      this.dialog.form = {
        name_en: s.name_en || '',
        name_fr: s.name_fr || '',
        name_es: s.name_es || '',
        amount: parseFloat(s.amount) || 0,
        date_range_type: s.date_range_type || 'all_time',
        description: s.description || '',
        is_active: s.is_active ?? true,
      };
      this.dialog.dateRange = {
        start: s.date_start ? new Date(s.date_start) : new Date(),
        end: s.date_end ? new Date(s.date_end) : new Date(),
      };
      this.dialog.show = true;
    },
    async saveDialog() {
      this.dialog.saving = true;
      try {
        const payload = {
          name_en: this.dialog.form.name_en,
          name_fr: this.dialog.form.name_fr,
          name_es: this.dialog.form.name_es,
          amount: this.dialog.form.amount,
          date_range_type: this.dialog.form.date_range_type,
          date_start: this.dialog.form.date_range_type !== 'all_time' ? this.formatDateForApi(this.dialog.dateRange.start) : null,
          date_end: this.dialog.form.date_range_type === 'date_range' ? this.formatDateForApi(this.dialog.dateRange.end) : null,
          description: this.dialog.form.description,
          is_active: this.dialog.form.is_active,
        };

        const snapshot = {
          name: this.getLocalizedName(payload),
          amount: this.formatNumber(payload.amount),
          dateType: this.$t(payload.date_range_type),
        };

        if (this.dialog.editId) {
          await this.store.update(this.dialog.editId, payload);
          this.showSnackbar(this.$t('static_charge_updated'), true, snapshot);
        } else {
          await this.store.store(payload);
          this.showSnackbar(this.$t('static_charge_created'), true, snapshot);
        }
        this.dialog.show = false;
      } catch (e) {
        this.showSnackbar(this.$t('error'), false, null);
      }
      this.dialog.saving = false;
    },
    confirmDelete(s) {
      this.deleteDialog.charge = s;
      this.deleteDialog.show = true;
    },
    async doDelete() {
      if (!this.deleteDialog.charge) return;
      this.deleteDialog.deleting = true;
      try {
        const snapshot = {
          name: this.getLocalizedName(this.deleteDialog.charge),
          amount: this.formatNumber(this.deleteDialog.charge.amount),
          dateType: this.$t(this.deleteDialog.charge.date_range_type),
        };
        await this.store.destroy(this.deleteDialog.charge.id);
        this.deleteDialog.show = false;
        this.showSnackbar(this.$t('static_charge_deleted'), true, snapshot);
      } catch (e) {
        this.showSnackbar(this.$t('error'), false, null);
      }
      this.deleteDialog.deleting = false;
    },
    showSnackbar(text, success, detail) {
      clearInterval(this.snackbarTimer);
      this.snackbar = { show: true, text, success, detail };
      this.snackbarProgress = 100;
      this.snackbarTimer = setInterval(() => {
        this.snackbarProgress -= 1.25;
        if (this.snackbarProgress <= 0) { clearInterval(this.snackbarTimer); }
      }, 100);
    },
  },
  async mounted() {
    if (!this.store.list.length) {
      await this.store.fetch();
    }
  },
};
</script>

<style scoped>
.fm-card { border-radius: 20px !important; overflow: visible; position: relative; font-family: 'Inter', sans-serif; }
.fm-hero { background: linear-gradient(135deg, #0097A7 0%, #00BCD4 40%, #4DD0E1 100%); border-radius: inherit; border-bottom-left-radius: 0; border-bottom-right-radius: 0; height: 100px; }
.fm-hero-inner { position: relative; z-index: 1; padding: 22px 26px; color: #fff; }
.fm-hero-icon { width: 52px; height: 52px; border-radius: 14px; background: rgba(255,255,255,.18); backdrop-filter: blur(6px); display: flex; align-items: center; justify-content: center; border: 1px solid rgba(255,255,255,.15); }
.fm-hero-title { font-size: 19px; font-weight: 800; letter-spacing: .3px; }
.fm-toolbar-card { background: rgba(var(--v-theme-surface), 0.7); backdrop-filter: blur(8px); }
.param-table { border: 1px solid rgba(0,188,212,.15); }
.param-th { padding: 10px 8px 4px !important; }
.param-th-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .4px; opacity: .7; text-align: center; white-space: nowrap; cursor: pointer; }
.param-filter-row th { padding: 4px 6px 8px !important; }
.param-filter-input :deep(.v-field) { border-radius: 8px !important; font-size: 11px !important; }
.param-filter-input :deep(.v-field__input) { padding: 2px 6px !important; min-height: 28px !important; }
.param-filter-input :deep(.v-chip) { height: 18px !important; font-size: 10px !important; }
.sc-info-item {
  display: flex;
  align-items: flex-start;
}
.fnm-currency-num {
  font-family: 'JetBrains Mono', 'Roboto Mono', monospace !important;
  font-variant-numeric: tabular-nums !important;
  font-feature-settings: "tnum" 1 !important;
}

/* Dialog Redesign Styles */
.sc-dialog-hero {
  background: linear-gradient(135deg, #00838F 0%, #00BCD4 60%, #26C6DA 100%);
  position: relative;
  box-shadow: 0 4px 20px -2px rgba(0, 188, 212, 0.25);
}
.sc-dialog-hero-icon {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.2);
  backdrop-filter: blur(8px);
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid rgba(255, 255, 255, 0.25);
}
.sc-dialog-title {
  font-size: 17px;
  line-height: 1.2;
}
.sc-section-card {
  background: rgba(var(--v-theme-on-surface), 0.02);
  border-color: rgba(0, 188, 212, 0.15) !important;
}
.sc-status-toggle-box {
  background: rgba(var(--v-theme-on-surface), 0.02);
  border-color: rgba(var(--v-theme-on-surface), 0.08) !important;
  height: 40px;
}
.sc-mode-card {
  padding: 12px 14px;
  border-radius: 12px;
  border: 1px solid rgba(var(--v-theme-on-surface), 0.08);
  background: rgba(var(--v-theme-on-surface), 0.015);
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  height: 100%;
}
.sc-mode-card:hover {
  background: rgba(0, 188, 212, 0.04);
  border-color: rgba(0, 188, 212, 0.25);
  transform: translateY(-1px);
}
.sc-mode-card.is-selected {
  background: rgba(0, 188, 212, 0.08);
  border-color: #00BCD4;
  box-shadow: 0 0 0 1px #00BCD4, 0 4px 12px -2px rgba(0, 188, 212, 0.15);
}
.sc-mode-card.is-selected .mode-title {
  color: #00838F;
}
.sc-date-summary-strip {
  background: rgba(0, 188, 212, 0.03);
  border-color: rgba(0, 188, 212, 0.15) !important;
}
.sc-calendar-container {
  border-color: rgba(0, 188, 212, 0.18) !important;
}
.sc-preview-card {
  background: linear-gradient(135deg, rgba(0, 188, 212, 0.04) 0%, rgba(0, 131, 143, 0.04) 100%);
  border-color: rgba(0, 188, 212, 0.2) !important;
}
</style>
