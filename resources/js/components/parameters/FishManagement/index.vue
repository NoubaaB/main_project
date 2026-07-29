<template>
  <v-card class="fm-card" elevation="0">
    <!-- Hero Header -->
    <div class="fm-hero">
      <div class="fm-hero-inner">
        <div class="d-flex align-center ga-4">
          <div class="fm-hero-icon">
            <v-icon size="28" color="white">mdi-fish</v-icon>
          </div>
          <div>
            <div class="fm-hero-title">{{ $t('fish_management') }}</div>
            <div class="text-white text-caption" style="opacity:.7">{{ $t('manage_fish_families_types') }}</div>
          </div>
        </div>
      </div>
    </div>

    <v-tabs v-model="activeTab" color="info" class="mx-4 mt-2" slider-color="info">
      <v-tab value="families"><v-icon start>mdi-family-tree</v-icon>{{ $t('fish_families') }}</v-tab>
      <v-tab value="types"><v-icon start>mdi-fish</v-icon>{{ $t('fish_types') }}</v-tab>
      <v-tab value="process_types"><v-icon start>mdi-cog-outline</v-icon>{{ $t('process_types') }}</v-tab>
    </v-tabs>

    <v-divider></v-divider>

    <v-card-text class="pa-4">
      <v-window v-model="activeTab">
        <!-- ============= FISH FAMILIES TAB ============= -->
        <v-window-item value="families">
          <div class="d-flex align-center ga-3 mb-3">
            <v-spacer></v-spacer>
            <v-btn v-if="hasActiveFamilyFilters" variant="tonal" color="warning" rounded size="small" prepend-icon="mdi-filter-off" @click="familyColumnFilters = {}">{{ $t('clear_filters') }}</v-btn>
            <v-btn color="info" prepend-icon="mdi-plus" rounded @click="openFamilyDialog(null)">{{ $t('add_fish_family') }}</v-btn>
          </div>
          <v-data-table :headers="familyHeaders" :items="filteredFamilies" :loading="loadingFamilies ? 'info' : false" density="comfortable" class="rounded-lg param-table" hover>
            <template v-slot:headers="{ columns, isSorted, getSortIcon, toggleSort }">
              <tr><th v-for="col in columns" :key="col.key" class="param-th"><div class="param-th-label" @click="col.sortable !== false ? toggleSort(col) : null" :style="{ cursor: col.sortable !== false ? 'pointer' : 'default' }">{{ col.title }}<v-icon v-if="isSorted(col)" size="14" class="ms-1">{{ getSortIcon(col) }}</v-icon></div></th></tr>
              <tr class="param-filter-row"><th v-for="col in columns" :key="col.key + '-f'" class="param-filter-th"><v-autocomplete v-if="col.key !== 'actions'" v-model="familyColumnFilters[col.key]" :items="getFamilyColumnOptions(col.key)" density="compact" variant="outlined" color="info" class="param-filter-input text-info" hide-details clearable multiple chips closable-chips :placeholder="$t('filter')"><template #chip="{ item, props }"><v-chip v-bind="props" size="x-small" color="info" variant="tonal" closable>{{ item.title }}</v-chip></template></v-autocomplete></th></tr>
            </template>
            <template v-slot:[`item.fish_types_count`]="{ item }"><v-chip color="info" size="small" variant="tonal">{{ item.fish_types_count || 0 }}</v-chip></template>
            <template v-slot:[`item.actions`]="{ item }">
              <v-btn icon size="x-small" variant="tonal" color="info" @click="openFamilyDialog(item)" class="mr-1"><v-icon size="16">mdi-pencil</v-icon></v-btn>
              <v-btn icon size="x-small" variant="tonal" color="error" @click="deleteFamily(item)"><v-icon size="16">mdi-delete</v-icon></v-btn>
            </template>
          </v-data-table>
        </v-window-item>

        <!-- ============= FISH TYPES TAB ============= -->
        <v-window-item value="types">
          <div class="d-flex align-center ga-3 mb-3">
            <v-spacer></v-spacer>
            <v-btn v-if="hasActiveTypeFilters" variant="tonal" color="warning" rounded size="small" prepend-icon="mdi-filter-off" @click="typeColumnFilters = {}">{{ $t('clear_filters') }}</v-btn>
            <v-btn color="info" prepend-icon="mdi-plus" rounded @click="openTypeDialog(null)">{{ $t('add_fish_type') }}</v-btn>
          </div>
          <v-data-table :headers="typeHeaders" :items="filteredTypes" :loading="loadingTypes ? 'info' : false" density="comfortable" class="rounded-lg param-table" hover>
            <template v-slot:headers="{ columns, isSorted, getSortIcon, toggleSort }">
              <tr><th v-for="col in columns" :key="col.key" class="param-th"><div class="param-th-label" @click="col.sortable !== false ? toggleSort(col) : null" :style="{ cursor: col.sortable !== false ? 'pointer' : 'default' }">{{ col.title }}<v-icon v-if="isSorted(col)" size="14" class="ms-1">{{ getSortIcon(col) }}</v-icon></div></th></tr>
              <tr class="param-filter-row"><th v-for="col in columns" :key="col.key + '-f'" class="param-filter-th"><v-autocomplete v-if="col.key !== 'actions'" v-model="typeColumnFilters[col.key]" :items="getTypeColumnOptions(col.key)" density="compact" variant="outlined" color="info" class="param-filter-input text-info" hide-details clearable multiple chips closable-chips :placeholder="$t('filter')"><template #chip="{ item, props }"><v-chip v-bind="props" size="x-small" color="info" variant="tonal" closable>{{ item.title }}</v-chip></template></v-autocomplete></th></tr>
            </template>
            <template v-slot:[`item.type`]="{ item }">
              <v-chip v-if="item.type" size="small" variant="tonal" :color="item.type === 'fresh' ? 'teal' : 'blue'">{{ $t(item.type) }}</v-chip>
              <span v-else class="text-grey">-</span>
            </template>
            <template v-slot:[`item.fish_family`]="{ item }">
              <v-chip v-if="item.fish_family" color="teal" size="small" variant="tonal">{{ item.fish_family['name_' + main.lang.short] || item.fish_family.name_en }}</v-chip>
              <span v-else class="text-grey">-</span>
            </template>
            <template v-slot:[`item.fish_process_type`]="{ item }">
              <v-chip v-if="item.fish_process_type" size="small" variant="tonal" :color="item.productivity_color || 'info'">{{ item.fish_process_type.name }}</v-chip>
              <span v-else class="text-grey">-</span>
            </template>
            <template v-slot:[`item.productivity_color`]="{ item }">
              <div v-if="item.productivity_color" class="d-flex align-center justify-center">
                <div class="fm-color-dot" :style="{ background: item.productivity_color }"></div>
                <span class="text-caption ml-1">{{ item.productivity_color }}</span>
              </div>
              <span v-else class="text-grey">-</span>
            </template>
            <template v-slot:[`item.image`]="{ item }">
              <v-avatar v-if="item.image" size="32" rounded="lg"><v-img :src="item.image" :alt="item.name_en" cover></v-img></v-avatar>
              <span v-else class="text-grey">-</span>
            </template>
            <template v-slot:[`item.actions`]="{ item }">
              <v-btn icon size="x-small" variant="tonal" color="purple" @click="openWapDialog(item)" class="mr-1" :title="$t('wap_config')"><v-icon size="16">mdi-calculator</v-icon></v-btn>
              <v-btn icon size="x-small" variant="tonal" color="info" @click="openTypeDialog(item)" class="mr-1"><v-icon size="16">mdi-pencil</v-icon></v-btn>
              <v-btn icon size="x-small" variant="tonal" color="error" @click="deleteType(item)"><v-icon size="16">mdi-delete</v-icon></v-btn>
            </template>
          </v-data-table>
        </v-window-item>

        <!-- ============= PROCESS TYPES TAB ============= -->
        <v-window-item value="process_types">
          <div class="d-flex align-center ga-3 mb-3">
            <v-spacer></v-spacer>
            <v-btn v-if="hasActiveProcessFilters" variant="tonal" color="warning" rounded size="small" prepend-icon="mdi-filter-off" @click="processColumnFilters = {}">{{ $t('clear_filters') }}</v-btn>
            <v-btn color="info" prepend-icon="mdi-plus" rounded @click="openProcessDialog(null)">{{ $t('add_process_type') }}</v-btn>
          </div>
          <v-data-table :headers="processHeaders" :items="filteredProcessTypes" :loading="loadingProcessTypes ? 'info' : false" density="comfortable" class="rounded-lg param-table" hover>
            <template v-slot:headers="{ columns, isSorted, getSortIcon, toggleSort }">
              <tr><th v-for="col in columns" :key="col.key" class="param-th"><div class="param-th-label" @click="col.sortable !== false ? toggleSort(col) : null" :style="{ cursor: col.sortable !== false ? 'pointer' : 'default' }">{{ col.title }}<v-icon v-if="isSorted(col)" size="14" class="ms-1">{{ getSortIcon(col) }}</v-icon></div></th></tr>
              <tr class="param-filter-row"><th v-for="col in columns" :key="col.key + '-f'" class="param-filter-th"><v-autocomplete v-if="col.key !== 'actions'" v-model="processColumnFilters[col.key]" :items="getProcessColumnOptions(col.key)" density="compact" variant="outlined" color="info" class="param-filter-input text-info" hide-details clearable multiple chips closable-chips :placeholder="$t('filter')"><template #chip="{ item, props }"><v-chip v-bind="props" size="x-small" color="info" variant="tonal" closable>{{ item.title }}</v-chip></template></v-autocomplete></th></tr>
            </template>
            <template v-slot:[`item.fish_types_count`]="{ item }"><v-chip color="info" size="small" variant="tonal">{{ item.fish_types_count || 0 }}</v-chip></template>
            <template v-slot:[`item.actions`]="{ item }">
              <v-btn icon size="x-small" variant="tonal" color="info" @click="openProcessDialog(item)" class="mr-1"><v-icon size="16">mdi-pencil</v-icon></v-btn>
              <v-btn icon size="x-small" variant="tonal" color="error" @click="deleteProcess(item)"><v-icon size="16">mdi-delete</v-icon></v-btn>
            </template>
          </v-data-table>
        </v-window-item>
      </v-window>
    </v-card-text>

    <!-- ============= FAMILY DIALOG ============= -->
    <v-dialog v-model="familyDialog" max-width="500" persistent>
      <v-card class="rounded-xl">
        <v-card-title class="d-flex align-center pa-5 pb-2">
          <v-icon color="info" class="mr-2">mdi-family-tree</v-icon>
          {{ editingFamily ? $t('edit_fish_family') : $t('add_fish_family') }}
        </v-card-title>
        <v-card-text class="pa-5">
          <v-text-field v-model="familyForm.name_fr" :label="$t('name_fr')" variant="outlined" density="compact" rounded color="info" class="text-info mb-3"></v-text-field>
          <v-text-field v-model="familyForm.name_en" :label="$t('name_en')" variant="outlined" density="compact" rounded color="info" class="text-info mb-3"></v-text-field>
          <v-text-field v-model="familyForm.name_es" :label="$t('name_es')" variant="outlined" density="compact" rounded color="info" class="text-info"></v-text-field>
        </v-card-text>
        <v-card-actions class="pa-5 pt-0">
          <v-spacer></v-spacer>
          <v-btn @click="familyDialog = false" variant="text">{{ $t('cancel') }}</v-btn>
          <v-btn color="info" variant="flat" rounded @click="saveFamily" :loading="saving">{{ $t('save') }}</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- ============= FISH TYPE DIALOG ============= -->
    <v-dialog v-model="typeDialog" max-width="700" persistent>
      <v-card class="rounded-xl">
        <v-card-title class="d-flex align-center pa-5 pb-2">
          <v-icon color="info" class="mr-2">mdi-fish</v-icon>
          {{ editingType ? $t('edit_fish_type') : $t('add_fish_type') }}
        </v-card-title>
        <v-card-text class="pa-5">
          <v-row>
            <v-col cols="6">
              <v-text-field v-model="typeForm.name_fr" :label="$t('name_fr')" variant="outlined" density="compact" rounded color="info" class="text-info"></v-text-field>
            </v-col>
            <v-col cols="6">
              <v-text-field v-model="typeForm.name_en" :label="$t('name_en')" variant="outlined" density="compact" rounded color="info" class="text-info"></v-text-field>
            </v-col>
            <v-col cols="6">
              <v-text-field v-model="typeForm.name_es" :label="$t('name_es')" variant="outlined" density="compact" rounded color="info" class="text-info"></v-text-field>
            </v-col>
            <v-col cols="6">
              <v-text-field v-model="typeForm.name_in_invoice" :label="$t('name_in_invoice')" variant="outlined" density="compact" rounded color="info" class="text-info"></v-text-field>
            </v-col>
            <v-col cols="6">
              <v-text-field v-model="typeForm.scientific_name" :label="$t('scientific_name')" variant="outlined" density="compact" rounded color="info" class="text-info"></v-text-field>
            </v-col>
            <v-col cols="6">
              <v-autocomplete
                v-model="typeForm.fish_family_id"
                :items="families"
                :item-title="familyTitle"
                item-value="id"
                :label="$t('select_family')"
                variant="outlined"
                density="compact"
                rounded
                clearable
                chips
                color="info"
                class="text-info"
              >
                <template #chip="{ item, index }">
                  <v-chip :key="index" class="text-white" color="info accent-4" variant="tonal">{{ item.title }}</v-chip>
                </template>
              </v-autocomplete>
            </v-col>
            <v-col cols="6">
              <v-autocomplete
                v-model="typeForm.type"
                :items="fishTypeOptions"
                item-title="label"
                item-value="value"
                :label="$t('fish_category_type')"
                variant="outlined"
                density="compact"
                rounded
                clearable
                chips
                color="info"
                class="text-info"
              >
                <template #chip="{ item, index }">
                  <v-chip :key="index" :color="item.raw.value === 'fresh' ? 'teal' : 'blue'" variant="tonal">{{ item.title }}</v-chip>
                </template>
              </v-autocomplete>
            </v-col>
            <v-col cols="6">
              <v-text-field v-model="typeForm.process" :label="$t('fish_process')" variant="outlined" density="compact" rounded color="info" class="text-info"></v-text-field>
            </v-col>
            <v-col cols="6">
              <v-text-field v-model="typeForm.image" :label="$t('image_url')" variant="outlined" density="compact" rounded color="info" class="text-info" prepend-inner-icon="mdi-image-outline"></v-text-field>
            </v-col>
            <v-col cols="6">
              <v-textarea v-model="typeForm.description" :label="$t('description')" variant="outlined" density="compact" rounded color="info" class="text-info" rows="2"></v-textarea>
            </v-col>
          </v-row>

          <v-divider class="my-3"></v-divider>

          <!-- Productivity Section -->
          <div class="text-subtitle-2 font-weight-bold mb-3 d-flex align-center">
            <v-icon color="info" class="mr-2" size="20">mdi-chart-line</v-icon>
            {{ $t('productivity_type') }}
          </div>

          <v-row>
            <v-col cols="8">
              <v-autocomplete
                v-model="typeForm.fish_process_type_id"
                :items="processTypes"
                item-title="name"
                item-value="id"
                :label="$t('select_productivity_type')"
                :placeholder="$t('type_and_enter_to_create')"
                variant="outlined"
                density="compact"
                rounded
                clearable
                chips
                color="info"
                class="text-info"
                :search="processTypeSearch"
                @update:search="processTypeSearch = $event"
                @keydown.enter.prevent="onProcessTypeEnter"
                :loading="creatingProcessType"
              >
                <template #chip="{ item, index }">
                  <v-chip :key="index" class="text-white" :color="typeForm.productivity_color || 'info accent-4'" variant="tonal">{{ item.title }}</v-chip>
                </template>
                <template #no-data>
                  <v-list-item>
                    <v-list-item-title class="text-caption text-grey">
                      <v-icon size="16" class="mr-1">mdi-keyboard-return</v-icon>
                      {{ $t('type_and_enter_to_create') }}
                    </v-list-item-title>
                  </v-list-item>
                </template>
              </v-autocomplete>

              <!-- Created success message -->
              <v-slide-y-transition>
                <v-alert
                  v-if="processTypeCreatedMsg"
                  type="success"
                  variant="tonal"
                  density="compact"
                  class="mt-1"
                  closable
                  @click:close="processTypeCreatedMsg = ''"
                >
                  {{ processTypeCreatedMsg }}
                </v-alert>
              </v-slide-y-transition>
            </v-col>
            <v-col cols="4">
              <div class="text-caption text-grey mb-1">{{ $t('productivity_color') }}</div>
              <v-menu :close-on-content-click="false" location="bottom">
                <template v-slot:activator="{ props }">
                  <v-btn
                    v-bind="props"
                    variant="outlined"
                    rounded
                    block
                    class="fm-color-btn"
                    :style="{ borderColor: typeForm.productivity_color || '#ccc' }"
                  >
                    <div class="fm-color-dot mr-2" :style="{ background: typeForm.productivity_color || '#ccc' }"></div>
                    {{ typeForm.productivity_color || $t('select') }}
                  </v-btn>
                </template>
                <v-color-picker
                  v-model="typeForm.productivity_color"
                  :modes="['hexa']"
                  show-swatches
                  swatches-max-height="120"
                  elevation="4"
                  rounded
                ></v-color-picker>
              </v-menu>
            </v-col>
          </v-row>
        </v-card-text>
        <v-card-actions class="pa-5 pt-0">
          <v-spacer></v-spacer>
          <v-btn @click="typeDialog = false" variant="text">{{ $t('cancel') }}</v-btn>
          <v-btn color="info" variant="flat" rounded @click="saveType" :loading="saving">{{ $t('save') }}</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- ============= PROCESS TYPE DIALOG ============= -->
    <v-dialog v-model="processDialog" max-width="500" persistent>
      <v-card class="rounded-xl">
        <v-card-title class="d-flex align-center pa-5 pb-2">
          <v-icon color="info" class="mr-2">mdi-cog-outline</v-icon>
          {{ editingProcess ? $t('edit_process_type') : $t('add_process_type') }}
        </v-card-title>
        <v-card-text class="pa-5">
          <v-text-field v-model="processForm.name" :label="$t('name')" variant="outlined" density="compact" rounded color="info" class="text-info mb-3"></v-text-field>
          <v-textarea v-model="processForm.description" :label="$t('description')" variant="outlined" density="compact" rounded color="info" class="text-info" rows="2"></v-textarea>
        </v-card-text>
        <v-card-actions class="pa-5 pt-0">
          <v-spacer></v-spacer>
          <v-btn @click="processDialog = false" variant="text">{{ $t('cancel') }}</v-btn>
          <v-btn color="info" variant="flat" rounded @click="saveProcess" :loading="saving">{{ $t('save') }}</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- ============= CONFIRM DELETE DIALOG ============= -->
    <v-dialog v-model="confirmDeleteDialog" max-width="400">
      <v-card class="rounded-xl">
        <v-card-title class="d-flex align-center pa-5 pb-2">
          <v-icon color="error" class="mr-2">mdi-alert-circle-outline</v-icon>
          {{ $t('confirm_delete') }}
        </v-card-title>
        <v-card-text class="pa-5 pt-2 text-body-1">
          {{ $t('confirm_delete_message') }}
        </v-card-text>
        <v-card-actions class="pa-5 pt-0">
          <v-spacer></v-spacer>
          <v-btn @click="confirmDeleteDialog = false" variant="text">{{ $t('cancel') }}</v-btn>
          <v-btn color="error" variant="flat" rounded @click="executeDelete">{{ $t('delete') }}</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- ============= WAP CONFIGURATION DIALOG ============= -->
    <v-dialog v-model="wapDialog" max-width="850" persistent>
      <v-card class="rounded-xl">
        <v-card-title class="d-flex align-center pa-5 pb-2">
          <v-icon color="purple" class="mr-2">mdi-calculator</v-icon>
          <span class="text-subtitle-1 font-weight-bold">
            {{ $t('wap_config') }} - {{ selectedFishTypeForWap ? (selectedFishTypeForWap['name_' + main.lang.short] || selectedFishTypeForWap.name_en) : '' }}
          </span>
          <v-spacer></v-spacer>
          <v-btn icon variant="text" size="small" @click="closeWapDialog"><v-icon>mdi-close</v-icon></v-btn>
        </v-card-title>
        <v-divider></v-divider>
        <v-card-text class="pa-5">
          <!-- WAP Rule Form -->
          <v-card variant="outlined" class="rounded-lg pa-4 mb-4" style="border-color: rgba(147, 66, 245, 0.25); background: rgba(147, 66, 245, 0.02)">
            <div class="text-subtitle-2 font-weight-bold mb-3 d-flex align-center text-purple">
              <v-icon size="18" color="purple" class="mr-1">mdi-plus-circle-outline</v-icon>
              {{ editingWapRule ? $t('edit_wap_rule') : $t('add_wap_rule') }}
            </div>
            <v-row dense>
              <v-col cols="12" sm="6">
                <v-autocomplete
                  v-model="wapForm.warehouse_id"
                  :items="warehousesList"
                  item-title="name"
                  item-value="id"
                  :label="$t('select_warehouse')"
                  variant="outlined"
                  density="compact"
                  rounded
                  color="purple"
                  class="text-purple mb-2"
                  hide-details
                ></v-autocomplete>
              </v-col>
              <v-col cols="12" sm="6">
                <v-text-field
                  v-model.number="wapForm.wap"
                  type="number"
                  step="0.01"
                  min="0"
                  :label="$t('wap') + ' (MAD/Kg)'"
                  variant="outlined"
                  density="compact"
                  rounded
                  color="purple"
                  class="text-purple mb-2"
                  hide-details
                  prepend-inner-icon="mdi-cash"
                ></v-text-field>
              </v-col>
              <v-col cols="12">
                <div class="d-flex align-center justify-space-between mb-2">
                  <div class="text-caption font-weight-bold opacity-70">{{ $t('wap_by_date_range') }}</div>
                  <v-checkbox
                    v-model="wapForm.is_indefinite"
                    :label="$t('date_range')"
                    density="compact"
                    hide-details
                    color="purple"
                  ></v-checkbox>
                </div>
                <!-- Extended Calendar -->
                <DatePicker
                  v-if="wapForm.is_indefinite"
                  v-model.range="wapDateRangeVC"
                  expanded
                  :is-dark="main.is_dark"
                  mode="date"
                  color="purple"
                  class="rounded-lg mb-2"
                />
                <DatePicker
                  v-else
                  v-model="wapSingleDateVC"
                  expanded
                  :is-dark="main.is_dark"
                  mode="date"
                  color="purple"
                  class="rounded-lg mb-2"
                />
                <div class="d-flex align-center ga-4 text-caption mt-1">
                  <div><b>{{ $t('date_start') }}:</b> <span class="text-purple font-weight-bold">{{ wapStartDateDisplay }}</span></div>
                  <div><b>{{ $t('date_end') }}:</b> <span class="text-purple font-weight-bold">{{ wapEndDateDisplay }}</span></div>
                </div>
              </v-col>
              <v-col cols="12" class="d-flex align-center justify-end mt-2">
                <v-btn v-if="editingWapRule" variant="text" size="small" @click="resetWapForm" class="mr-2">{{ $t('cancel') }}</v-btn>
                <v-btn color="purple" variant="flat" rounded :loading="savingWapRule" @click="saveWapRule" prepend-icon="mdi-check">
                  {{ editingWapRule ? $t('update') : $t('save') }}
                </v-btn>
              </v-col>
            </v-row>
          </v-card>

          <!-- Existing Rules Table Header with Chart Button -->
          <div class="d-flex align-center justify-space-between mb-2">
            <div class="text-subtitle-2 font-weight-bold">{{ $t('wap_by_date_range') }}</div>
            <v-btn
              color="purple"
              variant="tonal"
              rounded
              size="small"
              prepend-icon="mdi-chart-timeline-variant"
              @click="wapChartModalDialog = true"
            >
              {{ $t('wap_chart_statistics') }}
            </v-btn>
          </div>
          <v-data-table
            :headers="wapHeaders"
            :items="wapsList"
            :loading="loadingWaps ? 'purple' : false"
            density="compact"
            class="rounded-lg param-table"
            hover
          >
            <template v-slot:[`item.warehouse`]="{ item }">
              <span class="font-weight-bold">{{ item.warehouse ? item.warehouse.name : '-' }}</span>
            </template>
            <template v-slot:[`item.date_start`]="{ item }">
              <span>{{ main.formateDate(item.date_start) }}</span>
            </template>
            <template v-slot:[`item.date_end`]="{ item }">
              <v-chip size="x-small" :color="item.date_end ? 'purple' : 'warning'" variant="tonal" class="font-weight-bold">
                {{ item.date_end ? main.formateDate(item.date_end) : $t('indefinite') }}
              </v-chip>
            </template>
            <template v-slot:[`item.wap`]="{ item }">
              <b class="text-purple">{{ main.formatDisplayNumber(item.wap) }} MAD/Kg</b>
            </template>
            <template v-slot:[`item.actions`]="{ item }">
              <v-btn icon size="x-small" variant="tonal" color="purple" @click="editWapRule(item)" class="mr-1"><v-icon size="14">mdi-pencil</v-icon></v-btn>
              <v-btn icon size="x-small" variant="tonal" color="error" @click="deleteWapRule(item)"><v-icon size="14">mdi-delete</v-icon></v-btn>
            </template>
          </v-data-table>
        </v-card-text>
        <v-card-actions class="pa-5 pt-0">
          <v-spacer></v-spacer>
          <v-btn @click="closeWapDialog" variant="text">{{ $t('close') }}</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- ============= WAP CHART & STATISTICS DIALOG ============= -->
    <v-dialog v-model="wapChartModalDialog" max-width="900" persistent>
      <v-card class="rounded-xl">
        <v-card-title class="d-flex align-center pa-5 pb-2">
          <v-icon color="purple" class="mr-2">mdi-chart-timeline-variant</v-icon>
          <span class="text-subtitle-1 font-weight-bold">
            {{ $t('wap_chart_statistics') }} - {{ selectedFishTypeForWap ? (selectedFishTypeForWap['name_' + main.lang.short] || selectedFishTypeForWap.name_en) : '' }}
          </span>
          <v-spacer></v-spacer>
          <v-btn icon variant="text" size="small" @click="wapChartModalDialog = false"><v-icon>mdi-close</v-icon></v-btn>
        </v-card-title>
        <v-divider></v-divider>
        <v-card-text class="pa-5">
          <!-- Summary Statistics Cards -->
          <v-row dense class="mb-4">
            <v-col cols="12" sm="3">
              <v-card variant="tonal" color="purple" class="pa-3 rounded-lg text-center">
                <div class="text-caption text-medium-emphasis">{{ $t('min_wap') }}</div>
                <div class="text-h6 font-weight-bold text-purple">{{ main.formatDisplayNumber(wapStats.min) }} MAD/Kg</div>
              </v-card>
            </v-col>
            <v-col cols="12" sm="3">
              <v-card variant="tonal" color="purple" class="pa-3 rounded-lg text-center">
                <div class="text-caption text-medium-emphasis">{{ $t('avg_wap') }}</div>
                <div class="text-h6 font-weight-bold text-purple">{{ main.formatDisplayNumber(wapStats.avg) }} MAD/Kg</div>
              </v-card>
            </v-col>
            <v-col cols="12" sm="3">
              <v-card variant="tonal" color="purple" class="pa-3 rounded-lg text-center">
                <div class="text-caption text-medium-emphasis">{{ $t('max_wap') }}</div>
                <div class="text-h6 font-weight-bold text-purple">{{ main.formatDisplayNumber(wapStats.max) }} MAD/Kg</div>
              </v-card>
            </v-col>
            <v-col cols="12" sm="3">
              <v-card variant="tonal" color="purple" class="pa-3 rounded-lg text-center">
                <div class="text-caption text-medium-emphasis">{{ $t('active_rules') }}</div>
                <div class="text-h6 font-weight-bold text-purple">{{ wapsList.length }}</div>
              </v-card>
            </v-col>
          </v-row>

          <!-- ApexChart for WAP Evolution -->
          <v-card variant="outlined" class="pa-4 rounded-xl" style="border-color: rgba(147, 66, 245, 0.2)">
            <div class="text-subtitle-2 font-weight-bold mb-3 d-flex align-center text-purple">
              <v-icon size="18" class="mr-1">mdi-chart-line</v-icon>
              {{ $t('wap_evolution') }}
            </div>
            <apexchart
              v-if="wapChartSeries.length && wapChartSeries[0].data.length"
              type="line"
              :height="350"
              :options="wapChartOptions"
              :series="wapChartSeries"
            ></apexchart>
            <div v-else class="text-center text-caption text-grey pa-8">
              <v-icon size="40" color="grey" class="mb-2">mdi-chart-line</v-icon>
              <div>{{ $t('no_data_available') }}</div>
            </div>
          </v-card>
        </v-card-text>
        <v-card-actions class="pa-5 pt-0">
          <v-spacer></v-spacer>
          <v-btn color="purple" variant="flat" rounded @click="wapChartModalDialog = false">{{ $t('close') }}</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Summary Snackbar -->
    <v-snackbar v-model="snackbar.show" :color="snackbar.color" location="bottom end" elevation="4" rounded="lg">
      <div class="d-flex align-center"><v-icon class="mr-2">mdi-information</v-icon><span>{{ snackbar.text }}</span></div>
      <template v-slot:actions>
        <v-btn variant="text" size="small" @click="snackbar.show = false">{{ $t('close') }}</v-btn>
      </template>
    </v-snackbar>
  </v-card>
