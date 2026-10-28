<template>
  <v-card class="wm-card" elevation="0">
    <!-- Hero Header -->
    <div class="wm-hero">
      <div class="wm-hero-inner">
        <div class="d-flex align-center ga-4">
          <div class="wm-hero-icon">
            <v-icon size="28" color="white">mdi-warehouse</v-icon>
          </div>
          <div>
            <div class="wm-hero-title">{{ $t('warehouse_management') }}</div>
            <div class="text-white text-caption" style="opacity:.7">{{ $t('manage_warehouses_categories') }}</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Toolbar with SegmentedPillToggle (Activity Breakdown style) -->
    <div class="wm-toolbar-card mx-4 mt-3 mb-2 pa-2 rounded-xl border d-flex flex-wrap align-center justify-space-between ga-3">
      <div class="d-flex flex-wrap align-center ga-3">
        <SegmentedPillToggle
          v-model="activeTab"
          :items="[
            { value: 'warehouses', label: $t('warehouses'), icon: 'mdi-warehouse', badge: warehouses.length },
            { value: 'categories', label: $t('categories'), icon: 'mdi-tag-multiple-outline', badge: categories.length },
            { value: 'sejour', label: $t('sejour_rates'), icon: 'mdi-calendar-clock', badge: sejourRates.length }
          ]"
          color="#a21ee5"
        />
      </div>
    </div>

    <v-card-text class="pa-4">
      <!-- ============= WAREHOUSES TAB ============= -->
      <v-window v-model="activeTab">
        <v-window-item value="warehouses">
          <div class="param-top-toolbar d-flex align-center justify-space-between mb-4 flex-wrap ga-3">
            <div class="d-flex align-center ga-2 flex-wrap">
              <v-chip size="small" variant="tonal" color="#a21ee5" class="font-weight-bold fnm-currency-num px-3" rounded="lg">
                <v-icon start size="14">mdi-warehouse</v-icon>
                {{ filteredWarehouses.length }} {{ $t('records') }}
              </v-chip>
              <v-btn v-if="hasActiveWarehouseFilters" variant="tonal" color="warning" rounded="lg" size="small" prepend-icon="mdi-filter-off" @click="warehouseColumnFilters = {}">{{ $t('clear_filters') }}</v-btn>
            </div>
            <v-btn color="#a21ee5" variant="flat" rounded="xl" height="38" prepend-icon="mdi-plus" class="param-add-btn text-white text-capitalize font-weight-bold px-4" @click="openWarehouseDialog(null)">{{ $t('add_warehouse') }}</v-btn>
          </div>
          <v-data-table :headers="warehouseHeaders" :items="filteredWarehouses" :loading="loadingWarehouses ? '#a21ee5' : false" :multi-sort="true" density="comfortable" class="rounded-xl param-table" hover>
            <template v-slot:headers="{ columns, isSorted, getSortIcon, toggleSort }">
              <tr><th v-for="col in columns" :key="col.key" class="param-th" :style="col.width ? { width: col.width } : {}"><div class="param-th-label" @click="col.sortable !== false ? toggleSort(col) : null" :style="{ cursor: col.sortable !== false ? 'pointer' : 'default' }">{{ col.title }}<v-icon v-if="isSorted(col)" size="14" class="ms-1">{{ getSortIcon(col) }}</v-icon></div></th></tr>
              <tr class="param-filter-row"><th v-for="col in columns" :key="col.key + '-f'" class="param-filter-th" :style="col.width ? { width: col.width } : {}"><v-autocomplete v-if="col.key !== 'actions'" v-model="warehouseColumnFilters[col.key]" :items="getWarehouseColumnOptions(col.key)" density="compact" variant="outlined" color="#a21ee5" class="param-filter-input" hide-details clearable multiple chips closable-chips :placeholder="$t('filter')"><template #chip="{ item, props }"><v-chip v-bind="props" size="x-small" color="#a21ee5" variant="tonal" closable>{{ item.title }}</v-chip></template></v-autocomplete></th></tr>
            </template>
            <template v-slot:[`item.has_sejour`]="{ item }"><v-chip :color="item.has_sejour ? 'warning' : 'grey'" size="small" variant="tonal">{{ item.has_sejour ? $t('yes') : $t('no') }}</v-chip></template>
            <template v-slot:[`item.allow_production_effect`]="{ item }"><v-chip :color="item.allow_production_effect ? '#a21ee5' : 'grey'" size="small" variant="tonal">{{ item.allow_production_effect ? $t('yes') : $t('no') }}</v-chip></template>
            <template v-slot:[`item.operation_service_fee`]="{ item }"><span class="fnm-currency-num">{{ item.has_sejour ? item.operation_service_fee + ' DH' : '-' }}</span></template>
            <template v-slot:[`item.category`]="{ item }"><v-chip :color="categoryColor(item.category?.name)" size="small" variant="tonal">{{ item.category?.name || 'N/A' }}</v-chip></template>
            <template v-slot:[`item.actions`]="{ item }">
              <div class="param-actions-cell">
                <v-tooltip :text="$t('edit')" location="top">
                  <template #activator="{ props }">
                    <v-btn v-bind="props" icon size="28" variant="tonal" color="#a21ee5" class="param-action-btn" @click="openWarehouseDialog(item)">
                      <v-icon size="15">mdi-pencil</v-icon>
                    </v-btn>
                  </template>
                </v-tooltip>
                <v-tooltip :text="$t('delete')" location="top">
                  <template #activator="{ props }">
                    <v-btn v-bind="props" icon size="28" variant="tonal" color="error" class="param-action-btn param-action-delete" @click="deleteWarehouse(item)">
                      <v-icon size="15">mdi-delete-outline</v-icon>
                    </v-btn>
                  </template>
                </v-tooltip>
              </div>
            </template>
          </v-data-table>
        </v-window-item>

        <!-- ============= CATEGORIES TAB ============= -->
        <v-window-item value="categories">
          <div class="param-top-toolbar d-flex align-center justify-space-between mb-4 flex-wrap ga-3">
            <div class="d-flex align-center ga-2 flex-wrap">
              <v-chip size="small" variant="tonal" color="#a21ee5" class="font-weight-bold fnm-currency-num px-3" rounded="lg">
                <v-icon start size="14">mdi-tag-multiple-outline</v-icon>
                {{ filteredCategories.length }} {{ $t('records') }}
              </v-chip>
              <v-btn v-if="hasActiveCategoryFilters" variant="tonal" color="warning" rounded="lg" size="small" prepend-icon="mdi-filter-off" @click="categoryColumnFilters = {}">{{ $t('clear_filters') }}</v-btn>
            </div>
            <v-btn color="#a21ee5" variant="flat" rounded="xl" height="38" prepend-icon="mdi-plus" class="param-add-btn text-white text-capitalize font-weight-bold px-4" @click="openCategoryDialog(null)">{{ $t('add_category') }}</v-btn>
          </div>
          <v-data-table :headers="categoryHeaders" :items="filteredCategories" :loading="loadingCategories ? '#a21ee5' : false" :multi-sort="true" density="comfortable" class="rounded-xl param-table" hover>
            <template v-slot:headers="{ columns, isSorted, getSortIcon, toggleSort }">
              <tr><th v-for="col in columns" :key="col.key" class="param-th" :style="col.width ? { width: col.width } : {}"><div class="param-th-label" @click="col.sortable !== false ? toggleSort(col) : null" :style="{ cursor: col.sortable !== false ? 'pointer' : 'default' }">{{ col.title }}<v-icon v-if="isSorted(col)" size="14" class="ms-1">{{ getSortIcon(col) }}</v-icon></div></th></tr>
              <tr class="param-filter-row"><th v-for="col in columns" :key="col.key + '-f'" class="param-filter-th" :style="col.width ? { width: col.width } : {}"><v-autocomplete v-if="col.key !== 'actions'" v-model="categoryColumnFilters[col.key]" :items="getCategoryColumnOptions(col.key)" density="compact" variant="outlined" color="#a21ee5" class="param-filter-input" hide-details clearable multiple chips closable-chips :placeholder="$t('filter')"><template #chip="{ item, props }"><v-chip v-bind="props" size="x-small" color="#a21ee5" variant="tonal" closable>{{ item.title }}</v-chip></template></v-autocomplete></th></tr>
            </template>
            <template v-slot:[`item.warehouse_count`]="{ item }"><v-chip color="#a21ee5" size="small" variant="tonal" class="font-weight-bold fnm-currency-num">{{ item.warehouse_count || 0 }}</v-chip></template>
            <template v-slot:[`item.actions`]="{ item }">
              <div class="param-actions-cell">
                <v-tooltip :text="$t('edit')" location="top">
                  <template #activator="{ props }">
                    <v-btn v-bind="props" icon size="28" variant="tonal" color="#a21ee5" class="param-action-btn" @click="openCategoryDialog(item)">
                      <v-icon size="15">mdi-pencil</v-icon>
                    </v-btn>
                  </template>
                </v-tooltip>
                <v-tooltip :text="$t('delete')" location="top">
                  <template #activator="{ props }">
                    <v-btn v-bind="props" icon size="28" variant="tonal" color="error" class="param-action-btn param-action-delete" @click="deleteCategory(item)">
                      <v-icon size="15">mdi-delete-outline</v-icon>
                    </v-btn>
                  </template>
                </v-tooltip>
              </div>
            </template>
          </v-data-table>
        </v-window-item>

        <!-- ============= SEJOUR RATES TAB ============= -->
        <v-window-item value="sejour">
          <v-row>
            <v-col cols="12" md="4">
              <!-- Warehouse selector + DatePicker range -->
              <v-autocomplete
                v-model="selectedSejourWarehouse"
                :items="sejourWarehouses"
                item-title="name"
                item-value="id"
                :label="$t('select_warehouse')"
                prepend-inner-icon="mdi-warehouse"
                chips
                density="compact"
                variant="outlined"
                rounded
                color="#a21ee5"
                class="mb-4"
                hide-details
                @update:model-value="fetchSejourRates"
              >
                <template #chip="{ item, index }">
                  <v-chip :key="index" color="#a21ee5" variant="tonal">{{ item.title }}</v-chip>
                </template>
              </v-autocomplete>

              <!-- Range Date Picker for rate config -->
              <template v-if="selectedSejourWarehouse">
                <v-switch
                  v-model="rateOpenEnded"
                  :label="$t('open_ended')"
                  color="warning"
                  density="compact"
                  hide-details
                  class="mb-2"
                ></v-switch>

                <DatePicker
                  :is-dark="main.is_dark"
                  v-model.range="rateDateRange"
                  mode="date"
                  :locale="main.lang.short"
                  expanded
                  v-if="!rateOpenEnded"
                />
                <DatePicker
                  :is-dark="main.is_dark"
                  v-model="rateDateRange.start"
                  mode="date"
                  :locale="main.lang.short"
                  expanded
                  v-if="rateOpenEnded"
                />
                <div class="text-center mt-3" role="group">
                  <v-chip color="#a21ee5" variant="tonal" class="mr-2">
                    <v-icon left small class="mr-2">mdi-calendar-start</v-icon>
                    <span>{{ main.formateDate(rateOpenEnded ? rateDateRange.start : rateDateRange.start) }}</span>
                  </v-chip>
                  <v-divider vertical></v-divider>
                  <v-chip :color="rateOpenEnded ? 'warning' : 'indigo'" variant="tonal" class="ml-2">
                    <span>{{ rateOpenEnded ? $t('ongoing') : main.formateDate(rateDateRange.end) }}</span>
                    <v-icon left small class="ml-2">{{ rateOpenEnded ? 'mdi-infinity' : 'mdi-calendar-end' }}</v-icon>
                  </v-chip>
                </div>

                <!-- Overlap error -->
                <v-alert v-if="overlapError" type="error" variant="tonal" density="compact" class="mt-3" closable @click:close="overlapError = false">
                  {{ $t('date_overlap_error') }}
                </v-alert>

                <!-- Fee inputs -->
                <v-text-field
                  v-model.number="rateForm.weight_unit"
                  :label="$t('weight_unit') + ' (KG)'"
                  prepend-inner-icon="mdi-scale"
                  variant="outlined" density="compact" rounded color="#a21ee5" class="mt-4"
                  type="number" step="1" min="1" hide-details="auto"
                  suffix="KG"
                ></v-text-field>
                <v-text-field
                  v-model.number="rateForm.daily_rate_per_kg"
                  :label="$t('sejour_unit_price') + ' (DH/' + formatWeightUnit(rateForm.weight_unit) + ' KG)'"
                  prepend-inner-icon="mdi-cash"
                  variant="outlined" density="compact" rounded color="#a21ee5" class="mt-3"
                  type="number" step="0.0001" hide-details="auto"
                ></v-text-field>
                <v-text-field
                  v-model.number="rateForm.service_unit_price"
                  :label="$t('service_unit_price') + ' (DH/' + formatWeightUnit(rateForm.weight_unit) + ' KG)'"
                  prepend-inner-icon="mdi-cash-check"
                  variant="outlined" density="compact" rounded color="#a21ee5" class="mt-3"
                  type="number" step="0.0001" hide-details="auto"
                ></v-text-field>

                <div class="d-flex ga-3 mt-4">
                  <v-btn color="#a21ee5" prepend-icon="mdi-content-save" rounded block @click="saveRate" :loading="saving">
                    {{ editingRate ? $t('update_rate') : $t('add_rate') }}
                  </v-btn>
                </div>
                <v-btn v-if="editingRate" color="grey" variant="text" size="small" block class="mt-1" @click="cancelEditRate">
                  {{ $t('cancel') }}
                </v-btn>
              </template>
            </v-col>

            <v-col cols="12" md="8">
              <!-- Empty state when no warehouse selected -->
              <div v-if="!selectedSejourWarehouse" class="sejour-empty-state">
                <div class="sejour-empty-icon">
                  <v-icon size="64" color="grey-lighten-1">mdi-warehouse</v-icon>
                </div>
                <h3 class="text-h5 font-weight-bold mt-5 mb-2" style="color: rgba(var(--v-theme-on-surface), .75)">
                  {{ $t('no_warehouse_selected') }}
                </h3>
                <p class="text-body-1 text-medium-emphasis mx-auto" style="max-width: 420px; line-height: 1.7">
                  {{ $t('no_warehouse_selected_hint') }}
                </p>
                <v-icon size="32" color="#a21ee5" class="mt-4 sejour-empty-arrow">mdi-arrow-left-bold</v-icon>
              </div>

              <!-- Rates Table -->
              <v-data-table
                v-if="selectedSejourWarehouse"
                :headers="rateHeaders"
                :items="sejourRates"
                :loading="loadingRates ? '#a21ee5' : false"
                :multi-sort="true"
                density="comfortable"
                class="rounded-xl param-table mb-6"
                hover
              >
                <template v-slot:[`item.start_date`]="{ item }">
                  {{ main.formateDate(item.start_date) }}
                </template>
                <template v-slot:[`item.end_date`]="{ item }">
                  <v-chip v-if="!item.end_date" color="warning" size="small" variant="tonal">
                    <v-icon start size="14">mdi-infinity</v-icon>{{ $t('ongoing') }}
                  </v-chip>
                  <span v-else>{{ main.formateDate(item.end_date) }}</span>
                </template>
                <template v-slot:[`item.daily_rate_per_kg`]="{ item }">
                  <span class="font-weight-bold fnm-currency-num" style="color:#a21ee5">{{ item.daily_rate_per_kg }} DH/{{ formatWeightUnit(item.weight_unit) }} KG</span>
                </template>
                <template v-slot:[`item.weight_unit`]="{ item }">
                  <v-chip color="#a21ee5" size="small" variant="tonal" class="font-weight-bold fnm-currency-num">{{ formatWeightUnit(item.weight_unit || 1000) }} KG</v-chip>
                </template>
                <template v-slot:[`item.service_unit_price`]="{ item }">
                  <span class="font-weight-bold fnm-currency-num" style="color:#a21ee5">{{ item.service_unit_price || 0 }} DH/{{ formatWeightUnit(item.weight_unit) }} KG</span>
                </template>
                <template v-slot:[`item.actions`]="{ item }">
                  <div class="param-actions-cell">
                    <v-tooltip :text="$t('edit')" location="top">
                      <template #activator="{ props }">
                        <v-btn v-bind="props" icon size="28" variant="tonal" color="#a21ee5" class="param-action-btn" @click="openRateDialog(item)">
                          <v-icon size="15">mdi-pencil</v-icon>
                        </v-btn>
                      </template>
                    </v-tooltip>
                    <v-tooltip :text="$t('delete')" location="top">
                      <template #activator="{ props }">
                        <v-btn v-bind="props" icon size="28" variant="tonal" color="error" class="param-action-btn param-action-delete" @click="deleteRate(item)">
                          <v-icon size="15">mdi-delete-outline</v-icon>
                        </v-btn>
                      </template>
                    </v-tooltip>
                  </div>
                </template>
              </v-data-table>
            </v-col>
          </v-row>
        </v-window-item>
      </v-window>
    </v-card-text>

    <!-- ============= WAREHOUSE DIALOG ============= -->
    <v-dialog v-model="warehouseDialog" max-width="580" persistent scrollable>
      <v-card class="param-dialog-card">
        <div class="param-dialog-header">
          <div class="d-flex align-center ga-3">
            <div class="param-dialog-icon-badge" style="background: rgba(162, 30, 229, 0.12); color: #a21ee5;">
              <v-icon size="22">mdi-warehouse</v-icon>
            </div>
            <div>
              <div class="param-dialog-title">{{ editingWarehouse ? $t('edit_warehouse') : $t('add_warehouse') }}</div>
              <div class="param-dialog-subtitle">{{ $t('parameters') }} • {{ $t('warehouse_management') }}</div>
            </div>
          </div>
          <v-btn icon variant="text" size="small" density="comfortable" @click="warehouseDialog = false">
            <v-icon size="18">mdi-close</v-icon>
          </v-btn>
        </div>
        <v-card-text class="param-dialog-body">
          <v-row dense>
            <v-col cols="12">
              <v-text-field v-model="warehouseForm.name" :label="$t('name')" prepend-inner-icon="mdi-warehouse" variant="outlined" density="compact" rounded="lg" color="#a21ee5" hide-details="auto" :error-messages="warehouseNameError"></v-text-field>
            </v-col>
            <v-col cols="12" sm="6">
              <v-select v-model="warehouseForm.warehouse_category_id" :items="categories" item-title="name" item-value="id" :label="$t('category')" prepend-inner-icon="mdi-tag-outline" variant="outlined" density="compact" rounded="lg" color="#a21ee5" hide-details="auto" clearable></v-select>
            </v-col>
            <v-col cols="12" sm="6">
              <v-select v-model="warehouseForm.warehouse_type" :items="['FishType','GuanoType','PackageType']" :label="$t('warehouse_type')" prepend-inner-icon="mdi-shape" variant="outlined" density="compact" rounded="lg" color="#a21ee5" hide-details="auto" clearable></v-select>
            </v-col>
            <v-col cols="12">
              <v-textarea v-model="warehouseForm.description" :label="$t('description')" prepend-inner-icon="mdi-text" variant="outlined" density="compact" rounded="lg" color="#a21ee5" hide-details="auto" rows="2"></v-textarea>
            </v-col>
            <v-col cols="12" sm="6">
              <v-switch v-model="warehouseForm.has_sejour" :label="$t('has_sejour')" color="warning" density="compact" hide-details></v-switch>
            </v-col>
            <v-col v-if="warehouseForm.has_sejour" cols="12" sm="6">
              <v-text-field v-model.number="warehouseForm.operation_service_fee" :label="$t('operation_service_fee') + ' (DH)'" prepend-inner-icon="mdi-cash" variant="outlined" density="compact" rounded="lg" color="#a21ee5" hide-details="auto" type="number"></v-text-field>
            </v-col>
            <v-col cols="12">
              <v-divider class="my-2"></v-divider>
              <div class="text-caption font-weight-bold mb-2" style="color:#a21ee5">{{ $t('enabled_fields') }}</div>
              <v-switch v-model="warehouseForm.allow_production_effect" :label="$t('allow_production_effect')" color="#a21ee5" density="compact" hide-details></v-switch>
            </v-col>
          </v-row>
        </v-card-text>
        <v-card-actions class="param-dialog-actions">
          <v-spacer></v-spacer>
          <v-btn @click="warehouseDialog = false" variant="tonal" rounded="lg" class="text-capitalize">{{ $t('cancel') }}</v-btn>
          <v-btn color="#a21ee5" variant="flat" rounded="lg" class="text-capitalize font-weight-bold" @click="saveWarehouse" :loading="saving">{{ $t('save') }}</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- ============= CATEGORY DIALOG ============= -->
    <v-dialog v-model="categoryDialog" max-width="480" persistent scrollable>
      <v-card class="param-dialog-card">
        <div class="param-dialog-header">
          <div class="d-flex align-center ga-3">
            <div class="param-dialog-icon-badge" style="background: rgba(162, 30, 229, 0.12); color: #a21ee5;">
              <v-icon size="22">mdi-tag-multiple-outline</v-icon>
            </div>
            <div>
              <div class="param-dialog-title">{{ editingCategory ? $t('edit_category') : $t('add_category') }}</div>
              <div class="param-dialog-subtitle">{{ $t('parameters') }} • {{ $t('warehouse_categories') }}</div>
            </div>
          </div>
          <v-btn icon variant="text" size="small" density="comfortable" @click="categoryDialog = false">
            <v-icon size="18">mdi-close</v-icon>
          </v-btn>
        </div>
        <v-card-text class="param-dialog-body">
          <v-row dense>
            <v-col cols="12">
              <v-text-field v-model="categoryForm.name" :label="$t('name')" prepend-inner-icon="mdi-tag-outline" variant="outlined" density="compact" rounded="lg" color="#a21ee5" hide-details="auto" :error-messages="categoryNameError"></v-text-field>
            </v-col>
            <v-col cols="12">
              <v-textarea v-model="categoryForm.description" :label="$t('description')" prepend-inner-icon="mdi-text" variant="outlined" density="compact" rounded="lg" color="#a21ee5" hide-details="auto" rows="3"></v-textarea>
            </v-col>
          </v-row>
        </v-card-text>
        <v-card-actions class="param-dialog-actions">
          <v-spacer></v-spacer>
          <v-btn @click="categoryDialog = false" variant="tonal" rounded="lg" class="text-capitalize">{{ $t('cancel') }}</v-btn>
          <v-btn color="#a21ee5" variant="flat" rounded="lg" class="text-capitalize font-weight-bold" @click="saveCategory" :loading="saving">{{ $t('save') }}</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- ============= CONFIRM DELETE DIALOG ============= -->
    <v-dialog v-model="confirmDeleteDialog" max-width="440">
      <v-card class="param-dialog-card">
        <div class="pa-6 text-center">
          <div class="param-dialog-icon-badge mx-auto mb-4" style="background: rgba(239, 68, 68, 0.12); color: #ef4444; width: 56px; height: 56px;">
            <v-icon size="28">mdi-trash-can-outline</v-icon>
          </div>
          <div class="text-h6 font-weight-bold mb-2">{{ $t('confirm_delete') }}</div>
          <div class="text-body-2 text-medium-emphasis mb-6">
            {{ $t('confirm_delete_message') }}
          </div>
          <div class="d-flex align-center ga-3 justify-center">
            <v-btn variant="tonal" rounded="lg" class="text-capitalize px-5" @click="confirmDeleteDialog = false">{{ $t('cancel') }}</v-btn>
            <v-btn color="error" variant="flat" rounded="lg" class="text-capitalize font-weight-bold px-6" elevation="2" @click="executeDelete">{{ $t('delete') }}</v-btn>
          </div>
        </div>
      </v-card>
    </v-dialog>
    <!-- Summary Snackbar (Rule 5) -->
    <v-snackbar v-model="snackbar.show" :timeout="5000" location="top right" color="surface" rounded="lg" elevation="12" min-width="320" max-width="400">
      <v-progress-linear :model-value="snackbarProgress" color="#a21ee5" height="3" class="mb-2" rounded></v-progress-linear>
      <div class="d-flex align-center mb-1">
        <v-icon :color="snackbar.success ? 'success' : 'error'" class="mr-2">{{ snackbar.success ? 'mdi-check-circle' : 'mdi-alert-circle' }}</v-icon>
        <span class="text-subtitle-2 font-weight-bold">{{ snackbar.message }}</span>
      </div>
      <template #actions>
        <v-btn variant="text" size="small" icon="mdi-close" @click="snackbar.show = false"></v-btn>
      </template>
    </v-snackbar>
  </v-card>
