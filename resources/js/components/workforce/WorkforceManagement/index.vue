<template>
  <div class="wfm-page-wrap">
    <v-card class="wfm-card border" elevation="0">
      <!-- ================= HERO HEADER ================= -->
      <div class="wfm-hero">
        <div class="wfm-hero-inner">
          <div class="d-flex flex-wrap align-center justify-space-between ga-4">
            <div class="d-flex align-center ga-3">
              <div class="wfm-hero-icon">
                <v-icon size="28" color="white">mdi-account-multiple</v-icon>
              </div>
              <div>
                <div class="wfm-hero-title">{{ $t('workforce_management') }}</div>
                <div class="text-white text-caption" style="opacity: .85;">{{ $t('manage_workforces_desc') }}</div>
              </div>
            </div>

            <!-- Hero KPI Chips -->
            <div class="d-flex flex-wrap align-center ga-2">
              <div class="wfm-kpi-chip">
                <v-icon size="16" color="#90CAF9" class="mr-1">mdi-account-group</v-icon>
                <span class="wfm-kpi-num fnm-currency-num">{{ items.length }}</span>
                <span class="wfm-kpi-lbl">{{ $t('total') }}</span>
              </div>
              <div class="wfm-kpi-chip">
                <v-icon size="16" color="#A5D6A7" class="mr-1">mdi-file-document-check-outline</v-icon>
                <span class="wfm-kpi-num fnm-currency-num">{{ declaredCount }}</span>
                <span class="wfm-kpi-lbl">{{ $t('declared') }}</span>
              </div>
              <div class="wfm-kpi-chip">
                <v-icon size="16" color="#FFE082" class="mr-1">mdi-cash-lock</v-icon>
                <span class="wfm-kpi-num fnm-currency-num">{{ fixedPayCount }}</span>
                <span class="wfm-kpi-lbl">{{ $t('fixed_pay') }}</span>
              </div>
              <div class="wfm-kpi-chip">
                <v-icon size="16" color="#FFCC80" class="mr-1">mdi-earth</v-icon>
                <span class="wfm-kpi-num fnm-currency-num">{{ migrantCount }}</span>
                <span class="wfm-kpi-lbl">{{ $t('migrant') }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ================= TOOLBAR & ACTIONS ================= -->
      <v-card-text class="pa-4 wfm-card-body">
        <div class="d-flex flex-wrap align-center justify-space-between ga-3 mb-3">
          <!-- Left: Search & Quick Filter Pills -->
          <div class="d-flex flex-wrap align-center ga-2 flex-grow-1" style="max-width: 680px;">
            <v-text-field
              v-model="quickSearch"
              :placeholder="$t('search_workforces') || 'Search workforces by name, matricule, CNI...'"
              prepend-inner-icon="mdi-magnify"
              variant="outlined"
              density="compact"
              rounded
              color="#42A5F5"
              class="wfm-search-input text-workforce"
              hide-details
              clearable
              style="min-width: 260px;"
            ></v-text-field>

            <v-btn-toggle
              v-model="quickFilter"
              density="compact"
              rounded
              mandatory
              variant="outlined"
              color="#42A5F5"
              class="wfm-filter-toggle"
            >
              <v-btn value="all" size="small">{{ $t('all') }} ({{ items.length }})</v-btn>
              <v-btn value="fixed" size="small">{{ $t('fixed_pay') }} ({{ fixedPayCount }})</v-btn>
              <v-btn value="declared" size="small">{{ $t('declared') }} ({{ declaredCount }})</v-btn>
              <v-btn value="migrant" size="small">{{ $t('migrant') }} ({{ migrantCount }})</v-btn>
            </v-btn-toggle>
          </div>

          <!-- Right: Actions -->
          <div class="d-flex align-center ga-2">
            <v-btn
              v-if="hasActiveColumnFilters || quickSearch"
              variant="tonal"
              color="warning"
              rounded
              size="small"
              prepend-icon="mdi-filter-off"
              @click="clearAllFilters"
            >
              {{ $t('clear_filters') }}
            </v-btn>
            <v-btn
              v-if="selectedIds.length > 0"
              color="error"
              prepend-icon="mdi-delete-sweep"
              rounded
              size="small"
              variant="flat"
              @click="bulkDeleteDialog = true"
            >
              {{ $t('delete') }} ({{ selectedIds.length }})
            </v-btn>
            <v-btn
              color="#42A5F5"
              prepend-icon="mdi-account-plus"
              rounded
              size="small"
              variant="flat"
              class="text-white font-weight-bold shadow-sm"
              @click="openDialog(null)"
            >
              {{ $t('add_workforce') }}
            </v-btn>
          </div>
        </div>

        <!-- ================= RESPONSIVE DATA TABLE ================= -->
        <div class="wfm-table-wrap">
          <v-data-table-virtual
            :headers="headers"
            :items="filteredItems"
            :loading="loading ? '#42A5F5' : false"
            density="compact"
            class="rounded-xl wfm-table border"
            hover
            show-select
            v-model="selectedIds"
            item-value="id"
            :return-object="false"
            fixed-header
            :height="tableHeight"
          >
            <!-- Custom headers with integrated column filters -->
            <template v-slot:headers="{ columns, isSorted, getSortIcon, toggleSort }">
              <tr>
                <th v-for="col in columns" :key="col.key" class="wfm-th">
                  <template v-if="col.key === 'data-table-select'">
                    <v-checkbox-btn
                      :model-value="selectAll"
                      :indeterminate="someSelected && !selectAll"
                      color="#42A5F5"
                      density="compact"
                      hide-details
                      @update:model-value="toggleSelectAll"
                    ></v-checkbox-btn>
                  </template>
                  <div
                    v-else
                    class="wfm-th-label"
                    @click="col.sortable !== false ? toggleSort(col) : null"
                    :style="{ cursor: col.sortable !== false ? 'pointer' : 'default' }"
                  >
                    {{ col.title }}
                    <v-icon v-if="isSorted(col)" size="14" class="ms-1">{{ getSortIcon(col) }}</v-icon>
                  </div>
                </th>
              </tr>
              <tr class="wfm-filter-row">
                <th v-for="col in columns" :key="col.key + '-filter'" class="wfm-filter-th">
                  <v-autocomplete
                    v-if="col.key !== 'actions' && col.key !== 'data-table-select'"
                    v-model="columnFilters[col.key]"
                    :items="getColumnOptions(col.key)"
                    density="compact"
                    variant="outlined"
                    rounded
                    color="#42A5F5"
                    class="wfm-col-filter text-workforce"
                    hide-details
                    clearable
                    multiple
                    chips
                    closable-chips
                    :placeholder="$t('filter')"
                  >
                    <template #chip="{ item, props }">
                      <v-chip v-bind="props" size="x-small" color="#42A5F5" variant="tonal" closable>{{ item.title }}</v-chip>
                    </template>
                  </v-autocomplete>
                </th>
              </tr>
            </template>

            <!-- Worker Identity (Avatar + Name + Matricule) -->
            <template v-slot:[`item.full_name`]="{ item }">
              <div class="d-flex align-center ga-2 py-1">
                <div class="wfm-avatar-circle">
                  {{ getInitials(item.full_name) }}
                </div>
                <div>
                  <div class="font-weight-bold text-body-2 text-truncate" style="max-width: 220px;">
                    {{ item.full_name }}
                  </div>
                  <div class="d-flex align-center ga-1 mt-0">
                    <span class="text-caption font-weight-medium text-medium-emphasis fnm-currency-num">#{{ item.matricule }}</span>
                    <span v-if="item.nationality" class="text-caption text-disabled">• {{ item.nationality }}</span>
                  </div>
                </div>
              </div>
            </template>

            <!-- Matricule -->
            <template v-slot:[`item.matricule`]="{ item }">
              <v-chip size="x-small" variant="tonal" color="#1565C0" class="font-weight-bold fnm-currency-num">
                {{ item.matricule }}
              </v-chip>
            </template>

            <!-- Nationality -->
            <template v-slot:[`item.nationality`]="{ item }">
              <div class="d-flex align-center justify-center ga-1">
                <v-icon size="14" color="grey">mdi-flag-outline</v-icon>
                <span class="text-caption">{{ item.nationality || '-' }}</span>
              </div>
            </template>

            <!-- Sex -->
            <template v-slot:[`item.sex`]="{ item }">
              <v-chip :color="item.sex === 'male' ? 'blue' : 'pink'" size="x-small" variant="tonal" class="font-weight-medium">
                <v-icon size="12" class="mr-1">{{ item.sex === 'male' ? 'mdi-gender-male' : 'mdi-gender-female' }}</v-icon>
                {{ $t(item.sex) }}
              </v-chip>
            </template>

            <!-- CNI & CNSS -->
            <template v-slot:[`item.cni`]="{ item }">
              <div class="text-caption font-mono">{{ item.cni || '-' }}</div>
            </template>
            <template v-slot:[`item.cnss_id`]="{ item }">
              <div class="text-caption font-mono">{{ item.cnss_id || '-' }}</div>
            </template>

            <!-- Status badges: Migrant -->
            <template v-slot:[`item.is_migrant`]="{ item }">
              <v-chip
                size="x-small"
                :color="item.is_migrant ? 'warning' : 'grey'"
                :variant="item.is_migrant ? 'flat' : 'outlined'"
                class="font-weight-medium"
              >
                <v-icon size="12" class="mr-1">{{ item.is_migrant ? 'mdi-earth' : 'mdi-home-outline' }}</v-icon>
                {{ item.is_migrant ? $t('migrant') : $t('local') }}
              </v-chip>
            </template>

            <!-- Status badges: Salary Statement / Declared -->
            <template v-slot:[`item.salary_statement`]="{ item }">
              <v-chip
                size="x-small"
                :color="item.salary_statement ? 'success' : 'grey'"
                :variant="item.salary_statement ? 'flat' : 'outlined'"
                class="font-weight-medium"
              >
                <v-icon size="12" class="mr-1">{{ item.salary_statement ? 'mdi-check-circle' : 'mdi-close-circle-outline' }}</v-icon>
                {{ item.salary_statement ? $t('declared') : $t('non_declared') }}
              </v-chip>
            </template>

            <!-- Status badges: Fixed Daily Pay -->
            <template v-slot:[`item.fixed_daily_pay`]="{ item }">
              <v-chip
                size="x-small"
                :color="item.fixed_daily_pay ? '#42A5F5' : 'grey'"
                :variant="item.fixed_daily_pay ? 'flat' : 'outlined'"
                class="font-weight-medium"
              >
                <v-icon size="12" class="mr-1">{{ item.fixed_daily_pay ? 'mdi-cash-lock' : 'mdi-cash' }}</v-icon>
                {{ item.fixed_daily_pay ? $t('fixed_pay') : $t('standard_pay') }}
              </v-chip>
            </template>

            <!-- Daily Price -->
            <template v-slot:[`item.daily_price`]="{ item }">
              <v-chip v-if="item.fixed_daily_pay" size="small" color="success" variant="tonal" class="font-weight-bold fnm-currency-num">
                {{ formatNumber(item.daily_price) }} {{ main.currency || 'DH' }}
              </v-chip>
              <span v-else class="text-grey text-caption">—</span>
            </template>

            <!-- Actions -->
            <template v-slot:[`item.actions`]="{ item }">
              <div class="d-flex align-center justify-center ga-1">
                <v-btn icon size="x-small" variant="tonal" color="#42A5F5" @click="openDialog(item)" :title="$t('edit')">
                  <v-icon size="15">mdi-pencil</v-icon>
                </v-btn>
                <v-btn icon size="x-small" variant="tonal" color="error" @click="confirmDelete(item)" :title="$t('delete')">
                  <v-icon size="15">mdi-delete</v-icon>
                </v-btn>
              </div>
            </template>
          </v-data-table-virtual>
        </div>
      </v-card-text>
    </v-card>

    <!-- ============= CREATE / EDIT WORKFORCE DIALOG ============= -->
    <v-dialog v-model="dialog" max-width="720" persistent scrollable>
      <v-card class="rounded-2xl overflow-hidden elevation-12">
        <!-- Dialog Header Banner -->
        <div class="wfm-dialog-hero">
          <div class="d-flex align-center justify-space-between w-100">
            <div class="d-flex align-center ga-3">
              <div class="wfm-dialog-icon">
                <v-icon size="24" color="white">{{ editing ? 'mdi-account-edit' : 'mdi-account-plus' }}</v-icon>
              </div>
              <div>
                <div class="text-subtitle-1 font-weight-bold text-white">
                  {{ editing ? $t('edit_workforce') : $t('add_workforce') }}
                </div>
                <div class="text-caption text-white" style="opacity: .8;">
                  {{ editing ? form.full_name : $t('new_workforce_member_desc') }}
                </div>
              </div>
            </div>
            <v-btn icon size="small" variant="text" color="white" @click="dialog = false">
              <v-icon size="20">mdi-close</v-icon>
            </v-btn>
          </div>
        </div>

        <v-card-text class="pa-5" style="max-height: 72vh;">
          <!-- Live Preview Card -->
          <div class="wfm-preview-card mb-4 pa-3 rounded-xl border">
            <div class="d-flex align-center ga-3">
              <div class="wfm-avatar-circle" style="width: 44px; height: 44px; font-size: 15px;">
                {{ getInitials(form.full_name) }}
              </div>
              <div class="flex-grow-1 min-w-0">
                <div class="d-flex align-center ga-2">
                  <span class="font-weight-bold text-body-1 text-truncate">
                    {{ form.full_name || $t('full_name') }}
                  </span>
                  <v-chip size="x-small" color="#1565C0" variant="tonal" class="font-weight-bold">
                    #{{ form.matricule || '----' }}
                  </v-chip>
                </div>
                <div class="d-flex flex-wrap align-center ga-2 mt-1">
                  <span class="text-caption text-medium-emphasis">
                    <v-icon size="12" class="mr-1">mdi-flag</v-icon>
                    {{ form.nationality || $t('nationality') }}
                  </span>
                  <span class="text-caption text-disabled">•</span>
                  <span class="text-caption text-medium-emphasis">
                    {{ $t(form.sex) }}
                  </span>
                  <v-chip v-if="form.fixed_daily_pay" size="x-small" color="success" variant="flat" class="text-white font-weight-bold">
                    {{ formatNumber(form.daily_price) }} {{ main.currency || 'DH' }}/day
                  </v-chip>
                  <v-chip v-if="form.salary_statement" size="x-small" color="success" variant="tonal">
                    {{ $t('declared') }}
                  </v-chip>
                  <v-chip v-if="form.is_migrant" size="x-small" color="warning" variant="tonal">
                    {{ $t('migrant') }}
                  </v-chip>
                </div>
              </div>
            </div>
          </div>

          <!-- Form Fields -->
          <v-row dense>
            <!-- Group 1: Personal Identification -->
            <v-col cols="12">
              <div class="text-caption font-weight-bold text-medium-emphasis text-uppercase mb-2">
                <v-icon size="14" color="#42A5F5" class="mr-1">mdi-badge-account-outline</v-icon>
                {{ $t('identification') }}
              </div>
            </v-col>

            <v-col cols="12" sm="7">
              <v-text-field
                v-model="form.full_name"
                :label="$t('full_name')"
                variant="outlined"
                density="compact"
                rounded
                color="#42A5F5"
                class="text-workforce"
                prepend-inner-icon="mdi-account"
                :rules="[v => !!v || $t('is_required', { type: $t('full_name') })]"
                @keyup.enter="save"
              ></v-text-field>
            </v-col>

            <v-col cols="12" sm="5">
              <v-text-field
                v-model="form.matricule"
                :label="$t('matricule')"
                variant="outlined"
                density="compact"
                rounded
                color="#42A5F5"
                class="text-workforce"
                placeholder="ex: 4021"
                prepend-inner-icon="mdi-badge-account-horizontal-outline"
                :rules="[v => !!v || $t('is_required', { type: $t('matricule') })]"
                @keyup.enter="save"
              ></v-text-field>
            </v-col>

            <v-col cols="12" sm="4">
              <v-combobox
                v-model="form.nationality"
                :items="nationalityPresets"
                :label="$t('nationality')"
                variant="outlined"
                density="compact"
                rounded
                color="#42A5F5"
                class="text-workforce"
                prepend-inner-icon="mdi-flag-outline"
                clearable
                @keyup.enter="save"
              ></v-combobox>
            </v-col>

            <v-col cols="12" sm="4">
              <v-select
                v-model="form.sex"
                :items="sexOptions"
                :label="$t('sex')"
                variant="outlined"
                density="compact"
                rounded
                color="#42A5F5"
                class="text-workforce"
                prepend-inner-icon="mdi-gender-male-female"
              ></v-select>
            </v-col>

            <v-col cols="12" sm="4">
              <v-text-field
                v-model="form.cni"
                :label="$t('cni')"
                variant="outlined"
                density="compact"
                rounded
                color="#42A5F5"
                class="text-workforce"
                placeholder="ex: AB123456"
                prepend-inner-icon="mdi-card-account-details-outline"
                @keyup.enter="save"
              ></v-text-field>
            </v-col>

            <v-col cols="12">
              <v-text-field
                v-model="form.cnss_id"
                :label="$t('cnss_id')"
                variant="outlined"
                density="compact"
                rounded
                color="#42A5F5"
                class="text-workforce"
                placeholder="ex: 123456789"
                prepend-inner-icon="mdi-shield-account-outline"
                @keyup.enter="save"
              ></v-text-field>
            </v-col>

            <!-- Group 2: Status & Compensation -->
            <v-col cols="12" class="mt-2">
              <div class="text-caption font-weight-bold text-medium-emphasis text-uppercase mb-2">
                <v-icon size="14" color="#42A5F5" class="mr-1">mdi-cash-cog</v-icon>
                {{ $t('compensation_and_status') }}
              </div>
            </v-col>

            <!-- Fixed Daily Pay Card -->
            <v-col cols="12">
              <div
                class="wfm-toggle-card mb-2"
                :class="{ 'wfm-toggle-active-blue': form.fixed_daily_pay }"
                @click="form.fixed_daily_pay = !form.fixed_daily_pay"
              >
                <div class="wfm-toggle-left">
                  <div class="wfm-toggle-icon-wrap" :style="{ background: form.fixed_daily_pay ? '#1565C01A' : 'rgba(var(--v-border-color), .06)' }">
                    <v-icon :color="form.fixed_daily_pay ? '#1565C0' : 'grey'" size="20">mdi-cash-lock</v-icon>
                  </div>
                  <div>
                    <div class="wfm-toggle-label">{{ $t('fixed_daily_pay') }}</div>
                    <div class="text-caption text-medium-emphasis" style="font-size: 11px !important;">
                      {{ $t('fixed_daily_price_active_desc') }}
                    </div>
                  </div>
                </div>
                <v-switch v-model="form.fixed_daily_pay" color="#42A5F5" inset density="compact" hide-details @click.stop></v-switch>
              </div>

              <!-- Animated Daily Price Input -->
              <v-expand-transition>
                <div v-if="form.fixed_daily_pay" class="pa-3 rounded-xl bg-blue-lighten-5 mb-2 border">
                  <v-text-field
                    v-model.number="form.daily_price"
                    :label="$t('daily_price')"
                    variant="outlined"
                    density="compact"
                    rounded
                    color="#42A5F5"
                    class="text-workforce bg-white"
                    type="number"
                    min="0"
                    step="0.01"
                    prepend-inner-icon="mdi-cash"
                    :prefix="'Dh'"
                    :suffix="main.currency || 'DH'"
                    @keyup.enter="save"
                    hide-details
                  ></v-text-field>
                </div>
              </v-expand-transition>
            </v-col>

            <!-- Salary Statement (Declared) -->
            <v-col cols="12" sm="6">
              <div
                class="wfm-toggle-card"
                :class="{ 'wfm-toggle-active-green': form.salary_statement }"
                @click="form.salary_statement = !form.salary_statement"
              >
                <div class="wfm-toggle-left">
                  <div class="wfm-toggle-icon-wrap" :style="{ background: form.salary_statement ? '#4CAF501A' : 'rgba(var(--v-border-color), .06)' }">
                    <v-icon :color="form.salary_statement ? 'success' : 'grey'" size="20">mdi-file-document-check-outline</v-icon>
                  </div>
                  <div>
                    <div class="wfm-toggle-label">{{ $t('salary_statement') }}</div>
                    <div class="text-caption text-medium-emphasis" style="font-size: 11px !important;">
                      {{ $t('declared_worker') }}
                    </div>
                  </div>
                </div>
                <v-switch v-model="form.salary_statement" color="success" inset density="compact" hide-details @click.stop></v-switch>
              </div>
            </v-col>

            <!-- Migrant Worker -->
            <v-col cols="12" sm="6">
              <div
                class="wfm-toggle-card"
                :class="{ 'wfm-toggle-active': form.is_migrant }"
                @click="form.is_migrant = !form.is_migrant"
              >
                <div class="wfm-toggle-left">
                  <div class="wfm-toggle-icon-wrap" :style="{ background: form.is_migrant ? '#FF98001A' : 'rgba(var(--v-border-color), .06)' }">
                    <v-icon :color="form.is_migrant ? 'warning' : 'grey'" size="20">mdi-earth</v-icon>
                  </div>
                  <div>
                    <div class="wfm-toggle-label">{{ $t('is_migrant') }}</div>
                    <div class="text-caption text-medium-emphasis" style="font-size: 11px !important;">
                      {{ $t('migrant_status') }}
                    </div>
                  </div>
                </div>
                <v-switch v-model="form.is_migrant" color="warning" inset density="compact" hide-details @click.stop></v-switch>
              </div>
            </v-col>
          </v-row>
        </v-card-text>

        <v-divider></v-divider>

        <v-card-actions class="pa-4">
          <v-spacer></v-spacer>
          <v-btn @click="dialog = false" variant="text" rounded>{{ $t('cancel') }}</v-btn>
          <v-btn
            color="#42A5F5"
            variant="flat"
            rounded
            class="text-white px-5 font-weight-bold"
            @click="save"
            :loading="saving"
          >
            {{ $t('save') }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- ============= CONFIRM DELETE DIALOG ============= -->
    <v-dialog v-model="confirmDeleteDialog" max-width="420">
      <v-card class="rounded-2xl">
        <v-card-title class="d-flex align-center pa-5 pb-2">
          <v-icon color="error" class="mr-2">mdi-alert-circle-outline</v-icon>
          {{ $t('confirm_delete') }}
        </v-card-title>
        <v-card-text class="pa-5 pt-2 text-body-1">
          {{ $t('confirm_delete_workforce_message') }}
        </v-card-text>
        <v-card-actions class="pa-5 pt-0">
          <v-spacer></v-spacer>
          <v-btn @click="confirmDeleteDialog = false" variant="text" rounded>{{ $t('cancel') }}</v-btn>
          <v-btn color="error" variant="flat" rounded @click="executeDelete" :loading="deleting">{{ $t('delete') }}</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- ============= BULK DELETE CONFIRMATION DIALOG ============= -->
    <v-dialog v-model="bulkDeleteDialog" max-width="450">
      <v-card class="rounded-2xl">
        <v-card-title class="d-flex align-center pa-5 pb-2">
          <v-icon color="error" class="mr-2">mdi-delete-alert</v-icon>
          {{ $t('bulk_delete') }}
        </v-card-title>
        <v-card-text class="pa-5 pt-2 text-body-1">
          {{ $t('confirm_bulk_delete_message', { count: selectedIds.length }) }}
        </v-card-text>
        <v-card-actions class="pa-5 pt-0">
          <v-spacer></v-spacer>
          <v-btn @click="bulkDeleteDialog = false" variant="text" rounded>{{ $t('cancel') }}</v-btn>
          <v-btn color="error" variant="flat" rounded @click="executeBulkDelete" :loading="bulkDeleting">{{ $t('delete') }}</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- ============= SUMMARY SNACKBAR ============= -->
    <transition name="wfm-snackbar-anim">
      <div v-if="snackbar.show" class="wfm-summary-snackbar">
        <div class="wfm-snackbar-card">
          <div class="wfm-snackbar-accent" style="background: #42A5F5;"></div>
          <div class="wfm-snackbar-body">
            <div class="wfm-snackbar-header">
              <v-icon :color="snackbar.color" size="20" class="mr-2">{{ snackbar.icon }}</v-icon>
              <span class="wfm-snackbar-title">{{ snackbar.title }}</span>
              <v-btn icon size="x-small" variant="text" class="ml-auto wfm-snackbar-close" @click="snackbar.show = false">
                <v-icon size="16">mdi-close</v-icon>
              </v-btn>
            </div>
            <div class="wfm-snackbar-grid">
              <div v-for="(f, i) in snackbar.fields" :key="i" class="wfm-snackbar-field">
                <span class="wfm-field-label">{{ f.label }}</span>
                <span class="wfm-field-val">
                  <v-icon v-if="f.icon" size="14" class="mr-1" style="opacity:.7">{{ f.icon }}</v-icon>
                  {{ f.value }}
                </span>
              </div>
            </div>
            <div class="wfm-snackbar-progress">
              <div class="wfm-progress-bar" style="background: #42A5F5;" :style="{ width: snackbarProgress + '%' }"></div>
            </div>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<script>
import { useMain } from '@/stores/Main';
import { useWorkforce } from '@/stores/Workforce';

export default {
  name: 'WorkforceManagement',
  data() {
    return {
      windowHeight: typeof window !== 'undefined' ? window.innerHeight : 800,
      loading: false,
      quickSearch: '',
      quickFilter: 'all',
      columnFilters: {},
      dialog: false,
      editing: null,
      form: this.emptyForm(),
      saving: false,
      confirmDeleteDialog: false,
      deleteTarget: null,
      deleting: false,
      bulkDeleteDialog: false,
      bulkDeleting: false,
      selectedIds: [],
      nationalityPresets: [
        'Moroccan',
        'Senegalese',
        'Malian',
        'Ivorian',
        'Guinean',
        'Algerian',
        'Tunisian',
        'French',
        'Spanish',
      ],
      sexOptions: [
        { title: this.$t('male'), value: 'male' },
        { title: this.$t('female'), value: 'female' },
      ],
      snackbar: {
        show: false,
        title: '',
        color: 'success',
        icon: 'mdi-check-circle',
        fields: [],
      },
      snackbarProgress: 100,
      snackbarTimer: null,
      snackbarInterval: null,
    };
  },
  computed: {
    main() { return useMain(); },
    workforceStore() { return useWorkforce(); },
    items() { return this.workforceStore.list || []; },

    tableHeight() {
      // Dynamic height to fit any screen height cleanly
      return Math.max(340, this.windowHeight - 275);
    },

    declaredCount() {
      return this.items.filter(w => w.salary_statement).length;
    },
    fixedPayCount() {
      return this.items.filter(w => w.fixed_daily_pay).length;
    },
    migrantCount() {
      return this.items.filter(w => w.is_migrant).length;
    },

    headers() {
      return [
        { title: this.$t('full_name'), key: 'full_name', align: 'start', minWidth: '220px' },
        { title: this.$t('matricule'), key: 'matricule', align: 'center', minWidth: '100px' },
        { title: this.$t('nationality'), key: 'nationality', align: 'center', minWidth: '120px' },
        { title: this.$t('sex'), key: 'sex', align: 'center', minWidth: '100px' },
        { title: this.$t('cni'), key: 'cni', align: 'center', minWidth: '110px' },
        { title: this.$t('cnss_id'), key: 'cnss_id', align: 'center', minWidth: '110px' },
        { title: this.$t('is_migrant'), key: 'is_migrant', align: 'center', minWidth: '110px' },
        { title: this.$t('salary_statement'), key: 'salary_statement', align: 'center', minWidth: '130px' },
        { title: this.$t('fixed_daily_pay'), key: 'fixed_daily_pay', align: 'center', minWidth: '120px' },
        { title: this.$t('daily_price'), key: 'daily_price', align: 'center', minWidth: '110px' },
        { title: this.$t('actions'), key: 'actions', align: 'center', sortable: false, minWidth: '90px' },
      ];
    },

    filteredItems() {
      let result = this.items;

      // Quick filter tabs
      if (this.quickFilter === 'fixed') {
        result = result.filter(w => w.fixed_daily_pay);
      } else if (this.quickFilter === 'declared') {
        result = result.filter(w => w.salary_statement);
      } else if (this.quickFilter === 'migrant') {
        result = result.filter(w => w.is_migrant);
      }

      // Quick search
      if (this.quickSearch && this.quickSearch.trim()) {
        const q = this.quickSearch.toLowerCase().trim();
        result = result.filter(item => {
          return (
            (item.full_name && item.full_name.toLowerCase().includes(q)) ||
            (item.matricule && String(item.matricule).toLowerCase().includes(q)) ||
            (item.cni && item.cni.toLowerCase().includes(q)) ||
            (item.cnss_id && String(item.cnss_id).toLowerCase().includes(q)) ||
            (item.nationality && item.nationality.toLowerCase().includes(q))
          );
        });
      }

      // Column filters
      return result.filter(item => {
        return Object.entries(this.columnFilters).every(([key, selected]) => {
          if (!selected || !selected.length) return true;
          return selected.includes(this.getCellDisplayValue(key, item));
        });
      });
    },

    hasActiveColumnFilters() {
      return Object.values(this.columnFilters).some(f => f && f.length > 0);
    },
    selectAll() {
      return this.selectedIds.length === this.filteredItems.length && this.filteredItems.length > 0;
    },
    someSelected() {
      return this.selectedIds.length > 0;
    },
  },

  mounted() {
    this.fetchData();
    window.addEventListener('resize', this.onResize);
  },
  unmounted() {
    window.removeEventListener('resize', this.onResize);
    this.clearSnackbarTimers();
  },

  methods: {
    onResize() {
      this.windowHeight = window.innerHeight;
    },
    formatNumber(val) {
      if (!val && val !== 0) return '0.00';
      return parseFloat(val).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    },
    getInitials(name) {
      if (!name) return '??';
      const parts = name.trim().split(/\s+/);
      if (parts.length >= 2) return (parts[0][0] + parts[1][0]).toUpperCase();
      return name.slice(0, 2).toUpperCase();
    },
    emptyForm() {
      return {
        full_name: '',
        matricule: '',
        nationality: 'Moroccan',
        sex: 'male',
        cni: '',
        cnss_id: '',
        is_migrant: false,
        salary_statement: false,
        fixed_daily_pay: false,
        daily_price: 0,
      };
    },
    getCellDisplayValue(key, item) {
      if (key === 'is_migrant') return item.is_migrant ? this.$t('migrant') : this.$t('local');
      if (key === 'salary_statement') return item.salary_statement ? this.$t('declared') : this.$t('non_declared');
      if (key === 'fixed_daily_pay') return item.fixed_daily_pay ? this.$t('fixed_pay') : this.$t('standard_pay');
      if (key === 'sex') return this.$t(item[key]);
      if (key === 'daily_price') return this.formatNumber(item[key]);
      return String(item[key] ?? '');
    },
    getColumnOptions(key) {
      const set = new Set();
      this.items.forEach(item => set.add(this.getCellDisplayValue(key, item)));
      return [...set].sort();
    },
    clearAllFilters() {
      this.columnFilters = {};
      this.quickSearch = '';
      this.quickFilter = 'all';
    },
    toggleSelectAll(v) {
      this.selectedIds = v ? this.filteredItems.map(i => i.id) : [];
    },
    async fetchData() {
      this.loading = true;
      await this.workforceStore.fetch();
      this.loading = false;
    },
    openDialog(item) {
      this.editing = item;
      if (item) {
        this.form = {
          full_name: item.full_name || '',
          matricule: item.matricule || '',
          nationality: item.nationality || '',
          sex: item.sex || 'male',
          cni: item.cni || '',
          cnss_id: item.cnss_id || '',
          is_migrant: !!item.is_migrant,
          salary_statement: !!item.salary_statement,
          fixed_daily_pay: !!item.fixed_daily_pay,
          daily_price: parseFloat(item.daily_price) || 0,
        };
      } else {
        this.form = this.emptyForm();
      }
      this.dialog = true;
    },
    async save() {
      if (!this.form.full_name || !this.form.matricule) return;
      this.saving = true;
      try {
        let wf;
        if (this.editing) {
          wf = await this.workforceStore.update(this.editing.id, this.form);
          this.triggerSummarySnackbar({
            title: this.$t('workforce_updated') || 'Workforce Updated',
            icon: 'mdi-check-circle',
            color: 'success',
            fields: [
              { label: this.$t('full_name'), value: wf.full_name, icon: 'mdi-account' },
              { label: this.$t('matricule'), value: `#${wf.matricule}`, icon: 'mdi-badge-account-horizontal-outline' },
              { label: this.$t('fixed_pay'), value: wf.fixed_daily_pay ? `${wf.daily_price} ${this.main.currency || 'DH'}` : this.$t('standard_pay'), icon: 'mdi-cash-lock' },
            ],
          });
        } else {
          wf = await this.workforceStore.create(this.form);
          this.triggerSummarySnackbar({
            title: this.$t('workforce_created') || 'Workforce Created',
            icon: 'mdi-account-check',
            color: 'success',
            fields: [
              { label: this.$t('full_name'), value: wf.full_name, icon: 'mdi-account' },
              { label: this.$t('matricule'), value: `#${wf.matricule}`, icon: 'mdi-badge-account-horizontal-outline' },
              { label: this.$t('fixed_pay'), value: wf.fixed_daily_pay ? `${wf.daily_price} ${this.main.currency || 'DH'}` : this.$t('standard_pay'), icon: 'mdi-cash-lock' },
            ],
          });
        }
        this.dialog = false;
        await this.workforceStore.fetch();
      } catch (e) {
        console.error(e);
        this.main.add_snackbar({ show: true, message: this.$t('workforce_save_error') || 'Error saving workforce', color: 'error' });
      } finally {
        this.saving = false;
      }
    },
    confirmDelete(item) {
      this.deleteTarget = item;
      this.confirmDeleteDialog = true;
    },
    async executeDelete() {
      if (!this.deleteTarget) return;
      this.deleting = true;
      const target = { ...this.deleteTarget };
      try {
        await this.workforceStore.remove(target.id);
        this.confirmDeleteDialog = false;
        this.triggerSummarySnackbar({
          title: this.$t('workforce_deleted') || 'Workforce Deleted',
          icon: 'mdi-delete-sweep',
          color: 'error',
          fields: [
            { label: this.$t('workforce'), value: target.full_name, icon: 'mdi-account-remove' },
            { label: this.$t('matricule'), value: `#${target.matricule}`, icon: 'mdi-badge-account-horizontal-outline' },
          ],
        });
      } catch (e) {
        console.error(e);
        this.main.add_snackbar({ show: true, message: this.$t('workforce_delete_error') || 'Error deleting workforce', color: 'error' });
      } finally {
        this.deleting = false;
        this.deleteTarget = null;
      }
    },
    async executeBulkDelete() {
      if (!this.selectedIds.length) return;
      this.bulkDeleting = true;
      const count = this.selectedIds.length;
      try {
        await this.workforceStore.bulkRemove(this.selectedIds);
        this.bulkDeleteDialog = false;
        this.triggerSummarySnackbar({
          title: this.$t('bulk_deleted_success', { count, model: this.$t('workforces') }) || `${count} workforces deleted`,
          icon: 'mdi-delete-sweep',
          color: 'error',
          fields: [
            { label: this.$t('deleted_count'), value: count, icon: 'mdi-account-multiple-remove' },
          ],
        });
        this.selectedIds = [];
      } catch (e) {
        console.error(e);
        this.main.add_snackbar({ show: true, message: this.$t('bulk_delete_error') || 'Error in bulk deletion', color: 'error' });
      } finally {
        this.bulkDeleting = false;
      }
    },

    triggerSummarySnackbar({ title, color = 'success', icon = 'mdi-check-circle', fields = [] }) {
      this.clearSnackbarTimers();
      this.snackbar = { show: true, title, color, icon, fields };
      this.snackbarProgress = 100;

      const duration = 4000;
      const step = 50;
      const decrement = (step / duration) * 100;

      this.snackbarInterval = setInterval(() => {
        this.snackbarProgress = Math.max(0, this.snackbarProgress - decrement);
      }, step);

      this.snackbarTimer = setTimeout(() => {
        this.snackbar.show = false;
        this.clearSnackbarTimers();
      }, duration);
    },
    clearSnackbarTimers() {
      if (this.snackbarTimer) { clearTimeout(this.snackbarTimer); this.snackbarTimer = null; }
      if (this.snackbarInterval) { clearInterval(this.snackbarInterval); this.snackbarInterval = null; }
    },
  },
};
</script>

<style scoped>
.wfm-page-wrap {
  width: 100%;
  height: 100%;
  display: flex;
  flex-direction: column;
}
.wfm-card {
  border-radius: 20px !important;
  display: flex;
  flex-direction: column;
  height: 100%;
  overflow: hidden;
  font-family: 'Inter', sans-serif;
  background: rgb(var(--v-theme-surface));
}
.wfm-hero {
  background: linear-gradient(135deg, #1565C0 0%, #1E88E5 40%, #42A5F5 100%);
  border-radius: inherit;
  border-bottom-left-radius: 0;
  border-bottom-right-radius: 0;
  flex-shrink: 0;
}
.wfm-hero-inner {
  position: relative;
  z-index: 1;
  padding: 16px 24px;
  color: #fff;
}
.wfm-hero-icon {
  width: 48px;
  height: 48px;
  border-radius: 14px;
  background: rgba(255, 255, 255, 0.18);
  backdrop-filter: blur(8px);
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid rgba(255, 255, 255, 0.25);
  flex-shrink: 0;
}
.wfm-hero-title {
  font-size: 20px;
  font-weight: 800;
  letter-spacing: 0.3px;
}
.wfm-kpi-chip {
  padding: 4px 12px;
  border-radius: 20px;
  background: rgba(255, 255, 255, 0.16);
  backdrop-filter: blur(6px);
  border: 1px solid rgba(255, 255, 255, 0.2);
  display: inline-flex;
  align-items: center;
  color: #fff;
  font-size: 12px;
}
.wfm-kpi-num {
  font-weight: 800;
  margin-right: 4px;
  font-family: 'JetBrains Mono', 'Roboto Mono', monospace !important;
  font-variant-numeric: tabular-nums !important;
  font-feature-settings: "tnum" 1 !important;
}
.fnm-currency-num {
  font-family: 'JetBrains Mono', 'Roboto Mono', monospace !important;
  font-variant-numeric: tabular-nums !important;
  font-feature-settings: "tnum" 1 !important;
}
.wfm-kpi-lbl {
  opacity: 0.85;
  font-size: 11px;
}

.wfm-card-body {
  flex: 1 1 0%;
  min-height: 0;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.wfm-search-input :deep(.v-field) {
  border-radius: 24px !important;
}
.wfm-filter-toggle {
  border-color: rgba(66, 165, 245, 0.3) !important;
}

.wfm-table-wrap {
  flex: 1 1 0%;
  min-height: 0;
  width: 100%;
  overflow-x: auto;
  border-radius: 12px;
}
.wfm-table {
  width: 100%;
  border-color: rgba(var(--v-border-color), 0.12) !important;
}

.wfm-avatar-circle {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: linear-gradient(135deg, #1565C0, #42A5F5);
  color: #fff;
  font-size: 12px;
  font-weight: 700;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  letter-spacing: 0.3px;
  flex-shrink: 0;
  box-shadow: 0 2px 6px rgba(21, 101, 192, 0.25);
}

.wfm-th {
  padding: 8px 6px 4px !important;
  background: rgba(var(--v-theme-surface-variant), 0.04);
}
.wfm-th-label {
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.4px;
  opacity: 0.75;
  text-align: center;
  white-space: nowrap;
}
.wfm-filter-row th {
  padding: 0 4px 6px !important;
}
.wfm-filter-th {
  vertical-align: top;
}
.wfm-col-filter {
  min-width: 80px;
}
.wfm-col-filter :deep(.v-field) {
  font-size: 11px !important;
  min-height: 28px !important;
  border-radius: 8px !important;
}
.wfm-col-filter :deep(.v-field__input) {
  padding: 2px 6px !important;
  min-height: 24px !important;
  font-size: 11px;
}
.wfm-col-filter :deep(.v-field__clearable) {
  font-size: 13px;
}
.wfm-col-filter :deep(.v-chip) {
  height: 18px !important;
  font-size: 10px !important;
}

/* Dialog styling */
.wfm-dialog-hero {
  background: linear-gradient(135deg, #1565C0 0%, #1E88E5 40%, #42A5F5 100%);
  padding: 16px 20px;
  display: flex;
  align-items: center;
}
.wfm-dialog-icon {
  width: 40px;
  height: 40px;
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.2);
  backdrop-filter: blur(6px);
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid rgba(255, 255, 255, 0.25);
}
.wfm-preview-card {
  background: linear-gradient(135deg, rgba(21, 101, 192, 0.03) 0%, rgba(66, 165, 245, 0.08) 100%);
  border-color: rgba(66, 165, 245, 0.3) !important;
}

/* Toggle cards */
.wfm-toggle-card {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 10px 14px;
  border-radius: 14px;
  border: 1.5px solid rgba(var(--v-border-color), 0.12);
  cursor: pointer;
  transition: all 0.2s ease;
  background: rgba(var(--v-theme-surface-variant), 0.02);
  height: 100%;
}
.wfm-toggle-card:hover {
  border-color: rgba(66, 165, 245, 0.4);
  background: rgba(66, 165, 245, 0.03);
}
.wfm-toggle-active {
  border-color: #FF9800 !important;
  background: rgba(255, 152, 0, 0.05);
}
.wfm-toggle-active-green {
  border-color: #4CAF50 !important;
  background: rgba(76, 175, 80, 0.05);
}
.wfm-toggle-active-blue {
  border-color: #1565C0 !important;
  background: rgba(21, 101, 192, 0.05);
}
.wfm-toggle-left {
  display: flex;
  align-items: center;
  gap: 12px;
}
.wfm-toggle-icon-wrap {
  width: 38px;
  height: 38px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.2s ease;
  flex-shrink: 0;
}
.wfm-toggle-label {
  font-size: 13px;
  font-weight: 700;
}

/* Summary Snackbar */
.wfm-summary-snackbar {
  position: fixed;
  bottom: 24px;
  right: 24px;
  z-index: 9999;
  max-width: 380px;
  width: calc(100vw - 48px);
}
.wfm-snackbar-card {
  display: flex;
  background: rgb(var(--v-theme-surface));
  border-radius: 16px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.18), 0 2px 8px rgba(0, 0, 0, 0.08);
  border: 1px solid rgba(var(--v-border-color), 0.12);
  overflow: hidden;
}
.wfm-snackbar-accent {
  width: 5px;
  flex-shrink: 0;
}
.wfm-snackbar-body {
  padding: 12px 14px;
  flex: 1;
  min-width: 0;
}
.wfm-snackbar-header {
  display: flex;
  align-items: center;
  margin-bottom: 6px;
}
.wfm-snackbar-title {
  font-size: 13px;
  font-weight: 700;
}
.wfm-snackbar-close {
  opacity: 0.6;
}
.wfm-snackbar-close:hover {
  opacity: 1;
}
.wfm-snackbar-grid {
  display: flex;
  flex-direction: column;
  gap: 3px;
  margin-bottom: 6px;
}
.wfm-snackbar-field {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 11px;
}
.wfm-field-label {
  color: rgba(var(--v-theme-on-surface), 0.65);
}
.wfm-field-val {
  font-weight: 700;
  display: inline-flex;
  align-items: center;
}
.wfm-snackbar-progress {
  height: 3px;
  background: rgba(0, 0, 0, 0.06);
  border-radius: 2px;
  overflow: hidden;
}
.wfm-progress-bar {
  height: 100%;
  transition: width 0.05s linear;
}

.wfm-snackbar-anim-enter-active,
.wfm-snackbar-anim-leave-active {
  transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
.wfm-snackbar-anim-enter-from {
  opacity: 0;
  transform: translateY(20px) scale(0.95);
}
.wfm-snackbar-anim-leave-to {
  opacity: 0;
  transform: translateY(10px) scale(0.98);
}
</style>