</template>

<script>
import axios from 'axios';
import mitt from '@/plugins/mitt';
import VueApexCharts from 'vue3-apexcharts';
import { useMain } from '@/stores/Main';
import { useFishProcessType } from '@/stores/FishProcessType';
import { useFishFamily } from '@/stores/FishFamily';
import { useFishType } from '@/stores/FishType';

export default {
  name: 'FishManagement',
  components: { apexchart: VueApexCharts },
  data() {
    return {
      activeTab: 'families',
      families: [],
      loadingFamilies: false,
      familyDialog: false,
      editingFamily: null,
      familyForm: { name_fr: '', name_en: '', name_es: '' },
      familyColumnFilters: {},
      fishTypes: [],
      loadingTypes: false,
      typeDialog: false,
      editingType: null,
      typeForm: {
        name_fr: '', name_en: '', name_es: '', name_in_invoice: '',
        scientific_name: '', image: '', description: '', process: '', type: null,
        fish_family_id: null, fish_process_type_id: null,
        productivity_color: '#1E88E5',
      },
      typeColumnFilters: {},
      processTypesList: [],
      loadingProcessTypes: false,
      processDialog: false,
      editingProcess: null,
      processForm: { name: '', description: '' },
      processColumnFilters: {},
      processTypeSearch: '',
      creatingProcessType: false,
      processTypeCreatedMsg: '',
      confirmDeleteDialog: false,
      deleteTarget: null,
      deleteEntityType: null,
      saving: false,
      // WAP Configuration state
      wapDialog: false,
      wapChartModalDialog: false,
      selectedFishTypeForWap: null,
      loadingWaps: false,
      wapsList: [],
      warehousesList: [],
      editingWapRule: null,
      wapForm: { warehouse_id: null, wap: null, is_indefinite: false },
      wapSingleDateVC: new Date(),
      wapDateRangeVC: { start: new Date(), end: new Date() },
      savingWapRule: false,
      snackbar: { show: false, text: '', color: 'success' },
    };
  },
  computed: {
    main() { return useMain(); },
    fishProcessTypeStore() { return useFishProcessType(); },
    fishFamilyStore() { return useFishFamily(); },
    fishTypeStore() { return useFishType(); },
    processTypes() { return this.fishProcessTypeStore.getList; },
    fishTypeOptions() {
      return [
        { value: 'fresh', label: this.$t('fresh') },
        { value: 'frozen', label: this.$t('frozen') },
      ];
    },
    familyHeaders() {
      return [
        { title: this.$t('name_fr'), key: 'name_fr', align: 'center' },
        { title: this.$t('name_en'), key: 'name_en', align: 'center' },
        { title: this.$t('name_es'), key: 'name_es', align: 'center' },
        { title: this.$t('fish_types_count'), key: 'fish_types_count', align: 'center' },
        { title: this.$t('actions'), key: 'actions', align: 'center', sortable: false },
      ];
    },
    typeHeaders() {
      return [
        { title: this.$t('name_fr'), key: 'name_fr', align: 'center' },
        { title: this.$t('name_in_invoice'), key: 'name_in_invoice', align: 'center' },
        { title: this.$t('fish_category_type'), key: 'type', align: 'center' },
        { title: this.$t('fish_families'), key: 'fish_family', align: 'center' },
        { title: this.$t('image'), key: 'image', align: 'center' },
        { title: this.$t('productivity_type'), key: 'fish_process_type', align: 'center' },
        { title: this.$t('productivity_color'), key: 'productivity_color', align: 'center' },
        { title: this.$t('actions'), key: 'actions', align: 'center', sortable: false },
      ];
    },
    processHeaders() {
      return [
        { title: this.$t('name'), key: 'name', align: 'center' },
        { title: this.$t('description'), key: 'description', align: 'center' },
        { title: this.$t('fish_types_count'), key: 'fish_types_count', align: 'center' },
        { title: this.$t('actions'), key: 'actions', align: 'center', sortable: false },
      ];
    },
    wapHeaders() {
      return [
        { title: this.$t('warehouse'), key: 'warehouse', align: 'center' },
        { title: this.$t('date_start'), key: 'date_start', align: 'center' },
        { title: this.$t('date_end'), key: 'date_end', align: 'center' },
        { title: this.$t('wap'), key: 'wap', align: 'center' },
        { title: this.$t('actions'), key: 'actions', align: 'center', sortable: false },
      ];
    },
    wapStartDateDisplay() {
      if (this.wapForm.is_indefinite) {
        if (!this.wapDateRangeVC) return '-';
        const d = this.wapDateRangeVC.start || this.wapDateRangeVC;
        return d ? (typeof d === 'string' ? d.substring(0, 10) : new Date(d).toISOString().substring(0, 10)) : '-';
      } else {
        if (!this.wapSingleDateVC) return '-';
        const d = this.wapSingleDateVC;
        return d ? (typeof d === 'string' ? d.substring(0, 10) : new Date(d).toISOString().substring(0, 10)) : '-';
      }
    },
    wapEndDateDisplay() {
      if (!this.wapForm.is_indefinite) return this.$t('indefinite');
      if (!this.wapDateRangeVC || !this.wapDateRangeVC.end) return '-';
      const d = this.wapDateRangeVC.end;
      return d ? (typeof d === 'string' ? d.substring(0, 10) : new Date(d).toISOString().substring(0, 10)) : '-';
    },
    wapStats() {
      if (!this.wapsList.length) return { min: 0, max: 0, avg: 0 };
      const vals = this.wapsList.map(w => Number(w.wap || 0)).filter(v => v > 0);
      if (!vals.length) return { min: 0, max: 0, avg: 0 };
      const min = Math.min(...vals);
      const max = Math.max(...vals);
      const avg = vals.reduce((a, b) => a + b, 0) / vals.length;
      return {
        min: Math.round(min * 100) / 100,
        max: Math.round(max * 100) / 100,
        avg: Math.round(avg * 100) / 100,
      };
    },
    wapChartSeries() {
      if (!this.wapsList.length) return [];
      const warehouseGroups = {};
      this.wapsList.forEach(item => {
        const whName = item.warehouse ? item.warehouse.name : (this.$t('warehouse') + ' #' + item.warehouse_id);
        if (!warehouseGroups[whName]) warehouseGroups[whName] = [];
        
        const price = Number(item.wap || 0);
        const startDate = item.date_start ? item.date_start.substring(0, 10) : '';
        if (startDate) {
          warehouseGroups[whName].push({ x: startDate, y: price });
        }
        if (item.date_end) {
          const endDate = item.date_end.substring(0, 10);
          warehouseGroups[whName].push({ x: endDate, y: price });
        }
      });

      return Object.entries(warehouseGroups).map(([whName, points]) => {
        points.sort((a, b) => new Date(a.x) - new Date(b.x));
        return {
          name: whName,
          data: points,
        };
      });
    },
    wapChartOptions() {
      return {
        chart: {
          type: 'line',
          toolbar: { show: true },
          zoom: { enabled: true },
        },
        stroke: {
          width: 3,
          curve: 'stepline',
        },
        markers: {
          size: 6,
          hover: { size: 8 },
        },
        colors: ['#9342F5', '#1565C0', '#26A69A', '#FF9800', '#EF5350', '#AB47BC'],
        xaxis: {
          type: 'category',
          title: { text: this.$t('date'), style: { fontSize: '11px', fontWeight: 600 } },
          labels: { rotate: -45, style: { fontSize: '10px' } },
        },
        yaxis: {
          title: { text: 'WAP (MAD/Kg)', style: { fontSize: '11px', fontWeight: 600 } },
          labels: { formatter: val => this.main.formatDisplayNumber(val) + ' MAD/Kg' },
        },
        tooltip: {
          shared: true,
          y: { formatter: val => this.main.formatDisplayNumber(val) + ' MAD/Kg' },
        },
        legend: { position: 'top', fontSize: '12px' },
      };
    },
    filteredFamilies() {
      return this.families.filter(item => Object.entries(this.familyColumnFilters).every(([key, sel]) => {
        if (!sel || !sel.length) return true;
        return sel.includes(String(item[key] ?? ''));
      }));
    },
    filteredTypes() {
      return this.fishTypes.filter(item => Object.entries(this.typeColumnFilters).every(([key, sel]) => {
        if (!sel || !sel.length) return true;
        return sel.includes(this.getTypeCellValue(key, item));
      }));
    },
    filteredProcessTypes() {
      return this.processTypesList.filter(item => Object.entries(this.processColumnFilters).every(([key, sel]) => {
        if (!sel || !sel.length) return true;
        return sel.includes(String(item[key] ?? ''));
      }));
    },
    hasActiveFamilyFilters() { return Object.values(this.familyColumnFilters).some(f => f && f.length > 0); },
    hasActiveTypeFilters() { return Object.values(this.typeColumnFilters).some(f => f && f.length > 0); },
    hasActiveProcessFilters() { return Object.values(this.processColumnFilters).some(f => f && f.length > 0); },
  },
  mounted() {
    this.fetchFamilies();
    this.fetchTypes();
    this.fetchProcessTypes();
    mitt.on('model-changed', this._onModelChanged);
  },
  beforeUnmount() { mitt.off('model-changed', this._onModelChanged); },
  methods: {
    familyTitle(item) {
      return item['name_' + this.main.lang.short] || item.name_en;
    },
    getTypeCellValue(key, item) {
      if (key === 'type') return item.type ? this.$t(item.type) : '-';
      if (key === 'fish_family') return item.fish_family ? (item.fish_family['name_' + this.main.lang.short] || item.fish_family.name_en) : '-';
      if (key === 'fish_process_type') return item.fish_process_type ? item.fish_process_type.name : '-';
      return String(item[key] ?? '');
    },
    getFamilyColumnOptions(key) { const set = new Set(); this.families.forEach(i => set.add(String(i[key] ?? ''))); return [...set].sort(); },
    getTypeColumnOptions(key) { const set = new Set(); this.fishTypes.forEach(i => set.add(this.getTypeCellValue(key, i))); return [...set].sort(); },
    getProcessColumnOptions(key) { const set = new Set(); this.processTypesList.forEach(i => set.add(String(i[key] ?? ''))); return [...set].sort(); },
    _onModelChanged(data) {
      if (data.model === 'FishFamily') this.fetchFamilies();
      if (data.model === 'FishType') { this.fetchTypes(); this.fetchFamilies(); }
      if (data.model === 'FishProcessType') this.fetchProcessTypes();
      if (data.model === 'FishTypeWap' && this.selectedFishTypeForWap) { this.fetchWapRules(this.selectedFishTypeForWap.id); }
    },

    // -- Fish Families --
    async fetchFamilies() {
      this.loadingFamilies = true;
      try {
        const res = await axios.get('/api/fish-families');
        this.families = res.data.fish_families || [];
      } catch (e) { console.error(e); }
      this.loadingFamilies = false;
    },
    openFamilyDialog(item) {
      this.editingFamily = item;
      this.familyForm = item
        ? { name_fr: item.name_fr, name_en: item.name_en, name_es: item.name_es }
        : { name_fr: '', name_en: '', name_es: '' };
      this.familyDialog = true;
    },
    async saveFamily() {
      this.saving = true;
      try {
        if (this.editingFamily) {
          await axios.put(`/api/fish-families/${this.editingFamily.id}`, this.familyForm);
        } else {
          await axios.post('/api/fish-families', this.familyForm);
        }
        this.familyDialog = false;
        this.fetchFamilies();
      } catch (e) { console.error(e); }
      this.saving = false;
    },
    deleteFamily(item) {
      this.deleteTarget = item;
      this.deleteEntityType = 'family';
      this.confirmDeleteDialog = true;
    },

    // -- Fish Types --
    async fetchTypes() {
      this.loadingTypes = true;
      try {
        const res = await axios.get('/api/fish-types');
        this.fishTypes = res.data.fish_types || [];
      } catch (e) { console.error(e); }
      this.loadingTypes = false;
    },
    openTypeDialog(item) {
      this.editingType = item;
      this.processTypeCreatedMsg = '';
      this.processTypeSearch = '';
      if (item) {
        this.typeForm = {
          name_fr: item.name_fr, name_en: item.name_en, name_es: item.name_es,
          name_in_invoice: item.name_in_invoice,
          scientific_name: item.scientific_name || '',
          image: item.image || '',
          description: item.description || '',
          process: item.process || '',
          type: item.type || null,
          fish_family_id: item.fish_family_id,
          fish_process_type_id: item.fish_process_type_id,
          productivity_color: item.productivity_color || '#1E88E5',
        };
      } else {
        this.typeForm = {
          name_fr: '', name_en: '', name_es: '', name_in_invoice: '',
          scientific_name: '', image: '', description: '', process: '', type: null,
          fish_family_id: null, fish_process_type_id: null,
          productivity_color: '#1E88E5',
        };
      }
      this.typeDialog = true;
    },
    async saveType() {
      this.saving = true;
      try {
        if (this.editingType) {
          await axios.put(`/api/fish-types/${this.editingType.id}`, this.typeForm);
        } else {
          await axios.post('/api/fish-types', this.typeForm);
        }
        this.typeDialog = false;
        this.fetchTypes();
        this.fetchFamilies(); // refresh counts
      } catch (e) { console.error(e); }
      this.saving = false;
    },
    deleteType(item) {
      this.deleteTarget = item;
      this.deleteEntityType = 'type';
      this.confirmDeleteDialog = true;
    },

    // -- Process Types --
    async fetchProcessTypes() {
      this.loadingProcessTypes = true;
      try {
        const res = await axios.get('/api/fish-process-types');
        this.processTypesList = res.data.fish_process_types || [];
        // Also update the store for the autocomplete
        this.fishProcessTypeStore.list = this.processTypesList;
      } catch (e) { console.error(e); }
      this.loadingProcessTypes = false;
    },
    openProcessDialog(item) {
      this.editingProcess = item;
      this.processForm = item
        ? { name: item.name, description: item.description || '' }
        : { name: '', description: '' };
      this.processDialog = true;
    },
    async saveProcess() {
      this.saving = true;
      try {
        if (this.editingProcess) {
          await axios.put(`/api/fish-process-types/${this.editingProcess.id}`, this.processForm);
        } else {
          await axios.post('/api/fish-process-types', this.processForm);
        }
        this.processDialog = false;
        this.fetchProcessTypes();
      } catch (e) { console.error(e); }
      this.saving = false;
    },
    deleteProcess(item) {
      this.deleteTarget = item;
      this.deleteEntityType = 'process';
      this.confirmDeleteDialog = true;
    },

    // -- Process Type Create-on-Enter --
    async onProcessTypeEnter() {
      const name = (this.processTypeSearch || '').trim();
      if (!name) return;
      // Check if already exists
      const existing = this.processTypes.find(pt => pt.name.toLowerCase() === name.toLowerCase());
      if (existing) {
        this.typeForm.fish_process_type_id = existing.id;
        this.processTypeSearch = '';
        return;
      }
      // Create new
      this.creatingProcessType = true;
      try {
        const res = await axios.post('/api/fish-process-types', { name, description: null });
        const created = res.data.fish_process_type;
        this.fishProcessTypeStore.addFishProcessType(created);
        this.typeForm.fish_process_type_id = created.id;
        this.processTypeCreatedMsg = `${this.$t('process_type_created')}: ${created.name}`;
        this.processTypeSearch = '';
        // Auto-clear message after 3s
        setTimeout(() => { this.processTypeCreatedMsg = ''; }, 3000);
      } catch (e) { console.error(e); }
      this.creatingProcessType = false;
    },

    // -- Delete --
    async executeDelete() {
      this.confirmDeleteDialog = false;
      const item = this.deleteTarget;
      if (!item) return;
      try {
        if (this.deleteEntityType === 'family') {
          await axios.delete(`/api/fish-families/${item.id}`);
          this.fetchFamilies();
        } else if (this.deleteEntityType === 'type') {
          await axios.delete(`/api/fish-types/${item.id}`);
          this.fetchTypes();
          this.fetchFamilies();
        } else if (this.deleteEntityType === 'process') {
          await axios.delete(`/api/fish-process-types/${item.id}`);
          this.fetchProcessTypes();
        }
      } catch (e) { console.error(e); }
      this.deleteTarget = null;
      this.deleteEntityType = null;
    },

    // -- WAP Configuration Methods --
    async fetchWarehouses() {
      try {
        const res = await axios.get('/api/warehouses');
        this.warehousesList = res.data.warehouses || [];
      } catch (e) { console.error(e); }
    },
    async openWapDialog(item) {
      this.selectedFishTypeForWap = item;
      this.wapDialog = true;
      this.resetWapForm();
      if (!this.warehousesList.length) {
        await this.fetchWarehouses();
      }
      this.fetchWapRules(item.id);
    },
    closeWapDialog() {
      this.wapDialog = false;
      this.selectedFishTypeForWap = null;
      this.wapsList = [];
      this.resetWapForm();
    },
    async fetchWapRules(fishTypeId) {
      this.loadingWaps = true;
      try {
        const res = await axios.get(`/api/fish-types/${fishTypeId}/waps`);
        this.wapsList = res.data.waps || [];
      } catch (e) { console.error(e); }
      this.loadingWaps = false;
    },
    resetWapForm() {
      this.editingWapRule = null;
      this.wapForm = { warehouse_id: null, wap: null, is_indefinite: false };
      const today = new Date();
      this.wapSingleDateVC = today;
      this.wapDateRangeVC = { start: today, end: today };
    },
    editWapRule(item) {
      this.editingWapRule = item;
      const start = item.date_start ? new Date(item.date_start) : new Date();
      const end = item.date_end ? new Date(item.date_end) : new Date();
      this.wapSingleDateVC = start;
      this.wapDateRangeVC = { start, end };
      this.wapForm = {
        warehouse_id: item.warehouse_id,
        wap: item.wap,
        is_indefinite: !!item.date_end,
      };
    },
    async saveWapRule() {
      if (!this.selectedFishTypeForWap || !this.wapForm.warehouse_id || !this.wapForm.wap) {
        this.showSnackbar(this.$t('please_fill_all_required_fields'), 'error');
        return;
      }
      this.savingWapRule = true;
      try {
        const startStr = this.wapStartDateDisplay;
        let endStr = null;
        if (this.wapForm.is_indefinite && this.wapDateRangeVC && this.wapDateRangeVC.end) {
          const d = this.wapDateRangeVC.end;
          const formatted = typeof d === 'string' ? d.substring(0, 10) : new Date(d).toISOString().substring(0, 10);
          if (formatted && formatted !== startStr) {
            endStr = formatted;
          }
        }

        const payload = {
          warehouse_id: this.wapForm.warehouse_id,
          date_start: startStr,
          date_end: endStr,
          wap: this.wapForm.wap,
        };

        if (this.editingWapRule) {
          await axios.put(`/api/fish-type-waps/${this.editingWapRule.id}`, payload);
          this.showSnackbar(this.$t('wap_rule_saved'), 'success');
        } else {
          await axios.post(`/api/fish-types/${this.selectedFishTypeForWap.id}/waps`, payload);
          this.showSnackbar(this.$t('wap_rule_saved'), 'success');
        }
        this.resetWapForm();
        this.fetchWapRules(this.selectedFishTypeForWap.id);
      } catch (e) {
        console.error(e);
        this.showSnackbar(this.$t('an_error_occurred'), 'error');
      }
      this.savingWapRule = false;
    },
    async deleteWapRule(item) {
      try {
        await axios.delete(`/api/fish-type-waps/${item.id}`);
        this.showSnackbar(this.$t('wap_rule_deleted'), 'success');
        if (this.selectedFishTypeForWap) {
          this.fetchWapRules(this.selectedFishTypeForWap.id);
        }
      } catch (e) {
        console.error(e);
        this.showSnackbar(this.$t('an_error_occurred'), 'error');
      }
    },
    showSnackbar(text, color = 'success') {
      this.snackbar = { show: true, text, color };
    },
  },
};
</script>

<style scoped>
.fm-card { border-radius: 20px !important; overflow: visible; position: relative; font-family: 'Inter', sans-serif; }
.fm-hero { background: linear-gradient(135deg, #1565C0 0%, #1E88E5 40%, #42A5F5 100%); border-radius: inherit; border-bottom-left-radius: 0; border-bottom-right-radius: 0; height: 100px; }
.fm-hero-inner { position: relative; z-index: 1; padding: 22px 26px; color: #fff; }
.fm-hero-icon { width: 52px; height: 52px; border-radius: 14px; background: rgba(255,255,255,.18); backdrop-filter: blur(6px); display: flex; align-items: center; justify-content: center; border: 1px solid rgba(255,255,255,.15); }
.fm-hero-title { font-size: 19px; font-weight: 800; letter-spacing: .3px; }
.fm-color-dot { width: 18px; height: 18px; border-radius: 50%; border: 2px solid rgba(0,0,0,.12); display: inline-block; flex-shrink: 0; }
.fm-color-btn { text-transform: none; font-weight: 500; letter-spacing: 0; min-height: 40px; }
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
</style>