</template>

<script>
import axios from 'axios';
import mitt from '@/plugins/mitt';
import { useMain } from '@/stores/Main';
import { useAuth } from '@/stores/Auth';
import SegmentedPillToggle from '@/components/common/SegmentedPillToggle.vue';

export default {
  name: 'WarehouseManagement',
  components: { SegmentedPillToggle },
  data() {
    return {
      activeTab: 'warehouses',
      warehouses: [],
      loadingWarehouses: false,
      warehouseDialog: false,
      editingWarehouse: null,
      warehouseForm: { name: '', description: '', warehouse_type: null, warehouse_category_id: null, has_sejour: false, operation_service_fee: 0, allow_production_effect: false },
      warehouseColumnFilters: {},
      categories: [],
      loadingCategories: false,
      categoryDialog: false,
      editingCategory: null,
      categoryForm: { name: '', description: '' },
      categoryColumnFilters: {},
      selectedSejourWarehouse: null,
      sejourRates: [],
      loadingRates: false,
      editingRate: null,
      rateForm: { daily_rate_per_kg: 0, service_unit_price: 0, weight_unit: 1000 },
      rateDateRange: { start: new Date(), end: new Date() },
      rateOpenEnded: false,
      overlapError: false,
      confirmDeleteDialog: false,
      deleteTarget: null,
      deleteEntityType: null,
      search: '',
      categorySearch: '',
      saving: false,
      snackbar: { show: false, message: '', success: true },
      snackbarProgress: 100,
      snackbarTimer: null,
    };
  },
  computed: {
    main() { return useMain(); },
    auth() { return useAuth(); },
    warehouseHeaders() {
      return [
        { title: this.$t('name'), key: 'name', align: 'center' },
        { title: this.$t('description'), key: 'description', align: 'center' },
        { title: this.$t('category'), key: 'category', align: 'center' },
        { title: this.$t('warehouse_type'), key: 'warehouse_type', align: 'center' },
        { title: this.$t('has_sejour'), key: 'has_sejour', align: 'center' },
        { title: this.$t('allow_production_effect'), key: 'allow_production_effect', align: 'center' },
        { title: this.$t('operation_service_fee'), key: 'operation_service_fee', align: 'center' },
        { title: this.$t('actions'), key: 'actions', align: 'center', sortable: false, width: '120px' },
      ];
    },
    categoryHeaders() {
      return [
        { title: this.$t('name'), key: 'name', align: 'center' },
        { title: this.$t('description'), key: 'description', align: 'center' },
        { title: this.$t('warehouses'), key: 'warehouse_count', align: 'center' },
        { title: this.$t('actions'), key: 'actions', align: 'center', sortable: false, width: '120px' },
      ];
    },
    rateHeaders() {
      return [
        { title: this.$t('start_date'), key: 'start_date', align: 'center' },
        { title: this.$t('end_date'), key: 'end_date', align: 'center' },
        { title: this.$t('weight_unit'), key: 'weight_unit', align: 'center' },
        { title: this.$t('sejour_unit_price'), key: 'daily_rate_per_kg', align: 'center' },
        { title: this.$t('service_unit_price'), key: 'service_unit_price', align: 'center' },
        { title: this.$t('actions'), key: 'actions', align: 'center', sortable: false, width: '120px' },
      ];
    },
    sejourWarehouses() {
      return this.warehouses.filter(w => w.has_sejour);
    },
    filteredWarehouses() {
      return this.warehouses.filter(item => Object.entries(this.warehouseColumnFilters).every(([key, sel]) => {
        if (!sel || !sel.length) return true;
        const val = key === 'category' ? (item.category?.name ?? '') : key === 'has_sejour' ? (item[key] ? this.$t('yes') : this.$t('no')) : String(item[key] ?? '');
        return sel.includes(val);
      }));
    },
    filteredCategories() {
      return this.categories.filter(item => Object.entries(this.categoryColumnFilters).every(([key, sel]) => {
        if (!sel || !sel.length) return true;
        return sel.includes(String(item[key] ?? ''));
      }));
    },
    hasActiveWarehouseFilters() { return Object.values(this.warehouseColumnFilters).some(f => f && f.length > 0); },
    hasActiveCategoryFilters() { return Object.values(this.categoryColumnFilters).some(f => f && f.length > 0); },
    warehouseNameError() {
      const name = (this.warehouseForm.name || '').trim().toLowerCase();
      if (!name) return '';
      const dup = this.warehouses.find(w => w.name.toLowerCase() === name && (!this.editingWarehouse || w.id !== this.editingWarehouse.id));
      return dup ? this.$t('name_already_exists') : '';
    },
    categoryNameError() {
      const name = (this.categoryForm.name || '').trim().toLowerCase();
      if (!name) return '';
      const dup = this.categories.find(c => c.name.toLowerCase() === name && (!this.editingCategory || c.id !== this.editingCategory.id));
      return dup ? this.$t('name_already_exists') : '';
    },
  },
  mounted() {
    this.fetchWarehouses();
    this.fetchCategories();
    mitt.on('model-changed', this._onModelChanged);
  },
  beforeUnmount() { mitt.off('model-changed', this._onModelChanged); },
  methods: {
    formatNum(v) { return this.main.formatDisplayNumber(v); },
    _onModelChanged(data) {
      if (['Warehouse', 'WarehouseCategory', 'SejourRate'].includes(data.model)) {
        this.fetchWarehouses();
        if (data.model === 'WarehouseCategory') this.fetchCategories();
      }
    },
    getWarehouseColumnOptions(key) {
      const set = new Set();
      this.warehouses.forEach(item => {
        const val = key === 'category' ? (item.category?.name ?? '') : key === 'has_sejour' ? (item[key] ? this.$t('yes') : this.$t('no')) : String(item[key] ?? '');
        set.add(val);
      });
      return [...set].sort();
    },
    getCategoryColumnOptions(key) {
      const set = new Set();
      this.categories.forEach(item => set.add(String(item[key] ?? '')));
      return [...set].sort();
    },
    formatWeightUnit(v) {
      const n = Number(v) || 1000;
      return new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 0 }).format(n);
    },
    categoryColor(cat) {
      if (!cat) return 'grey';
      const c = cat.toLowerCase();
      if (c === 'local') return 'success';
      if (c === 'foreign') return 'deep-purple';
      if (c === 'client') return 'orange';
      return 'info';
    },

    // -- Warehouses --
    async fetchWarehouses() {
      this.loadingWarehouses = true;
      try {
        const res = await axios.get('/api/warehouses');
        this.warehouses = res.data.warehouses || [];
      } catch (e) { console.error(e); }
      this.loadingWarehouses = false;
    },
    openWarehouseDialog(item) {
      this.editingWarehouse = item;
      this.warehouseForm = item
        ? { name: item.name, description: item.description, warehouse_type: item.warehouse_type, warehouse_category_id: item.warehouse_category_id, has_sejour: item.has_sejour || false, operation_service_fee: item.operation_service_fee || 0, allow_production_effect: item.allow_production_effect || false }
        : { name: '', description: '', warehouse_type: null, warehouse_category_id: null, has_sejour: false, operation_service_fee: 0, allow_production_effect: false };
      this.warehouseDialog = true;
    },
    async saveWarehouse() {
      if (this.warehouseNameError) return;
      this.saving = true;
      try {
        if (this.editingWarehouse) {
          await axios.put(`/api/warehouses/${this.editingWarehouse.id}`, this.warehouseForm);
          this.showSnackbar(true, this.$t('warehouse_updated') || 'Warehouse updated successfully');
        } else {
          const res = await axios.post('/api/warehouses', this.warehouseForm);
          if (res.data.warehouse) this.auth.warehouse.list.push(res.data.warehouse);
          this.showSnackbar(true, this.$t('warehouse_created') || 'Warehouse created successfully');
        }
        this.warehouseDialog = false;
        this.fetchWarehouses();
      } catch (e) {
        console.error(e);
        this.showSnackbar(false, this.$t('error_occurred') || 'An error occurred');
      }
      this.saving = false;
    },
    async deleteWarehouse(item) {
      this.deleteTarget = item;
      this.deleteEntityType = 'warehouse';
      this.confirmDeleteDialog = true;
    },

    // -- Categories --
    async fetchCategories() {
      this.loadingCategories = true;
      try {
        const res = await axios.get('/api/warehouse-categories');
        this.categories = res.data.warehouse_categories || res.data.categories || [];
      } catch (e) { console.error(e); }
      this.loadingCategories = false;
    },
    openCategoryDialog(item) {
      this.editingCategory = item;
      this.categoryForm = item
        ? { name: item.name, description: item.description }
        : { name: '', description: '' };
      this.categoryDialog = true;
    },
    async saveCategory() {
      if (this.categoryNameError) return;
      this.saving = true;
      try {
        if (this.editingCategory) {
          await axios.put(`/api/warehouse-categories/${this.editingCategory.id}`, this.categoryForm);
          this.showSnackbar(true, this.$t('category_updated') || 'Category updated successfully');
        } else {
          await axios.post('/api/warehouse-categories', this.categoryForm);
          this.showSnackbar(true, this.$t('category_created') || 'Category created successfully');
        }
        this.categoryDialog = false;
        this.fetchCategories();
        this.fetchWarehouses();
      } catch (e) {
        console.error(e);
        this.showSnackbar(false, this.$t('error_occurred') || 'An error occurred');
      }
      this.saving = false;
    },
    async deleteCategory(item) {
      this.deleteTarget = item;
      this.deleteEntityType = 'category';
      this.confirmDeleteDialog = true;
    },

    // -- Sejour Rates --
    async fetchSejourRates() {
      if (!this.selectedSejourWarehouse) return;
      this.loadingRates = true;
      this.sejourResult = null;
      try {
        const res = await axios.get(`/api/warehouses/${this.selectedSejourWarehouse}/sejour-rates`);
        this.sejourRates = res.data.rates || [];
      } catch (e) { console.error(e); }
      this.loadingRates = false;
    },
    openRateDialog(item) {
      this.editingRate = item;
      this.overlapError = false;
      if (item) {
        this.rateOpenEnded = !item.end_date;
        this.rateDateRange = {
          start: new Date(item.start_date),
          end: item.end_date ? new Date(item.end_date) : new Date(),
        };
        this.rateForm = { daily_rate_per_kg: item.daily_rate_per_kg, service_unit_price: item.service_unit_price || 0, weight_unit: item.weight_unit || 1000 };
      } else {
        this.rateOpenEnded = false;
        this.rateForm = { daily_rate_per_kg: 0, service_unit_price: 0, weight_unit: 1000 };
        this.rateDateRange = { start: new Date(), end: new Date() };
      }
    },
    cancelEditRate() {
      this.editingRate = null;
      this.rateOpenEnded = false;
      this.overlapError = false;
      this.rateForm = { daily_rate_per_kg: 0, service_unit_price: 0, weight_unit: 1000 };
      this.rateDateRange = { start: new Date(), end: new Date() };
    },
    formatDateForApi(d) {
      if (!d) return null;
      const dt = d instanceof Date ? d : new Date(d);
      return dt.toISOString().slice(0, 10);
    },
    async saveRate() {
      this.saving = true;
      this.overlapError = false;
      try {
        const payload = {
          start_date: this.formatDateForApi(this.rateDateRange.start),
          end_date: this.rateOpenEnded ? null : this.formatDateForApi(this.rateDateRange.end),
          daily_rate_per_kg: this.rateForm.daily_rate_per_kg,
          service_unit_price: this.rateForm.service_unit_price,
          weight_unit: this.rateForm.weight_unit,
        };
        if (this.editingRate) {
          await axios.put(`/api/warehouses/sejour-rates/${this.editingRate.id}`, payload);
          this.showSnackbar(true, this.$t('rate_updated') || 'Rate updated successfully');
        } else {
          await axios.post(`/api/warehouses/${this.selectedSejourWarehouse}/sejour-rates`, payload);
          this.showSnackbar(true, this.$t('rate_created') || 'Rate created successfully');
        }
        this.cancelEditRate();
        this.fetchSejourRates();
      } catch (e) {
        if (e.response && e.response.status === 422 && e.response.data?.error === 'date_overlap') {
          this.overlapError = true;
        }
        console.error(e);
        this.showSnackbar(false, this.$t('error_occurred') || 'An error occurred');
      }
      this.saving = false;
    },
    async deleteRate(item) {
      this.deleteTarget = item;
      this.deleteEntityType = 'rate';
      this.confirmDeleteDialog = true;
    },
    async executeDelete() {
      this.confirmDeleteDialog = false;
      const item = this.deleteTarget;
      if (!item) return;
      try {
        if (this.deleteEntityType === 'warehouse') {
          await axios.delete(`/api/warehouses/${item.id}`);
          this.fetchWarehouses();
          this.showSnackbar(true, this.$t('warehouse_deleted') || 'Warehouse deleted successfully');
        } else if (this.deleteEntityType === 'category') {
          await axios.delete(`/api/warehouse-categories/${item.id}`);
          this.fetchCategories();
          this.showSnackbar(true, this.$t('category_deleted') || 'Category deleted successfully');
        } else if (this.deleteEntityType === 'rate') {
          await axios.delete(`/api/warehouses/sejour-rates/${item.id}`);
          this.fetchSejourRates();
          this.showSnackbar(true, this.$t('rate_deleted') || 'Rate deleted successfully');
        }
      } catch (e) {
        console.error(e);
        this.showSnackbar(false, this.$t('error_occurred') || 'An error occurred');
      }
      this.deleteTarget = null;
      this.deleteEntityType = null;
    },
    showSnackbar(success, message) {
      clearInterval(this.snackbarTimer);
      this.snackbar = { show: true, success, message };
      this.snackbarProgress = 100;
      this.snackbarTimer = setInterval(() => {
        this.snackbarProgress -= 2;
        if (this.snackbarProgress <= 0) {
          clearInterval(this.snackbarTimer);
        }
      }, 100);
    },
  },
};
</script>

