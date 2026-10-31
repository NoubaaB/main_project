<template>
  <v-card class="cm-card" elevation="0">
    <!-- Hero Header (Client Module Color #00838F) -->
    <div class="cm-hero">
      <div class="cm-hero-inner">
        <div class="d-flex align-center ga-4">
          <div class="cm-hero-icon">
            <v-icon size="28" color="white">mdi-account-tie</v-icon>
          </div>
          <div>
            <div class="cm-hero-title">{{ $t('client_management') }}</div>
            <div class="text-white text-caption" style="opacity:.7">{{ $t('manage_clients_and_plafonds_desc') }}</div>
          </div>
        </div>
      </div>
    </div>

    <v-card-text class="pa-4">
      <!-- Top Toolbar (Count, Active Filters, & Add) -->
      <div class="param-top-toolbar mb-4">
        <div class="d-flex align-center ga-2 flex-wrap">
          <v-chip color="#00838F" variant="tonal" size="small" class="font-weight-bold">
            <v-icon start size="14">mdi-account-group</v-icon>
            {{ filteredItems.length }} {{ $t('records') || 'enregistrements' }}
          </v-chip>
          <v-chip v-if="plafondCount > 0" color="#00838F" variant="outlined" size="small" class="font-weight-bold">
            <v-icon start size="14">mdi-shield-check</v-icon>
            {{ plafondCount }} {{ $t('plafond_active') }}
          </v-chip>
          <v-btn
            v-if="hasActiveColumnFilters"
            variant="tonal"
            color="warning"
            rounded="lg"
            size="small"
            prepend-icon="mdi-filter-off"
            @click="clearAllColumnFilters"
          >
            {{ $t('clear_filters') }}
          </v-btn>
        </div>
        <v-spacer></v-spacer>
        <v-btn
          color="#00838F"
          prepend-icon="mdi-plus"
          rounded="xl"
          variant="flat"
          height="38"
          class="text-white font-weight-bold param-add-btn"
          @click="openDialog(null)"
        >
          {{ $t('add_client') }}
        </v-btn>
      </div>

      <!-- Data Table with Column Filters -->
      <v-data-table
        :headers="headers"
        :items="filteredItems"
        :loading="loading ? '#00838F' : false"
        :multi-sort="true"
        density="comfortable"
        class="rounded-xl param-table"
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
                color="#00838F"
                class="param-filter-input"
                hide-details
                clearable
                multiple
                chips
                closable-chips
                :placeholder="$t('filter')"
              >
                <template #chip="{ item, props }">
                  <v-chip v-bind="props" size="x-small" color="#00838F" variant="tonal" closable>
                    {{ item.title }}
                  </v-chip>
                </template>
              </v-autocomplete>
            </th>
          </tr>
        </template>

        <!-- Name column -->
        <template v-slot:[`item.name`]="{ item }">
          <div class="d-flex align-center ga-2 py-1">
            <v-avatar size="32" color="rgba(0, 131, 143, 0.12)">
              <v-icon size="18" color="#00838F">mdi-account-tie</v-icon>
            </v-avatar>
            <div>
              <div class="font-weight-bold text-body-2">{{ item.name }}</div>
              <div v-if="item.ice" class="text-caption text-medium-emphasis">
                ICE: <strong>{{ item.ice }}</strong>
              </div>
            </div>
          </div>
        </template>

        <!-- Contact (Phone / Email) -->
        <template v-slot:[`item.phone`]="{ item }">
          <div>
            <div v-if="item.phone" class="d-flex align-center ga-1 text-caption">
              <v-icon size="13" color="#00838F">mdi-phone</v-icon>
              <span>{{ item.phone }}</span>
            </div>
            <div v-if="item.email" class="d-flex align-center ga-1 text-caption text-medium-emphasis">
              <v-icon size="13">mdi-email-outline</v-icon>
              <span>{{ item.email }}</span>
            </div>
            <span v-if="!item.phone && !item.email" class="text-caption text-medium-emphasis">-</span>
          </div>
        </template>

        <!-- City / Address -->
        <template v-slot:[`item.city`]="{ item }">
          <div>
            <span v-if="item.city" class="font-weight-medium">{{ item.city }}</span>
            <span v-else class="text-medium-emphasis">-</span>
            <div v-if="item.address" class="text-caption text-medium-emphasis text-truncate" style="max-width: 160px;">
              {{ item.address }}
            </div>
          </div>
        </template>

        <!-- Plafond Configuration Status -->
        <template v-slot:[`item.plafond`]="{ item }">
          <div v-if="item.enable_plafond" class="d-flex flex-column ga-1 py-1">
            <div class="d-flex align-center ga-1">
              <v-chip size="x-small" color="#00838F" variant="flat" class="font-weight-bold text-white">
                <v-icon start size="11">mdi-cash-lock</v-icon>
                {{ formatCurrency(item.plafond_amount) }}
              </v-chip>
              <v-chip size="x-small" variant="tonal" :color="item.plafond_mode === 'yearly' ? 'purple' : 'teal'">
                {{ item.plafond_mode === 'yearly' ? $t('yearly') : $t('monthly') }}
              </v-chip>
            </div>
            <div>
              <v-chip
                size="x-small"
                variant="outlined"
                :color="item.block_on_plafond ? 'error' : 'warning'"
                class="font-weight-bold"
              >
                <v-icon start size="11">{{ item.block_on_plafond ? 'mdi-cancel' : 'mdi-alert' }}</v-icon>
                {{ item.block_on_plafond ? $t('blocking') : $t('warning_only') }}
              </v-chip>
            </div>
          </div>
          <v-chip v-else size="x-small" variant="tonal" color="grey">
            {{ $t('disabled') }}
          </v-chip>
        </template>

        <!-- Active Status -->
        <template v-slot:[`item.active_status`]="{ item }">
          <v-chip
            size="x-small"
            :color="item.active_status !== false ? 'success' : 'grey'"
            variant="tonal"
            class="font-weight-bold"
          >
            {{ item.active_status !== false ? $t('active') : $t('inactive') }}
          </v-chip>
        </template>

        <!-- Actions -->
        <template v-slot:[`item.actions`]="{ item }">
          <div class="param-actions-cell">
            <v-tooltip :text="$t('edit')" location="top">
              <template #activator="{ props }">
                <v-btn v-bind="props" icon size="28" variant="tonal" color="#00838F" class="param-action-btn mr-1" @click="openDialog(item)">
                  <v-icon size="15">mdi-pencil</v-icon>
                </v-btn>
              </template>
            </v-tooltip>
            <v-tooltip :text="$t('delete')" location="top">
              <template #activator="{ props }">
                <v-btn v-bind="props" icon size="28" variant="tonal" color="error" class="param-action-btn param-action-delete" @click="confirmDelete(item)">
                  <v-icon size="15">mdi-delete-outline</v-icon>
                </v-btn>
              </template>
            </v-tooltip>
          </div>
        </template>
      </v-data-table>
    </v-card-text>

    <!-- Edit/Add Dialog -->
    <v-dialog v-model="dialog" max-width="680" persistent scrollable>
      <v-card class="rounded-xl overflow-hidden param-dialog-card" elevation="24">
        <!-- Dialog Header -->
        <div class="param-dialog-header" style="background: linear-gradient(135deg, #006064 0%, #00838F 40%, #26C6DA 100%);">
          <div class="d-flex align-center justify-space-between w-100 pa-4 text-white">
            <div class="d-flex align-center ga-3">
              <div class="param-dialog-icon-badge" style="background: rgba(255,255,255,0.2); color: #fff;">
                <v-icon size="24">{{ editing ? 'mdi-pencil-box-outline' : 'mdi-account-plus-outline' }}</v-icon>
              </div>
              <div>
                <div class="param-dialog-title text-h6 font-weight-bold">{{ editing ? $t('edit_client') : $t('add_client') }}</div>
                <div class="param-dialog-subtitle text-caption" style="opacity:.8">{{ $t('manage_client_info_and_plafond') }}</div>
              </div>
            </div>
            <v-btn icon="mdi-close" variant="text" size="small" class="text-white" @click="dialog = false" />
          </div>
        </div>

        <v-card-text class="pa-5 param-dialog-body" style="max-height: 75vh;">
          <v-form ref="formRef" @submit.prevent="save">
            <!-- Section 1: General Client Information -->
            <div class="text-subtitle-2 font-weight-bold text-client mb-3 d-flex align-center ga-2">
              <v-icon size="18" color="#00838F">mdi-information-outline</v-icon>
              <span>{{ $t('general_information') }}</span>
            </div>

            <v-row dense>
              <!-- Name (Required) -->
              <v-col cols="12" sm="8">
                <v-text-field
                  v-model="form.name"
                  :label="$t('client_name') + ' *'"
                  variant="outlined"
                  density="compact"
                  rounded
                  clearable
                  color="#00838F"
                  prepend-inner-icon="mdi-account"
                  :rules="[v => !!v || $t('is_required', { type: $t('name') })]"
                  hide-details="auto"
                  class="mb-3"
                ></v-text-field>
              </v-col>

              <!-- ICE -->
              <v-col cols="12" sm="4">
                <v-text-field
                  v-model="form.ice"
                  :label="$t('ice')"
                  variant="outlined"
                  density="compact"
                  rounded
                  clearable
                  color="#00838F"
                  prepend-inner-icon="mdi-card-account-details-outline"
                  hide-details="auto"
                  class="mb-3"
                ></v-text-field>
              </v-col>

              <!-- Phone -->
              <v-col cols="12" sm="6">
                <v-text-field
                  v-model="form.phone"
                  :label="$t('phone')"
                  variant="outlined"
                  density="compact"
                  rounded
                  clearable
                  color="#00838F"
                  prepend-inner-icon="mdi-phone"
                  hide-details="auto"
                  class="mb-3"
                ></v-text-field>
              </v-col>

              <!-- Email -->
              <v-col cols="12" sm="6">
                <v-text-field
                  v-model="form.email"
                  :label="$t('email')"
                  variant="outlined"
                  density="compact"
                  rounded
                  clearable
                  color="#00838F"
                  prepend-inner-icon="mdi-email-outline"
                  hide-details="auto"
                  class="mb-3"
                ></v-text-field>
              </v-col>

              <!-- Address -->
              <v-col cols="12" sm="8">
                <v-text-field
                  v-model="form.address"
                  :label="$t('address')"
                  variant="outlined"
                  density="compact"
                  rounded
                  clearable
                  color="#00838F"
                  prepend-inner-icon="mdi-map-marker-outline"
                  hide-details="auto"
                  class="mb-3"
                ></v-text-field>
              </v-col>

              <!-- City -->
              <v-col cols="12" sm="4">
                <v-text-field
                  v-model="form.city"
                  :label="$t('city')"
                  variant="outlined"
                  density="compact"
                  rounded
                  clearable
                  color="#00838F"
                  prepend-inner-icon="mdi-city"
                  hide-details="auto"
                  class="mb-3"
                ></v-text-field>
              </v-col>

              <!-- Country -->
              <v-col cols="12" sm="6">
                <v-text-field
                  v-model="form.country"
                  :label="$t('country')"
                  variant="outlined"
                  density="compact"
                  rounded
                  clearable
                  color="#00838F"
                  prepend-inner-icon="mdi-earth"
                  hide-details="auto"
                  class="mb-3"
                ></v-text-field>
              </v-col>

              <!-- Website -->
              <v-col cols="12" sm="6">
                <v-text-field
                  v-model="form.website"
                  :label="$t('website')"
                  variant="outlined"
                  density="compact"
                  rounded
                  clearable
                  color="#00838F"
                  prepend-inner-icon="mdi-web"
                  hide-details="auto"
                  class="mb-3"
                ></v-text-field>
              </v-col>

              <!-- Description -->
              <v-col cols="12">
                <v-textarea
                  v-model="form.description"
                  :label="$t('description')"
                  variant="outlined"
                  density="compact"
                  rounded
                  rows="2"
                  color="#00838F"
                  prepend-inner-icon="mdi-text"
                  hide-details="auto"
                  class="mb-3"
                ></v-textarea>
              </v-col>

              <!-- Active Status Switch -->
              <v-col cols="12">
                <v-switch
                  v-model="form.active_status"
                  :label="$t('active_status')"
                  color="#00838F"
                  hide-details
                  density="compact"
                ></v-switch>
              </v-col>
            </v-row>

            <v-divider class="my-4" color="#00838F" style="opacity: 0.2;"></v-divider>

            <!-- Section 2: Plafond Configuration (Credit Limit) -->
            <div class="pa-4 rounded-xl border mb-2" style="background: rgba(0, 131, 143, 0.04); border-color: rgba(0, 131, 143, 0.2) !important;">
              <div class="d-flex align-center justify-space-between mb-3">
                <div class="d-flex align-center ga-2">
                  <v-icon size="22" color="#00838F">mdi-shield-lock-outline</v-icon>
                  <div>
                    <span class="text-subtitle-2 font-weight-bold text-client">{{ $t('credit_plafond_configuration') }}</span>
                    <div class="text-caption text-medium-emphasis">{{ $t('credit_plafond_subtitle') }}</div>
                  </div>
                </div>
                <v-switch
                  v-model="form.enable_plafond"
                  color="#00838F"
                  hide-details
                  density="compact"
                ></v-switch>
              </div>

              <!-- Expanded Plafond Fields -->
              <v-expand-transition>
                <div v-show="form.enable_plafond" class="pt-2">
                  <v-row dense>
                    <!-- Plafond Amount -->
                    <v-col cols="12" sm="6">
                      <v-text-field
                        v-model.number="form.plafond_amount"
                        :label="$t('max_plafond_amount') + ' (' + (main.currency || 'MAD') + ') *'"
                        type="number"
                        min="0"
                        step="100"
                        variant="outlined"
                        density="compact"
                        rounded
                        clearable
                        color="#00838F"
                        prepend-inner-icon="mdi-cash-multiple"
                        :rules="form.enable_plafond ? [v => (v !== null && v !== undefined && v !== '') || $t('is_required', { type: $t('amount') }), v => Number(v) > 0 || $t('must_be_positive')] : []"
                        hide-details="auto"
                        class="mb-3"
                      ></v-text-field>
                    </v-col>

                    <!-- Plafond Mode: Monthly vs Yearly -->
                    <v-col cols="12" sm="6">
                      <v-select
                        v-model="form.plafond_mode"
                        :items="plafondModeOptions"
                        :label="$t('calculation_mode')"
                        variant="outlined"
                        density="compact"
                        rounded
                        color="#00838F"
                        prepend-inner-icon="mdi-calendar-repeat"
                        hide-details="auto"
                        class="mb-3"
                      ></v-select>
                    </v-col>

                    <!-- Block On Plafond Switch -->
                    <v-col cols="12">
                      <div class="d-flex align-center justify-space-between pa-3 rounded-lg border bg-surface mt-1">
                        <div>
                          <div class="font-weight-bold text-body-2 d-flex align-center ga-1">
                            <v-icon size="16" :color="form.block_on_plafond ? 'error' : '#00838F'">
                              {{ form.block_on_plafond ? 'mdi-lock-alert' : 'mdi-bell-alert-outline' }}
                            </v-icon>
                            <span>{{ $t('block_on_plafond') }}</span>
                          </div>
                          <div class="text-caption text-medium-emphasis">
                            {{ form.block_on_plafond ? $t('block_on_plafond_active_desc') : $t('block_on_plafond_warning_desc') }}
                          </div>
                        </div>
                        <v-switch
                          v-model="form.block_on_plafond"
                          :color="form.block_on_plafond ? 'error' : '#00838F'"
                          hide-details
                          density="compact"
                        ></v-switch>
                      </div>
                    </v-col>

                    <!-- Contextual Explanation Alert -->
                    <v-col cols="12" class="mt-2">
                      <v-alert
                        density="compact"
                        variant="tonal"
                        color="#00838F"
                        class="rounded-lg text-caption"
                        icon="mdi-information"
                      >
                        <span v-if="form.plafond_mode === 'monthly'">
                          {{ $t('client_monthly_plafond_rule_hint') }}
                        </span>
                        <span v-else>
                          {{ $t('client_yearly_plafond_rule_hint') }}
                        </span>
                      </v-alert>
                    </v-col>
                  </v-row>
                </div>
              </v-expand-transition>
            </div>
          </v-form>
        </v-card-text>

        <!-- Dialog Actions -->
        <v-card-actions class="param-dialog-actions pa-4 border-t bg-surface">
          <v-btn variant="tonal" color="grey" rounded="lg" class="px-4" @click="dialog = false">{{ $t('cancel') }}</v-btn>
          <v-spacer></v-spacer>
          <v-btn
            color="#00838F"
            variant="flat"
            rounded="lg"
            class="px-5 text-white font-weight-bold"
            @click="save"
            :loading="saving"
            :disabled="!form.name"
            prepend-icon="mdi-content-save"
          >
            {{ $t('save') }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Delete Confirmation Dialog -->
    <v-dialog v-model="deleteDialog" max-width="440">
      <v-card class="rounded-xl overflow-hidden param-dialog-card">
        <div class="pa-6 text-center">
          <div class="param-dialog-icon-badge mx-auto mb-4" style="background: rgba(244, 67, 54, 0.12); color: #f44336; width: 56px; height: 56px;">
            <v-icon size="32">mdi-alert-circle-outline</v-icon>
          </div>
          <div class="text-h6 font-weight-bold mb-2">{{ $t('confirm_delete') }}</div>
          <div class="text-body-2 text-medium-emphasis mb-4">
            {{ $t('confirm_delete_client_message') }}
          </div>
          <div v-if="deleteTarget" class="pa-3 rounded-lg text-left" style="background: rgba(244,67,54,.06); border: 1px solid rgba(244, 67, 54, 0.15);">
            <div class="font-weight-bold">{{ deleteTarget.name }}</div>
            <div v-if="deleteTarget.usage_count > 0" class="text-caption text-error mt-1">
              <v-icon size="14">mdi-alert</v-icon>
              {{ deleteTarget.usage_count }} {{ $t('associated_vouchers_exist') }}
            </div>
          </div>
        </div>
        <v-card-actions class="param-dialog-actions pa-4 border-t bg-surface">
          <v-btn variant="tonal" rounded="lg" color="grey" class="px-4" @click="deleteDialog = false">{{ $t('cancel') }}</v-btn>
          <v-spacer></v-spacer>
          <v-btn color="error" variant="flat" rounded="lg" class="px-5 font-weight-bold" @click="executeDelete" :loading="deleting" prepend-icon="mdi-delete">{{ $t('delete') }}</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Summary Snackbar with Countdown Progress Bar (Rule 5 & AddCharge style) -->
    <v-snackbar
      v-model="summarySnackbar.show"
      :timeout="6000"
      location="top right"
      color="surface"
      rounded="lg"
      elevation="12"
      min-width="340"
      max-width="420"
      @update:modelValue="val => { if (!val) clearInterval(snackbarTimer); }"
    >
      <v-progress-linear :model-value="snackbarProgress" color="#00838F" height="3" class="mb-2" rounded></v-progress-linear>
      <div class="d-flex align-center mb-1">
        <v-icon :color="summarySnackbar.color || '#00838F'" class="mr-2">
          {{ summarySnackbar.color === 'error' ? 'mdi-alert-circle' : 'mdi-check-circle' }}
        </v-icon>
        <span class="text-subtitle-2 font-weight-bold">{{ summarySnackbar.title }}</span>
      </div>
      <div v-if="summarySnackbar.detail" class="text-caption text-medium-emphasis ms-7">
        {{ summarySnackbar.detail }}
      </div>
      <template #actions>
        <v-btn variant="text" size="small" icon="mdi-close" @click="summarySnackbar.show = false"></v-btn>
      </template>
    </v-snackbar>
  </v-card>
</template>

<script>
import mitt from '@/plugins/mitt';
import { useMain } from '@/stores/Main';
import { useClient } from '@/stores/Client';

export default {
  name: 'ClientManagement',
  data() {
    return {
      items: [],
      loading: false,
      dialog: false,
      editing: null,
      saving: false,
      deleteDialog: false,
      deleteTarget: null,
      deleting: false,
      columnFilters: {},
      form: {
        name: '',
        ice: '',
        phone: '',
        email: '',
        address: '',
        city: '',
        country: '',
        website: '',
        description: '',
        active_status: true,
        enable_plafond: false,
        plafond_amount: null,
        block_on_plafond: false,
        plafond_mode: 'monthly',
        plafond_blocking_scope: 'past_unpaid',
      },
      summarySnackbar: { show: false, title: '', detail: '', color: '#00838F' },
      snackbarProgress: 100,
      snackbarTimer: null,
    };
  },
  computed: {
    main() { return useMain(); },
    clientStore() { return useClient(); },
    headers() {
      return [
        { title: this.$t('client'), key: 'name', align: 'start' },
        { title: this.$t('contact'), key: 'phone', align: 'start' },
        { title: this.$t('city'), key: 'city', align: 'start' },
        { title: this.$t('credit_plafond'), key: 'plafond', align: 'center', sortable: false },
        { title: this.$t('status'), key: 'active_status', align: 'center' },
        { title: this.$t('actions'), key: 'actions', align: 'center', sortable: false, width: '110px' },
      ];
    },
    plafondModeOptions() {
      return [
        { title: this.$t('monthly') + ' (Approche mensuelle)', value: 'monthly' },
        { title: this.$t('yearly') + ' (Approche annuelle cumulée)', value: 'yearly' },
      ];
    },
    plafondCount() {
      return this.items.filter(c => c.enable_plafond).length;
    },
    hasActiveColumnFilters() {
      return Object.values(this.columnFilters).some(v => Array.isArray(v) ? v.length > 0 : !!v);
    },
    filteredItems() {
      if (!this.hasActiveColumnFilters) return this.items;
      return this.items.filter(item => {
        for (const [key, filterValues] of Object.entries(this.columnFilters)) {
          if (!filterValues || (Array.isArray(filterValues) && filterValues.length === 0)) continue;
          let val = '';
          if (key === 'active_status') {
            val = item.active_status !== false ? this.$t('active') : this.$t('inactive');
          } else if (key === 'plafond') {
            val = item.enable_plafond ? (item.plafond_mode === 'yearly' ? this.$t('yearly') : this.$t('monthly')) : this.$t('disabled');
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
  },
  mounted() {
    this.fetchItems();
    mitt.on('model-changed', this._onMC);
  },
  beforeUnmount() {
    mitt.off('model-changed', this._onMC);
    if (this.snackbarTimer) clearInterval(this.snackbarTimer);
  },
  methods: {
    _onMC(d) {
      if (d.model === 'Client') this.fetchItems();
    },
    formatCurrency(val) {
      if (val === null || val === undefined || isNaN(val)) return '-';
      return new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(val) + ' ' + (this.main.currency || 'DH');
    },
    getColumnOptions(key) {
      const set = new Set();
      this.items.forEach(item => {
        let v = '';
        if (key === 'active_status') {
          v = item.active_status !== false ? this.$t('active') : this.$t('inactive');
        } else if (key === 'plafond') {
          v = item.enable_plafond ? (item.plafond_mode === 'yearly' ? this.$t('yearly') : this.$t('monthly')) : this.$t('disabled');
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
    triggerSummarySnackbar(title, detail = '', color = '#00838F') {
      if (this.snackbarTimer) clearInterval(this.snackbarTimer);
      this.summarySnackbar = { show: true, title, detail, color };
      this.snackbarProgress = 100;
      const duration = 6000;
      const interval = 50;
      const step = (interval / duration) * 100;
      this.snackbarTimer = setInterval(() => {
        this.snackbarProgress -= step;
        if (this.snackbarProgress <= 0) {
          clearInterval(this.snackbarTimer);
          this.summarySnackbar.show = false;
        }
      }, interval);
    },
    async fetchItems() {
      this.loading = true;
      try {
        const list = await this.clientStore.fetchClients();
        this.items = list;
      } catch (e) {
        console.error(e);
      }
      this.loading = false;
    },
    openDialog(item) {
      this.editing = item;
      if (item) {
        this.form = {
          name: item.name || '',
          ice: item.ice || '',
          phone: item.phone || '',
          email: item.email || '',
          address: item.address || '',
          city: item.city || '',
          country: item.country || '',
          website: item.website || '',
          description: item.description || '',
          active_status: item.active_status !== false,
          enable_plafond: !!item.enable_plafond,
          plafond_amount: item.plafond_amount !== null && item.plafond_amount !== undefined ? Number(item.plafond_amount) : null,
          block_on_plafond: !!item.block_on_plafond,
          plafond_mode: item.plafond_mode || 'monthly',
          plafond_blocking_scope: item.plafond_blocking_scope || 'past_unpaid',
        };
      } else {
        this.form = {
          name: '',
          ice: '',
          phone: '',
          email: '',
          address: '',
          city: '',
          country: '',
          website: '',
          description: '',
          active_status: true,
          enable_plafond: false,
          plafond_amount: null,
          block_on_plafond: false,
          plafond_mode: 'monthly',
          plafond_blocking_scope: 'past_unpaid',
        };
      }
      this.dialog = true;
    },
    async save() {
      if (!this.form.name) return;
      if (this.form.enable_plafond && (!this.form.plafond_amount || Number(this.form.plafond_amount) <= 0)) {
        this.triggerSummarySnackbar(this.$t('validation_error'), this.$t('plafond_amount_required'), 'error');
        return;
      }
      this.saving = true;
      try {
        if (this.editing) {
          await this.clientStore.updateClient(this.editing.id, this.form);
          this.triggerSummarySnackbar(this.$t('client_updated'), this.form.name, '#00838F');
        } else {
          await this.clientStore.createClient(this.form);
          this.triggerSummarySnackbar(this.$t('client_created'), this.form.name, '#00838F');
        }
        this.dialog = false;
        this.fetchItems();
      } catch (e) {
        console.error(e);
        const msg = e.response?.data?.message || this.$t('error_occurred');
        this.triggerSummarySnackbar(this.$t('error_occurred'), msg, 'error');
      }
      this.saving = false;
    },
    confirmDelete(item) {
      this.deleteTarget = item;
      this.deleteDialog = true;
    },
    async executeDelete() {
      this.deleting = true;
      if (!this.deleteTarget) {
        this.deleteDialog = false;
        this.deleting = false;
        return;
      }
      const targetName = this.deleteTarget.name;
      try {
        await this.clientStore.deleteClient(this.deleteTarget.id);
        this.triggerSummarySnackbar(this.$t('client_deleted'), targetName, '#00838F');
        this.fetchItems();
      } catch (e) {
        console.error(e);
        this.triggerSummarySnackbar(this.$t('error_occurred'), 'Delete failed', 'error');
      }
      this.deleteTarget = null;
      this.deleteDialog = false;
      this.deleting = false;
    },
  },
};
</script>

<style scoped>
.cm-card { border-radius: 20px !important; overflow: visible; font-family: 'Inter', sans-serif; }
.cm-hero { background: linear-gradient(135deg, #006064 0%, #00838F 40%, #26C6DA 100%); border-radius: inherit; border-bottom-left-radius: 0; border-bottom-right-radius: 0; height: 100px; }
.cm-hero-inner { position: relative; z-index: 1; padding: 22px 26px; color: #fff; }
.cm-hero-icon { width: 52px; height: 52px; border-radius: 14px; background: rgba(255,255,255,.18); backdrop-filter: blur(6px); display: flex; align-items: center; justify-content: center; border: 1px solid rgba(255,255,255,.15); }
.cm-hero-title { font-size: 19px; font-weight: 800; letter-spacing: .3px; }
.text-client { color: #00838F !important; }
.param-table { border: 1px solid rgba(var(--v-border-color), .12); }
.param-th { padding: 10px 8px 4px !important; }
.param-th-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .4px; opacity: .7; text-align: center; white-space: nowrap; cursor: pointer; }
.param-filter-row th { padding: 4px 6px 8px !important; }
.param-filter-input :deep(.v-field) { border-radius: 8px !important; font-size: 11px !important; }
.param-filter-input :deep(.v-field__input) { padding: 2px 6px !important; min-height: 28px !important; }
.param-filter-input :deep(.v-chip) { height: 18px !important; font-size: 10px !important; }
</style>

