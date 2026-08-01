<template>
  <v-card class="ccm-card" elevation="0">
    <!-- Hero Header -->
    <div class="ccm-hero">
      <div class="ccm-hero-inner">
        <div class="d-flex align-center ga-4">
          <div class="ccm-hero-icon">
            <v-icon size="28" color="white">mdi-calculator-variant-outline</v-icon>
          </div>
          <div>
            <div class="ccm-hero-title">{{ $t('cost_configuration_management') }}</div>
            <div class="text-white text-caption" style="opacity:.7">{{ $t('manage_cost_configurations_desc') }}</div>
          </div>
        </div>
      </div>
    </div>

    <v-card-text class="pa-4">
      <div class="d-flex align-center ga-3 mb-3">
        <v-text-field
          v-model="search"
          :label="$t('search')"
          variant="outlined"
          density="compact"
          rounded
          color="#3F51B5"
          prepend-inner-icon="mdi-magnify"
          hide-details
          clearable
          class="flex-grow-1"
          style="max-width:360px"
        ></v-text-field>
        <v-spacer></v-spacer>
        <v-btn
          v-if="hasActiveFilters"
          variant="tonal"
          color="warning"
          rounded
          size="small"
          prepend-icon="mdi-filter-off"
          @click="columnFilters = {}"
        >
          {{ $t('clear_filters') }}
        </v-btn>
        <v-btn
          color="#3F51B5"
          prepend-icon="mdi-plus"
          rounded
          @click="openDialog(null)"
        >
          {{ $t('add_cost_configuration') }}
        </v-btn>
      </div>

      <!-- Data Table -->
      <v-data-table
        :headers="headers"
        :items="filteredItems"
        :loading="loading ? '#3F51B5' : false"
        density="comfortable"
        class="rounded-lg param-table"
        hover
        :search="search"
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
                v-if="col.key !== 'actions' && col.key !== 'variables'"
                v-model="columnFilters[col.key]"
                :items="getColumnOptions(col.key)"
                density="compact"
                variant="outlined"
                color="#3F51B5"
                class="param-filter-input"
                hide-details
                clearable
                multiple
                chips
                closable-chips
                :placeholder="$t('filter')"
              >
                <template #chip="{ item, props }">
                  <v-chip v-bind="props" size="x-small" color="#3F51B5" variant="tonal" closable>{{ item.title }}</v-chip>
                </template>
              </v-autocomplete>
            </th>
          </tr>
        </template>

        <!-- Name Column -->
        <template v-slot:[`item.name_fr`]="{ item }">
          <div class="font-weight-bold text-body-2">
            {{ item['name_' + currentLang] || item.name_fr || item.name_en }}
          </div>
          <div v-if="item.description" class="text-caption text-grey text-truncate" style="max-width:260px">
            {{ item.description }}
          </div>
        </template>

        <!-- Unit Column -->
        <template v-slot:[`item.unit`]="{ item }">
          <v-chip color="#3F51B5" variant="tonal" size="small" class="font-weight-bold">
            {{ item.unit }}
          </v-chip>
        </template>

        <!-- Variables Breakdown Column -->
        <template v-slot:[`item.variables`]="{ item }">
          <div class="d-flex flex-wrap ga-1 my-1">
            <v-chip
              v-for="v in (item.variables || [])"
              :key="v.var_name"
              size="x-small"
              variant="outlined"
              color="#3F51B5"
              class="px-2"
            >
              <strong class="mr-1 text-indigo-darken-3">{{ v.var_name }}:</strong>
              <span>{{ getDataSourceLabel(v.data_source) }}</span>
              <span v-if="v.category_name" class="mx-1 text-grey-darken-1">({{ v.category_name }})</span>
              <v-badge
                :content="v.value_type === 'price' ? $t('price') : $t('weight')"
                :color="v.value_type === 'price' ? 'success' : 'info'"
                inline
                class="ms-1"
              ></v-badge>
            </v-chip>
          </div>
        </template>

        <!-- Formula Column -->
        <template v-slot:[`item.formula`]="{ item }">
          <code class="pa-1 px-2 rounded bg-indigo-lighten-5 text-indigo-darken-4 font-weight-bold">
            {{ item.formula }}
          </code>
        </template>

        <!-- Status Column -->
        <template v-slot:[`item.enabled`]="{ item }">
          <v-chip
            size="x-small"
            :color="item.enabled ? 'success' : 'grey'"
            variant="tonal"
          >
            {{ item.enabled ? $t('active') : $t('inactive') }}
          </v-chip>
        </template>

        <!-- Actions Column -->
        <template v-slot:[`item.actions`]="{ item }">
          <v-btn
            icon
            size="x-small"
            variant="tonal"
            color="#3F51B5"
            @click="openEvaluateDialog(item)"
            class="mr-1"
            :title="$t('test_calculator')"
          >
            <v-icon size="16">mdi-calculator</v-icon>
          </v-btn>
          <v-btn
            icon
            size="x-small"
            variant="tonal"
            color="#3F51B5"
            @click="openDialog(item)"
            class="mr-1"
            :title="$t('edit')"
          >
            <v-icon size="16">mdi-pencil</v-icon>
          </v-btn>
          <v-btn
            icon
            size="x-small"
            variant="tonal"
            color="error"
            @click="confirmDelete(item)"
            :title="$t('delete')"
          >
            <v-icon size="16">mdi-delete</v-icon>
          </v-btn>
        </template>
      </v-data-table>
    </v-card-text>

    <!-- Create / Edit Dialog -->
    <v-dialog v-model="dialog" max-width="900" persistent scrollable>
      <v-card class="rounded-xl">
        <v-card-title class="d-flex align-center pa-5 pb-3 bg-indigo-lighten-5">
          <v-icon color="#3F51B5" class="mr-2">mdi-calculator-variant-outline</v-icon>
          <span class="text-h6 font-weight-bold text-indigo-darken-4">
            {{ editing ? $t('edit_cost_configuration') : $t('add_cost_configuration') }}
          </span>
          <v-spacer></v-spacer>
          <v-btn icon variant="text" size="small" @click="dialog = false">
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </v-card-title>

        <v-card-text class="pa-5">
          <v-form ref="formRef" v-model="validForm">
            <!-- Basic Information -->
            <div class="text-subtitle-2 font-weight-bold text-indigo mb-3 d-flex align-center">
              <v-icon size="18" color="#3F51B5" class="mr-1">mdi-information-outline</v-icon>
              {{ $t('basic_information') }}
            </div>

            <v-row density="compact">
              <v-col cols="12" md="4">
                <v-text-field
                  v-model="form.name_fr"
                  :label="$t('name_fr')"
                  variant="outlined"
                  density="compact"
                  rounded
                  color="#3F51B5"
                  :rules="[v => !!v || $t('field_required')]"
                ></v-text-field>
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field
                  v-model="form.name_en"
                  :label="$t('name_en')"
                  variant="outlined"
                  density="compact"
                  rounded
                  color="#3F51B5"
                ></v-text-field>
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field
                  v-model="form.name_es"
                  :label="$t('name_es')"
                  variant="outlined"
                  density="compact"
                  rounded
                  color="#3F51B5"
                ></v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <v-combobox
                  v-model="form.unit"
                  :items="unitOptions"
                  :label="$t('measure_unit')"
                  variant="outlined"
                  density="compact"
                  rounded
                  color="#3F51B5"
                  :rules="[v => !!v || $t('field_required')]"
                  hint="e.g. KG, DH, DH/KG, %"
                  persistent-hint
                ></v-combobox>
              </v-col>
              <v-col cols="12" md="6" class="d-flex align-center">
                <v-switch
                  v-model="form.enabled"
                  :label="$t('enabled')"
                  color="#3F51B5"
                  density="compact"
                  hide-details
                  class="ms-2"
                ></v-switch>
              </v-col>
              <v-col cols="12">
                <v-textarea
                  v-model="form.description"
                  :label="$t('description')"
                  variant="outlined"
                  density="compact"
                  rounded
                  color="#3F51B5"
                  rows="2"
                  hide-details
                ></v-textarea>
              </v-col>
            </v-row>

            <v-divider class="my-4"></v-divider>

            <!-- Dynamic Variables Builder Section -->
            <div class="d-flex align-center justify-space-between mb-3">
              <div class="text-subtitle-2 font-weight-bold text-indigo d-flex align-center">
                <v-icon size="18" color="#3F51B5" class="mr-1">mdi-variable</v-icon>
                {{ $t('formula_variables') }}
              </div>
              <v-btn
                color="#3F51B5"
                variant="tonal"
                size="small"
                rounded
                prepend-icon="mdi-plus-circle"
                @click="addVariableRow"
              >
                {{ $t('add_variable') }}
              </v-btn>
            </div>

            <!-- List of Variables -->
            <div v-if="form.variables.length === 0" class="text-center pa-4 bg-grey-lighten-4 rounded-lg text-grey mb-4">
              <v-icon size="32" color="grey">mdi-variable-off</v-icon>
              <div class="text-caption mt-1">{{ $t('no_variables_added') }}</div>
            </div>

            <v-card
              v-for="(v, index) in form.variables"
              :key="index"
              variant="outlined"
              class="mb-3 rounded-lg pa-3"
              style="border-color: rgba(63, 81, 181, 0.3); background: rgba(63, 81, 181, 0.01)"
            >
              <v-row density="compact" class="align-center">
                <!-- Variable Name Badge -->
                <v-col cols="12" sm="2" md="1">
                  <v-chip color="#3F51B5" variant="flat" size="small" class="font-weight-bold">
                    {{ v.var_name }}
                  </v-chip>
                </v-col>

                <!-- Data Source Selection -->
                <v-col cols="12" sm="5" md="3">
                  <v-select
                    v-model="v.data_source"
                    :items="dataSourceOptions"
                    item-title="title"
                    item-value="value"
                    :label="$t('data_source')"
                    variant="outlined"
                    density="compact"
                    rounded
                    color="#3F51B5"
                    hide-details
                    @update:modelValue="onDataSourceChange(v)"
                  ></v-select>
                </v-col>

                <!-- Category Selection (Shown ONLY for charge, production, shipping) -->
                <v-col cols="12" sm="5" md="4">
                  <v-autocomplete
                    v-if="hasCategoryOptions(v.data_source)"
                    v-model="v.category_id"
                    :items="getCategoryOptionsForSource(v.data_source)"
                    item-title="title"
                    item-value="value"
                    :label="$t('category')"
                    variant="outlined"
                    density="compact"
                    rounded
                    color="#3F51B5"
                    hide-details
                    clearable
                    @update:modelValue="onCategorySelect(v)"
                  >
                    <template #chip="{ item }">
                      <v-chip size="x-small" color="#3F51B5" variant="tonal">{{ item.title }}</v-chip>
                    </template>
                  </v-autocomplete>
                  <div v-else class="text-caption text-grey text-center py-2 bg-grey-lighten-4 rounded-pill">
                    {{ $t('no_category_required') }}
                  </div>
                </v-col>

                <!-- Value Type Selection (Price vs Weight) -->
                <v-col cols="12" sm="4" md="3">
                  <v-radio-group
                    v-model="v.value_type"
                    inline
                    density="compact"
                    hide-details
                    color="#3F51B5"
                    :disabled="v.data_source === 'fortnight_activities'"
                  >
                    <v-radio :label="$t('price')" value="price" color="success"></v-radio>
                    <v-radio
                      :label="$t('weight')"
                      value="weight"
                      color="info"
                      :disabled="v.data_source === 'fortnight_activities'"
                    ></v-radio>
                  </v-radio-group>
                </v-col>

                <!-- Remove Row Button -->
                <v-col cols="12" sm="2" md="1" class="text-right">
                  <v-btn
                    icon="mdi-close"
                    size="x-small"
                    variant="text"
                    color="error"
                    @click="removeVariableRow(index)"
                  ></v-btn>
                </v-col>
              </v-row>
            </v-card>

            <v-divider class="my-4"></v-divider>

            <!-- Formula Editor & Quick Tokens -->
            <div class="text-subtitle-2 font-weight-bold text-indigo mb-2 d-flex align-center">
              <v-icon size="18" color="#3F51B5" class="mr-1">mdi-function-variant</v-icon>
              {{ $t('cost_formula') }}
            </div>

            <!-- Formula Quick-Insert Chips Bar -->
            <div class="d-flex flex-wrap align-center ga-1 mb-2">
              <span class="text-caption font-weight-bold text-grey-darken-1 mr-1">{{ $t('insert') }}:</span>
              <!-- Variable tokens -->
              <v-chip
                v-for="v in form.variables"
                :key="'chip-' + v.var_name"
                size="small"
                color="#3F51B5"
                variant="flat"
                class="font-weight-bold cursor-pointer"
                @click="appendFormulaToken(v.var_name)"
              >
                {{ v.var_name }}
              </v-chip>
              <v-divider vertical class="mx-1"></v-divider>
              <!-- Math operators -->
              <v-chip
                v-for="op in ['+', '-', '*', '/', '(', ')', '%', '100', '.']"
                :key="'op-' + op"
                size="small"
                color="indigo"
                variant="tonal"
                class="font-weight-bold cursor-pointer"
                @click="appendFormulaToken(op)"
              >
                {{ op }}
              </v-chip>
              <v-spacer></v-spacer>
              <v-btn size="x-small" variant="text" color="error" @click="form.formula = ''">{{ $t('clear') }}</v-btn>
            </div>

            <v-text-field
              v-model="form.formula"
              :label="$t('formula_expression')"
              placeholder="ex: ((c2 + c3) / c1) * 100"
              variant="outlined"
              density="compact"
              rounded
              color="#3F51B5"
              class="font-monospace font-weight-bold text-body-1"
              :rules="[
                v => !!v || $t('field_required'),
                v => !previewError || previewError
              ]"
              :error="isFormulaInvalid && !!form.formula"
              :error-messages="form.formula && isFormulaInvalid ? [previewError || $t('syntax_error')] : []"
              @input="onFormulaInputChange"
            ></v-text-field>

            <!-- Live Test Preview Box inside Form -->
            <v-card variant="outlined" class="rounded-lg pa-4 mt-3 bg-indigo-lighten-5 border-indigo-lighten-3">
              <div class="d-flex align-center justify-space-between mb-2">
                <div class="text-subtitle-2 font-weight-bold text-indigo-darken-4 d-flex align-center">
                  <v-icon size="18" color="#3F51B5" class="mr-1">mdi-eye-check-outline</v-icon>
                  {{ $t('live_formula_test') }}
                </div>
                <v-chip size="x-small" :color="isFormulaInvalid ? 'error' : '#3F51B5'" variant="flat">
                  {{ isFormulaInvalid ? $t('invalid') : (form.unit || 'DH/KG') }}
                </v-chip>
              </div>

              <!-- Inputs for variables to test calculation -->
              <v-row density="compact" class="mb-2">
                <v-col v-for="v in form.variables" :key="'test-' + v.var_name" cols="6" sm="4" md="3">
                  <v-text-field
                    v-model.number="testValues[v.var_name]"
                    :label="v.var_name + ' (' + (v.category_name || getDataSourceLabel(v.data_source)) + ')'"
                    type="number"
                    variant="outlined"
                    density="compact"
                    rounded
                    color="#3F51B5"
                    hide-details
                    bg-color="white"
                    @input="calculateLivePreview"
                  ></v-text-field>
                </v-col>
              </v-row>

              <!-- Live Test Result Output -->
              <div class="d-flex align-center justify-space-between pt-2 border-t">
                <div class="text-caption text-indigo-darken-3 font-weight-bold">
                  {{ $t('evaluated_result') }}:
                </div>
                <div class="text-h6 font-weight-black text-indigo-darken-4">
                  <span v-if="!isFormulaInvalid && previewResult !== null && !isNaN(previewResult)">
                    {{ previewResult }} {{ form.unit }}
                  </span>
                  <span v-else class="text-caption text-error font-weight-bold d-flex align-center ga-1">
                    <v-icon size="16" color="error">mdi-alert-circle</v-icon>
                    {{ previewError || $t('invalid_formula_syntax') }}
                  </span>
                </div>
              </div>
            </v-card>
          </v-form>
        </v-card-text>

        <v-card-actions class="pa-5 pt-0">
          <v-spacer></v-spacer>
          <v-btn @click="dialog = false" variant="text">{{ $t('cancel') }}</v-btn>
          <v-btn
            color="#3F51B5"
            variant="flat"
            rounded
            @click="save"
            :loading="saving"
            :disabled="!form.name_fr || !form.formula || form.variables.length === 0 || isFormulaInvalid"
          >
            {{ $t('save') }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Standalone Test Evaluator Dialog -->
    <v-dialog v-model="evaluateDialog" max-width="500" persistent>
      <v-card class="rounded-xl">
        <v-card-title class="d-flex align-center pa-5 pb-3 bg-indigo-lighten-5">
          <v-icon color="#3F51B5" class="mr-2">mdi-calculator</v-icon>
          <span class="text-subtitle-1 font-weight-bold text-indigo-darken-4">
            {{ $t('test_calculator') }} - {{ evaluatingItem ? (evaluatingItem['name_' + currentLang] || evaluatingItem.name_fr) : '' }}
          </span>
          <v-spacer></v-spacer>
          <v-btn icon variant="text" size="small" @click="evaluateDialog = false">
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </v-card-title>
        <v-divider></v-divider>
        <v-card-text class="pa-5">
          <div v-if="evaluatingItem" class="mb-3">
            <div class="text-caption text-grey mb-1">{{ $t('formula') }}:</div>
            <code class="pa-2 d-block rounded bg-indigo-lighten-5 text-indigo-darken-4 font-weight-bold text-center">
              {{ evaluatingItem.formula }}
            </code>
          </div>

          <div class="text-subtitle-2 font-weight-bold text-indigo mb-2">{{ $t('enter_test_values') }}:</div>

          <v-row density="compact">
            <v-col
              v-for="v in (evaluatingItem?.variables || [])"
              :key="'eval-modal-' + v.var_name"
              cols="12"
              sm="6"
            >
              <v-text-field
                v-model.number="evalModalValues[v.var_name]"
                :label="v.var_name + ' - ' + (v.category_name || getDataSourceLabel(v.data_source)) + ' (' + (v.value_type === 'price' ? $t('price') : $t('weight')) + ')'"
                type="number"
                variant="outlined"
                density="compact"
                rounded
                color="#3F51B5"
                @input="runModalEvaluation"
              ></v-text-field>
            </v-col>
          </v-row>

          <v-card variant="tonal" color="#3F51B5" class="pa-4 rounded-lg mt-3 text-center">
            <div class="text-caption text-indigo-darken-2 font-weight-bold uppercase">{{ $t('calculated_result') }}</div>
            <div class="text-h4 font-weight-black text-indigo-darken-4 my-1">
              <span v-if="evalModalResult !== null && !isNaN(evalModalResult)">
                {{ evalModalResult }} {{ evaluatingItem?.unit }}
              </span>
              <span v-else class="text-body-2 text-error">
                {{ evalModalError || '---' }}
              </span>
            </div>
            <div v-if="evalModalExpr" class="text-caption text-grey-darken-1 font-monospace">
              {{ evalModalExpr }}
            </div>
          </v-card>
        </v-card-text>
        <v-card-actions class="pa-5 pt-0">
          <v-spacer></v-spacer>
          <v-btn color="#3F51B5" variant="flat" rounded @click="evaluateDialog = false">{{ $t('close') }}</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Delete Confirmation Dialog -->
    <v-dialog v-model="deleteDialog" max-width="400">
      <v-card class="rounded-xl">
        <v-card-title class="d-flex align-center pa-5 pb-2">
          <v-icon color="error" class="mr-2">mdi-alert-circle-outline</v-icon>
          {{ $t('confirm_delete') }}
        </v-card-title>
        <v-card-text class="pa-5 pt-2 text-body-1">{{ $t('confirm_delete_cost_configuration_message') }}</v-card-text>
        <v-card-actions class="pa-5 pt-0">
          <v-spacer></v-spacer>
          <v-btn @click="deleteDialog = false" variant="text">{{ $t('cancel') }}</v-btn>
          <v-btn color="error" variant="flat" rounded @click="executeDelete" :loading="deleting">{{ $t('delete') }}</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Summary Snackbar -->
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
      <v-progress-linear :model-value="snackbarProgress" color="#3F51B5" height="3" class="mb-2" rounded></v-progress-linear>
      <div class="d-flex align-center mb-2">
        <v-icon color="success" class="mr-2">mdi-check-circle</v-icon>
        <span class="text-subtitle-1 font-weight-bold">{{ summarySnackbar.title }}</span>
      </div>
      <v-divider class="mb-2"></v-divider>
      <div class="d-flex flex-column ga-1 text-body-2">
        <div v-if="summarySnackbar.name">
          <v-icon size="14" color="#3F51B5" class="mr-1">mdi-calculator-variant</v-icon>
          <span class="text-medium-emphasis">{{ $t('name') }}:</span> <strong>{{ summarySnackbar.name }}</strong>
        </div>
        <div v-if="summarySnackbar.unit">
          <v-icon size="14" color="#3F51B5" class="mr-1">mdi-ruler</v-icon>
          <span class="text-medium-emphasis">{{ $t('measure_unit') }}:</span> <strong>{{ summarySnackbar.unit }}</strong>
        </div>
        <div v-if="summarySnackbar.formula">
          <v-icon size="14" color="#3F51B5" class="mr-1">mdi-function</v-icon>
          <span class="text-medium-emphasis">{{ $t('formula') }}:</span> <code>{{ summarySnackbar.formula }}</code>
        </div>
      </div>
      <template #actions>
        <v-btn variant="text" size="small" icon="mdi-close" @click="summarySnackbar.show = false"></v-btn>
      </template>
    </v-snackbar>
  </v-card>
</template>

<script>
import axios from 'axios';
import mitt from '@/plugins/mitt';
import { useMain } from '@/stores/Main';
import { useCostConfiguration } from '@/stores/CostConfiguration';

export default {
  name: 'CostConfigurationManagement',
  data() {
    return {
      search: '',
      loading: false,
      columnFilters: {},

      dialog: false,
      editing: null,
      saving: false,
      validForm: false,

      form: {
        name_fr: '',
        name_en: '',
        name_es: '',
        unit: 'DH/KG',
        variables: [],
        formula: '',
        description: '',
        enabled: true,
      },

      unitOptions: ['KG', 'DH', 'DH/KG', '%', 'DH/TON', 'MAD', 'USD'],

      // Live formula preview inside edit modal
      testValues: {},
      previewResult: null,
      previewError: '',

      // Standalone evaluator modal
      evaluateDialog: false,
      evaluatingItem: null,
      evalModalValues: {},
      evalModalResult: null,
      evalModalError: '',
      evalModalExpr: '',

      // Delete confirmation
      deleteDialog: false,
      deleteTarget: null,
      deleting: false,

      // Summary Snackbar
      summarySnackbar: {
        show: false,
        title: '',
        name: '',
        unit: '',
        formula: '',
      },
      snackbarProgress: 100,
      snackbarTimer: null,
    };
  },

  computed: {
    main() { return useMain(); },
    costStore() { return useCostConfiguration(); },
    currentLang() { return this.main.lang?.short || 'fr'; },

    headers() {
      return [
        { title: this.$t('name'), key: 'name_fr', align: 'start' },
        { title: this.$t('measure_unit'), key: 'unit', align: 'center' },
        { title: this.$t('formula_variables'), key: 'variables', align: 'start', sortable: false },
        { title: this.$t('formula'), key: 'formula', align: 'center' },
        { title: this.$t('status'), key: 'enabled', align: 'center' },
        { title: this.$t('actions'), key: 'actions', align: 'center', sortable: false },
      ];
    },

    items() {
      return this.costStore.list || [];
    },

    filteredItems() {
      let result = this.items;
      Object.keys(this.columnFilters).forEach(key => {
        const filterVals = this.columnFilters[key];
        if (filterVals && filterVals.length > 0) {
          result = result.filter(item => {
            const val = String(item[key] || '').toLowerCase();
            return filterVals.some(fv => val.includes(String(fv).toLowerCase()));
          });
        }
      });
      return result;
    },

    hasActiveFilters() {
      return Object.values(this.columnFilters).some(v => Array.isArray(v) && v.length > 0);
    },

    dataSourceOptions() {
      return [
        { title: this.$t('charge') + ' (' + this.$t('charges') + ')', value: 'charge' },
        { title: this.$t('production'), value: 'production' },
        { title: this.$t('shipping'), value: 'shipping' },
        { title: this.$t('storage_invoice'), value: 'storage_invoice' },
        { title: this.$t('fortnight_activities'), value: 'fortnight_activities' },
      ];
    },

    isFormulaInvalid() {
      if (!this.form.formula || !this.form.formula.trim()) return true;
      if (this.previewError) return true;
      if (this.previewResult === null || isNaN(this.previewResult) || !isFinite(this.previewResult)) return true;
      return false;
    },
  },

  mounted() {
    this.fetchData();
    mitt.on('model-changed', this._onMC);
  },

  beforeUnmount() {
    mitt.off('model-changed', this._onMC);
    if (this.snackbarTimer) clearInterval(this.snackbarTimer);
  },

  methods: {
    _onMC(d) {
      if (d.model === 'CostConfiguration') {
        this.costStore.fetchAll();
        // Show summary snackbar for other users when websocket arrives
        if (d.data) {
          this.triggerSummarySnackbar(
            d.action === 'created' ? this.$t('record_added_success') : this.$t('record_updated_success'),
            d.data
          );
        }
      }
    },

    async fetchData() {
      this.loading = true;
      await Promise.all([
        this.costStore.fetchAll(),
        this.costStore.fetchCategoriesAndSources(),
      ]);
      this.loading = false;
    },

    getColumnOptions(key) {
      const set = new Set();
      this.items.forEach(item => {
        if (item[key]) set.add(item[key]);
      });
      return Array.from(set).map(v => ({ title: String(v), value: v }));
    },

    getDataSourceLabel(ds) {
      switch (ds) {
        case 'charge': return this.$t('charge');
        case 'production': return this.$t('production');
        case 'shipping': return this.$t('shipping');
        case 'storage_invoice': return this.$t('storage_invoice');
        case 'fortnight_activities': return this.$t('fortnight_activities');
        default: return ds;
      }
    },

    hasCategoryOptions(ds) {
      return ['charge', 'production', 'shipping'].includes(ds);
    },

    getCategoryOptionsForSource(ds) {
      const cats = this.costStore.categories[ds] || [];
      return cats.map(c => ({
        title: c['name_' + this.currentLang] || c.name_fr || c.name,
        value: c.id,
        rawName: c.name_fr || c.name,
      }));
    },

    onDataSourceChange(vRow) {
      if (!this.hasCategoryOptions(vRow.data_source)) {
        vRow.category_id = null;
        vRow.category_name = null;
      }
      if (vRow.data_source === 'fortnight_activities') {
        vRow.value_type = 'price'; // Default to price by requirement
      }
      this.calculateLivePreview();
    },

    onCategorySelect(vRow) {
      if (!vRow.category_id) {
        vRow.category_name = null;
        return;
      }
      const opts = this.getCategoryOptionsForSource(vRow.data_source);
      const matched = opts.find(o => o.value === vRow.category_id);
      vRow.category_name = matched ? matched.title : null;
      this.calculateLivePreview();
    },

    addVariableRow() {
      const nextIndex = this.form.variables.length + 1;
      const varName = 'c' + nextIndex;
      this.form.variables.push({
        var_name: varName,
        data_source: 'charge',
        category_id: null,
        category_name: null,
        value_type: 'price',
      });
      this.testValues[varName] = 10 * nextIndex; // Default test value
      this.calculateLivePreview();
    },

    removeVariableRow(index) {
      const removed = this.form.variables.splice(index, 1)[0];
      if (removed) {
        delete this.testValues[removed.var_name];
      }
      // Re-index remaining variables c1, c2, c3... for cleanliness
      this.form.variables.forEach((v, i) => {
        const oldName = v.var_name;
        const newName = 'c' + (i + 1);
        if (oldName !== newName) {
          if (this.testValues[oldName] !== undefined) {
            this.testValues[newName] = this.testValues[oldName];
            delete this.testValues[oldName];
          }
          v.var_name = newName;
          // Update formula if it contains old variable name
          this.form.formula = this.form.formula.replace(new RegExp('\\b' + oldName + '\\b', 'g'), newName);
        }
      });
      this.calculateLivePreview();
    },

    appendFormulaToken(token) {
      if (!this.form.formula) {
        this.form.formula = token;
      } else {
        const needsSpace = !['(', ')', '.', '%'].includes(token) && !['(', '.'].includes(this.form.formula.slice(-1));
        this.form.formula += (needsSpace ? ' ' : '') + token;
      }
      this.calculateLivePreview();
    },

    onFormulaInputChange() {
      this.calculateLivePreview();
    },

    calculateLivePreview() {
      const rawFormula = (this.form.formula || '').trim();
      if (!rawFormula) {
        this.previewResult = null;
        this.previewError = this.$t('field_required');
        return;
      }

      try {
        // 1. Check parenthetical balance
        let parenDepth = 0;
        for (let i = 0; i < rawFormula.length; i++) {
          if (rawFormula[i] === '(') parenDepth++;
          if (rawFormula[i] === ')') parenDepth--;
          if (parenDepth < 0) {
            this.previewResult = null;
            this.previewError = this.$t('syntax_error');
            return;
          }
        }
        if (parenDepth !== 0) {
          this.previewResult = null;
          this.previewError = this.$t('syntax_error');
          return;
        }

        // 2. Check for undefined variable identifiers
        const definedVars = new Set(this.form.variables.map(v => v.var_name));
        const words = rawFormula.match(/\b[a-zA-Z_][a-zA-Z0-9_]*\b/g) || [];
        for (const w of words) {
          if (!definedVars.has(w)) {
            this.previewResult = null;
            this.previewError = this.$t('contains_undefined_variables') + ': ' + w;
            return;
          }
        }

        // 3. Substitute variable values (use positive default 10 if testValue is zero/empty to avoid div-by-zero during syntax check)
        let expr = rawFormula;
        const sortedVars = [...this.form.variables].sort((a, b) => b.var_name.length - a.var_name.length);

        sortedVars.forEach(v => {
          const rawVal = Number(this.testValues[v.var_name]);
          const val = (isNaN(rawVal) || rawVal === 0) ? 10 : rawVal;
          const escapedVar = v.var_name.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
          expr = expr.replace(new RegExp('\\b' + escapedVar + '\\b', 'g'), String(val));
        });

        // 4. Convert percentage operators e.g. 50% or (x)% to (x/100)
        expr = expr.replace(/(\d+(\.\d+)?)%/g, '($1/100)');

        // 5. Verify allowed characters only (digits, +, -, *, /, %, (, ), ., spaces)
        if (/[^0-9\+\-\*\/\%\(\)\.\s]/.test(expr)) {
          this.previewResult = null;
          this.previewError = this.$t('contains_undefined_variables');
          return;
        }

        // 6. Safe JavaScript Math evaluation
        const computed = Function('"use strict"; return (' + expr + ')')();

        if (computed === undefined || isNaN(computed) || !isFinite(computed)) {
          this.previewResult = null;
          this.previewError = this.$t('calculation_error');
        } else {
          this.previewResult = Math.round(computed * 10000) / 10000;
          this.previewError = '';
        }
      } catch (e) {
        this.previewResult = null;
        this.previewError = this.$t('syntax_error');
      }
    },

    openDialog(item) {
      this.editing = item;
      if (item) {
        this.form = {
          name_fr: item.name_fr || '',
          name_en: item.name_en || '',
          name_es: item.name_es || '',
          unit: item.unit || 'DH/KG',
          variables: JSON.parse(JSON.stringify(item.variables || [])),
          formula: item.formula || '',
          description: item.description || '',
          enabled: item.enabled !== undefined ? item.enabled : true,
        };
      } else {
        this.form = {
          name_fr: '',
          name_en: '',
          name_es: '',
          unit: 'DH/KG',
          variables: [
            { var_name: 'c1', data_source: 'charge', category_id: null, category_name: null, value_type: 'price' },
            { var_name: 'c2', data_source: 'production', category_id: null, category_name: null, value_type: 'weight' },
            { var_name: 'c3', data_source: 'production', category_id: null, category_name: null, value_type: 'weight' },
          ],
          formula: '((c2 + c3) / c1) * 100',
          description: '',
          enabled: true,
        };
      }

      // Initialize test values
      this.testValues = {};
      this.form.variables.forEach((v, i) => {
        this.testValues[v.var_name] = (i + 1) * 10;
      });

      this.dialog = true;
      this.$nextTick(() => {
        this.calculateLivePreview();
      });
    },

    async save() {
      if (!this.form.name_fr || !this.form.formula || this.form.variables.length === 0) return;
      this.saving = true;

      try {
        let savedItem = null;
        if (this.editing) {
          savedItem = await this.costStore.update(this.editing.id, this.form);
          this.triggerSummarySnackbar(this.$t('record_updated_success'), savedItem);
        } else {
          savedItem = await this.costStore.create(this.form);
          this.triggerSummarySnackbar(this.$t('record_added_success'), savedItem);
        }
        this.dialog = false;
      } catch (e) {
        console.error(e);
      }
      this.saving = false;
    },

    openEvaluateDialog(item) {
      this.evaluatingItem = item;
      this.evalModalValues = {};
      (item.variables || []).forEach((v, i) => {
        this.evalModalValues[v.var_name] = (i + 1) * 25;
      });
      this.evaluateDialog = true;
      this.runModalEvaluation();
    },

    async runModalEvaluation() {
      if (!this.evaluatingItem) return;
      try {
        const res = await this.costStore.evaluateFormula(this.evaluatingItem.formula, this.evalModalValues);
        if (res.success) {
          this.evalModalResult = res.result;
          this.evalModalExpr = res.evaluated_expression;
          this.evalModalError = '';
        } else {
          this.evalModalResult = null;
          this.evalModalError = res.message || this.$t('calculation_error');
          this.evalModalExpr = '';
        }
      } catch (e) {
        this.evalModalResult = null;
        this.evalModalError = this.$t('error');
      }
    },

    confirmDelete(item) {
      this.deleteTarget = item;
      this.deleteDialog = true;
    },

    async executeDelete() {
      if (!this.deleteTarget) return;
      this.deleting = true;
      try {
        await this.costStore.remove(this.deleteTarget.id);
        this.deleteDialog = false;
        this.deleteTarget = null;
      } catch (e) {
        console.error(e);
      }
      this.deleting = false;
    },

    triggerSummarySnackbar(title, item) {
      this.summarySnackbar = {
        show: true,
        title: title || this.$t('record_added_success'),
        name: item['name_' + this.currentLang] || item.name_fr || item.name || '',
        unit: item.unit || '',
        formula: item.formula || '',
      };
      this.snackbarProgress = 100;
      if (this.snackbarTimer) clearInterval(this.snackbarTimer);
      const step = 100 / 60;
      this.snackbarTimer = setInterval(() => {
        this.snackbarProgress -= step;
        if (this.snackbarProgress <= 0) {
          clearInterval(this.snackbarTimer);
          this.summarySnackbar.show = false;
        }
      }, 100);
    },
  },
};
</script>

<style scoped>
.ccm-card {
  border-radius: 20px !important;
  overflow: visible;
  font-family: 'Inter', sans-serif;
}
.ccm-hero {
  background: linear-gradient(135deg, #1A237E 0%, #283593 40%, #3F51B5 100%);
  border-radius: inherit;
  border-bottom-left-radius: 0;
  border-bottom-right-radius: 0;
  height: 100px;
}
.ccm-hero-inner {
  position: relative;
  z-index: 1;
  padding: 22px 26px;
  color: #fff;
}
.ccm-hero-icon {
  width: 52px;
  height: 52px;
  border-radius: 14px;
  background: rgba(255,255,255,.18);
  backdrop-filter: blur(6px);
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid rgba(255,255,255,.15);
}
.ccm-hero-title {
  font-size: 19px;
  font-weight: 800;
  letter-spacing: .3px;
}
.param-th {
  padding: 10px 8px 4px !important;
}
.param-th-label {
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .4px;
  opacity: .7;
  white-space: nowrap;
}
.param-filter-row th {
  padding: 0 4px 6px !important;
}
.param-filter-th {
  vertical-align: top;
}
.param-filter-input {
  min-width: 80px;
}
.param-filter-input :deep(.v-field) {
  font-size: 11px !important;
  min-height: 30px !important;
  border-radius: 8px !important;
}
.param-filter-input :deep(.v-field__input) {
  padding: 2px 6px !important;
  min-height: 26px !important;
  font-size: 11px;
}
.param-filter-input :deep(.v-field__clearable) {
  font-size: 14px;
}
.param-filter-input :deep(.v-chip) {
  height: 18px !important;
  font-size: 10px !important;
}
.cursor-pointer {
  cursor: pointer;
}
.font-monospace {
  font-family: 'Fira Code', 'Courier New', Courier, monospace !important;
}
</style>