<style scoped>
.wm-card { border-radius: 20px !important; overflow: visible; position: relative; }
.wm-hero { background: linear-gradient(135deg, #c015b4 0%, #a21ee5 40%, #9742f5 100%); border-radius: inherit; border-bottom-left-radius: 0; border-bottom-right-radius: 0; height: 100px; }
.wm-hero-inner { position: relative; z-index: 1; padding: 22px 26px; color: #fff; }
.wm-hero-icon { width: 52px; height: 52px; border-radius: 14px; background: rgba(255,255,255,.18); backdrop-filter: blur(6px); display: flex; align-items: center; justify-content: center; border: 1px solid rgba(255,255,255,.15); }
.wm-hero-title { font-size: 19px; font-weight: 800; letter-spacing: .3px; }
.sejour-empty-state { display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 60px 24px; min-height: 400px; }
.sejour-empty-icon { width: 120px; height: 120px; border-radius: 50%; background: rgba(var(--v-theme-on-surface), .04); border: 2px dashed rgba(var(--v-theme-on-surface), .12); display: flex; align-items: center; justify-content: center; animation: sejour-float 3s ease-in-out infinite; }
@keyframes sejour-float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-8px); } }
.sejour-empty-arrow { animation: sejour-pulse 2s ease-in-out infinite; }
@keyframes sejour-pulse { 0%, 100% { opacity: .3; transform: translateX(0); } 50% { opacity: 1; transform: translateX(-8px); } }
/* Column filter styles */
.param-th { padding: 10px 8px 4px !important; }
.param-th-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .4px; opacity: .7; text-align: center; white-space: nowrap; }
.param-filter-row th { padding: 0 4px 6px !important; }
.param-filter-th { vertical-align: top; }
.param-filter-input { min-width: 80px; }
.param-filter-input :deep(.v-field) { font-size: 11px !important; min-height: 30px !important; border-radius: 8px !important; }
.param-filter-input :deep(.v-field__input) { padding: 2px 6px !important; min-height: 26px !important; font-size: 11px; }
.param-filter-input :deep(.v-field__clearable) { font-size: 14px; }
.param-filter-input :deep(.v-chip) { height: 18px !important; font-size: 10px !important; }
.fnm-currency-num {
  font-family: 'JetBrains Mono', 'Roboto Mono', monospace !important;
  font-variant-numeric: tabular-nums !important;
  font-feature-settings: "tnum" 1 !important;
}
</style>
