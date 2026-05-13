<template>
<v-card class="ls-card" elevation="0">
  <div class="ls-hero"><div class="ls-hero-inner">
    <div class="d-flex align-center ga-4"><div class="ls-hero-icon"><v-icon size="28" color="white">mdi-cash-register</v-icon></div><div><div class="ls-hero-title">{{ $t('shippings') }}</div><div class="text-white text-caption" style="opacity:.7">{{ $t('datatable_shippings') }}</div></div></div>
    <div class="ls-hero-actions"><v-btn variant="tonal" color="white" rounded size="small" prepend-icon="mdi-refresh" @click="fetchShippings" :loading="loading">{{ $t('refresh') }}</v-btn></div>
  </div></div>
  <!-- Tab Slider -->
  <v-slide-group v-model="activeTab" mandatory show-arrows class="ls-tab-slider mx-4 mt-3">
    <v-slide-group-item v-for="tab in sellTabs" :key="tab.value" v-slot="{ isSelected, toggle }">
      <v-btn :color="isSelected ? '#4643a0' : undefined" :variant="isSelected ? 'flat' : 'text'" rounded class="mx-1" @click="toggle" :prepend-icon="tab.icon">{{ $t(tab.label) }}</v-btn>
    </v-slide-group-item>
  </v-slide-group>
  <v-divider class="mx-4 mt-2" color="#4643a0"></v-divider>
  <!-- TAB: List Sell -->
  <v-card-text v-if="activeTab === 0" class="pa-4">
    <!-- Analytics Row: Calendar | Chart | Statistics -->
    <v-row dense class="mb-3">
      <v-col cols="3">
        <div class="ls-panel-card">
          <div class="ls-panel-header"><v-icon size="16" color="#4643a0" class="mr-1">mdi-calendar-range</v-icon><span>{{ $t('date_range') }}</span></div>
          <DatePicker v-model.range="dateRangeVC" :is-dark="main.is_dark" :locale="main.lang?.short||'en'" mode="date" color="purple" :max-date="new Date()" expanded @update:model-value="onDateRangeChange" />
        </div>
      </v-col>
      <v-col cols="6">
        <div class="ls-panel-card" style="height:100%">
          <div class="ls-panel-header d-flex align-center justify-space-between">
            <div class="d-flex align-center"><v-icon size="16" color="#4643a0" class="mr-1">mdi-chart-areaspline</v-icon><span>{{ $t('analytics') }}</span></div>
            <v-btn-toggle v-model="chartMode" mandatory density="compact" color="#4643a0" variant="outlined" rounded="lg" divided>
              <v-btn value="daily" size="x-small">{{ $t('daily') }}</v-btn>
              <v-btn value="monthly" size="x-small">{{ $t('monthly') }}</v-btn>
              <v-btn value="yearly" size="x-small">{{ $t('yearly') }}</v-btn>
            </v-btn-toggle>
          </div>
          <apexchart v-if="chartSeries[0].data.length" type="line" :height="260" :options="chartOptions" :series="chartSeries"></apexchart>
          <div v-else class="d-flex align-center justify-center" style="height:220px;opacity:.4"><v-icon size="48" color="grey">mdi-chart-line-variant</v-icon></div>
        </div>
      </v-col>
      <v-col cols="3">
        <div class="ls-panel-card ls-stats-panel" style="height:100%;overflow-y:auto">
          <div class="ls-panel-header"><v-icon size="16" color="#4643a0" class="mr-1">mdi-chart-box-outline</v-icon><span>{{ $t('statistics') }}</span></div>
          <div v-for="(stat,si) in categoryStats" :key="si" class="ls-stat-item mb-2">
            <div class="d-flex align-center justify-space-between mb-1">
              <v-chip size="x-small" color="#4643a0" variant="tonal" class="font-weight-bold">{{ stat.name }} ({{ stat.count }})</v-chip>
              <v-chip size="x-small" color="#4643a0" variant="elevated" class="font-weight-bold">{{ totalWeight>0?Math.round(stat.weight/totalWeight*1000)/10:0 }}%</v-chip>
            </div>
            <v-progress-linear :model-value="totalWeight>0?(stat.weight/totalWeight*100):0" color="#4643a0" bg-color="#D1C4E9" height="5" rounded class="mb-1"></v-progress-linear>
            <div class="d-flex flex-column ga-1 text-caption">
              <div class="d-flex align-center ga-1"><v-icon size="12" color="purple">mdi-weight-kilogram</v-icon><span class="text-medium-emphasis">{{ $t('weight') }}:</span><b style="color: #4643a0">{{ main.formatDisplayNumber(stat.weight) }} Kg</b></div>
              <div class="d-flex align-center ga-1"><v-icon size="12" color="deep-orange">mdi-cash</v-icon><span class="text-medium-emphasis">{{ $t('total_price') }}:</span><b class="text-deep-orange">{{ main.formatDisplayNumber(stat.price) }} {{ main.currency }}</b></div>
            </div>
          </div>
          <v-divider class="my-2"></v-divider>
          <div class="d-flex flex-column ga-1">
            <div class="d-flex align-center ga-1"><v-icon size="14" color="#4643a0">mdi-sigma</v-icon><span class="text-caption font-weight-bold">{{ $t('total_weight') }}:</span><b style="color: #4643a0" class="text-caption">{{ main.formatDisplayNumber(totalWeight) }} Kg</b></div>
            <div class="d-flex align-center ga-1"><v-icon size="14" color="deep-orange">mdi-sigma</v-icon><span class="text-caption font-weight-bold">{{ $t('total_price') }}:</span><b class="text-deep-orange text-caption">{{ main.formatDisplayNumber(totalPrice) }} {{ main.currency }}</b></div>
          </div>
        </div>
      </v-col>
    </v-row>
    <!-- Action Buttons + Panels Row -->
    <v-row dense class="mb-3" align="start">
      <v-col cols="6">
        <div class="d-flex justify-center mb-2"><v-btn block :variant="showAdvanced?'flat':'outlined'" :color="showAdvanced?'success':'grey'" rounded size="small" :prepend-icon="showAdvanced?'mdi-chevron-up':'mdi-chevron-down'" @click="showAdvanced=!showAdvanced">{{ $t('advanced_filters') }}</v-btn></div>
        <v-expand-transition><div v-if="showAdvanced" class="adv-panel pa-3 rounded-lg">
          <v-row dense>
            <v-col cols="6"><v-autocomplete v-model="advFilter.articleType" :items="[{title:$t('text'),value:'text'},{title:'FishType',value:'FishType'},{title:'GuanoType',value:'GuanoType'},{title:'PackageType',value:'PackageType'}]" :label="$t('article_type')" variant="outlined" density="compact" rounded color="#4643a0" style="color: #4643a0" hide-details clearable></v-autocomplete></v-col>
            <v-col v-if="advFilter.articleType==='FishType'" cols="6"><v-autocomplete v-model="advFilter.fishFamilies" :items="fishFamilies.map(f=>({title:f[`name_${main.lang?.code}`]||f.name,value:f.id}))" :label="$t('fish_family')" variant="outlined" density="compact" rounded color="#4643a0" style="color: #4643a0" hide-details clearable multiple chips closable-chips></v-autocomplete></v-col>
            <v-col v-if="advFilter.articleType==='FishType'" cols="6"><v-autocomplete v-model="advFilter.fishFreshFrozen" :items="['fresh','frozen']" :label="$t('fish_type_fresh_frozen')" variant="outlined" density="compact" rounded color="#4643a0" style="color: #4643a0" hide-details clearable multiple chips closable-chips></v-autocomplete></v-col>
            <v-col v-if="advFilter.articleType==='FishType'" cols="6"><v-autocomplete v-model="advFilter.fishProcessTypes" :items="fishProcessTypes.map(f=>({title:f[`name_${main.lang?.code}`]||f.name,value:f.id}))" :label="$t('fish_process_type')" variant="outlined" density="compact" rounded color="#4643a0" style="color: #4643a0" hide-details clearable multiple chips closable-chips></v-autocomplete></v-col>
            <v-col v-if="advFilter.articleType==='GuanoType'" cols="6"><v-autocomplete v-model="advFilter.guanoTypes" :items="guanoTypes.map(g=>({title:g[`name_${main.lang?.code}`]||g.name,value:g.id}))" :label="$t('guano_type')" variant="outlined" density="compact" rounded color="#4643a0" style="color: #4643a0" hide-details clearable multiple chips closable-chips></v-autocomplete></v-col>
            <v-col v-if="advFilter.articleType==='PackageType'" cols="6"><v-autocomplete v-model="advFilter.packageTypes" :items="packageTypes.map(p=>({title:p[`name_${main.lang?.code}`]||p.name,value:p.id}))" :label="$t('package_type')" variant="outlined" density="compact" rounded color="#4643a0" style="color: #4643a0" hide-details clearable multiple chips closable-chips></v-autocomplete></v-col>
            <v-col cols="6"><v-autocomplete v-model="advFilter.warehouses" :items="warehouses.map(w=>({title:w[`name_${main.lang?.code}`]||w.name,value:w.id}))" :label="$t('warehouse')" variant="outlined" density="compact" rounded color="#4643a0" style="color: #4643a0" hide-details clearable multiple chips closable-chips></v-autocomplete></v-col>
          </v-row>
        </div></v-expand-transition>
      </v-col>
      <v-col cols="6">
        <div class="d-flex flex-column justify-center mb-2">
          <v-btn block :variant="showInvoice?'flat':'outlined'" :color="showInvoice?'teal':'grey'" rounded size="small" :prepend-icon="showInvoice?'mdi-chevron-up':'mdi-chevron-down'" @click="showInvoice=!showInvoice" :disabled="!canSetupInvoice">{{ $t('client_invoices') }}</v-btn>
          <v-alert v-if="uninvoicedSelectedShippings.length && !canSetupInvoice" type="warning" density="compact" variant="tonal" class="mt-1 text-caption" closable>{{ $t('selected_shippings_different_clients') }}</v-alert>
        </div>
        <v-expand-transition><div v-if="showInvoice&&uninvoicedSelectedShippings.length" class="inv-panel pa-3 rounded-lg">
          <v-row dense>
            <v-col cols="6">
              <DatePicker v-model="invForm.date" :is-dark="main.is_dark" :locale="main.lang?.short||'en'" mode="date" color="#4643a0" :disabled-dates="invDisabledDates" :min-date="invMinDate" :max-date="invMaxDate">
                <template #default="{ togglePopover, inputValue }">
                  <v-text-field :model-value="inputValue" :label="$t('invoice_date')" variant="outlined" density="compact" rounded color="#4643a0" style="color: #4643a0" hide-details readonly prepend-inner-icon="mdi-calendar" @click="togglePopover"></v-text-field>
                </template>
              </DatePicker>
            </v-col>
            <v-col cols="6"><v-text-field :model-value="invoiceClients.join(', ')" :label="$t('clients')" variant="outlined" density="compact" rounded color="#4643a0" style="color: #4643a0" hide-details readonly></v-text-field></v-col>
            <v-col cols="6"><v-autocomplete v-model="invForm.type_invoice" :items="[{title:$t('local'),value:'local'},{title:$t('export_type'),value:'export'}]" :label="$t('invoice_type')" variant="outlined" density="compact" rounded color="#4643a0" style="color: #4643a0" hide-details @update:model-value="onTypeInvoiceChange"></v-autocomplete></v-col>
            <v-col cols="6"><v-combobox v-model="invForm.trackingNumbers" :label="$t('invoice_tracking_number')" variant="outlined" density="compact" rounded color="#4643a0" style="color: #4643a0" hide-details multiple chips closable-chips :loading="invChecking" prepend-inner-icon="mdi-barcode" @update:model-value="onTrackingUpdate"></v-combobox></v-col>
          </v-row>

          <!-- ═══ EXPORT INFORMATION PANEL ═══ -->
          <v-expand-transition>
            <div v-if="invForm.type_invoice==='export'" class="mt-3">
              <v-divider class="mb-3" color="#4643a0" thickness="2" opacity="0.4"></v-divider>
              <div class="text-subtitle-2 font-weight-bold mb-2" style="color:#4643a0"><v-icon size="18" class="mr-1">mdi-file-export-outline</v-icon>{{ $t('export_invoice_info') }}</div>

              <v-row dense>
                <!-- Incoterm -->
                <v-col cols="4">
                  <v-autocomplete v-model="invForm.incoterm" :items="['FOB','C&F','CIF']" :label="$t('select_incoterm')" variant="outlined" density="compact" rounded color="#4643a0" style="color: #4643a0" hide-details prepend-inner-icon="mdi-checkbox-marked-circle-outline"></v-autocomplete>
                </v-col>
                <!-- Currency -->
                <v-col cols="4">
                  <v-autocomplete v-model="invForm.currency" :items="['USD','EUR','GBP','MAD','XOF','GHS','NGN','ZAR','CNY','JPY']" :label="$t('select_currency')" variant="outlined" density="compact" rounded color="#4643a0" style="color: #4643a0" hide-details prepend-inner-icon="mdi-currency-usd"></v-autocomplete>
                </v-col>
                <!-- Total Gross Weight -->
                <v-col cols="4">
                  <v-text-field v-model.number="invForm.total_gross_weight" :label="$t('total_gross_weight')" variant="outlined" density="compact" rounded color="#4643a0" style="color: #4643a0" hide-details type="number" prepend-inner-icon="mdi-weight-kilogram"></v-text-field>
                </v-col>
              </v-row>

              <v-row dense>
                <v-col cols="4">
                  <v-text-field v-model="invForm.shipment_country" :label="$t('shipment_country')" variant="outlined" density="compact" rounded color="#4643a0" style="color: #4643a0" hide-details prepend-inner-icon="mdi-flag"></v-text-field>
                </v-col>
                <v-col cols="4">
                  <v-text-field v-model.number="invForm.tax_percentage" :label="$t('tax_percentage')" variant="outlined" density="compact" rounded color="#4643a0" style="color: #4643a0" hide-details type="number" min="0" max="100" suffix="%" prepend-inner-icon="mdi-percent"></v-text-field>
                </v-col>
              </v-row>

              <!-- Intermediary Info -->
              <div class="text-caption font-weight-bold mt-3 mb-1" style="color:#4643a0"><v-icon size="14" class="mr-1">mdi-domain</v-icon>{{ $t('intermediary_information') }}</div>
              <v-row dense>
                <v-col cols="4"><v-text-field v-model="invForm.intermediary_company" :label="$t('intermediary_company')" variant="outlined" density="compact" rounded color="#4643a0" style="color: #4643a0" hide-details></v-text-field></v-col>
                <v-col cols="4"><v-text-field v-model="invForm.intermediary_address" :label="$t('intermediary_address')" variant="outlined" density="compact" rounded color="#4643a0" style="color: #4643a0" hide-details></v-text-field></v-col>
                <v-col cols="4"><v-text-field v-model="invForm.intermediary_country" :label="$t('intermediary_country')" variant="outlined" density="compact" rounded color="#4643a0" style="color: #4643a0" hide-details></v-text-field></v-col>
              </v-row>

              <!-- Destination Info -->
              <div class="text-caption font-weight-bold mt-3 mb-1" style="color:#4643a0"><v-icon size="14" class="mr-1">mdi-map-marker</v-icon>{{ $t('destination_information') }}</div>
              <v-row dense>
                <v-col cols="4"><v-text-field v-model="invForm.destination_company" :label="$t('destination_company')" variant="outlined" density="compact" rounded color="#4643a0" style="color: #4643a0" hide-details></v-text-field></v-col>
                <v-col cols="4"><v-text-field v-model="invForm.destination_address" :label="$t('destination_address')" variant="outlined" density="compact" rounded color="#4643a0" style="color: #4643a0" hide-details></v-text-field></v-col>
                <v-col cols="4"><v-text-field v-model="invForm.destination_country" :label="$t('destination_country')" variant="outlined" density="compact" rounded color="#4643a0" style="color: #4643a0" hide-details></v-text-field></v-col>
              </v-row>

              <!-- Export Detail Lines -->
              <div class="text-caption font-weight-bold mt-3 mb-1" style="color:#4643a0"><v-icon size="14" class="mr-1">mdi-format-list-numbered</v-icon>{{ $t('export_details') }}</div>
              <div class="export-detail-table-wrap">
                <table class="export-detail-table">
                  <thead>
                    <tr>
                      <th>#</th>
                      <th>{{ $t('description') }}</th>
                      <th>{{ $t('container_or_truck_id') }}</th>
                      <th>{{ $t('weight_metric_ton') }}</th>
                      <th>{{ $t('unit_price_per_ton') }}</th>
                      <th>{{ $t('total_price') }} ({{ invForm.currency || '-' }})</th>
                      <th></th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(line, idx) in invForm.export_details" :key="idx">
                      <td class="text-center">{{ idx + 1 }}</td>
                      <td><v-text-field v-model="line.description" variant="plain" density="compact" hide-details class="export-input"></v-text-field></td>
                      <td><v-text-field v-model="line.container_or_truck_id" variant="plain" density="compact" hide-details class="export-input"></v-text-field></td>
                      <td><v-text-field v-model.number="line.weight_mt" variant="plain" density="compact" hide-details type="number" class="export-input" @update:model-value="calcDetailTotal(line)"></v-text-field></td>
                      <td><v-text-field v-model.number="line.unit_price_per_ton" variant="plain" density="compact" hide-details type="number" class="export-input" @update:model-value="calcDetailTotal(line)"></v-text-field></td>
                      <td class="text-right font-weight-bold">{{ main.formatDisplayNumber(calcDetailTotal(line)) }}</td>
                      <td><v-btn icon size="x-small" variant="text" color="error" @click="removeExportDetail(idx)"><v-icon size="16">mdi-close-circle</v-icon></v-btn></td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <v-btn variant="tonal" color="#4643a0" size="small" rounded class="mt-1" prepend-icon="mdi-plus" @click="addExportDetail">{{ $t('add_detail_line') }}</v-btn>

              <!-- Summary -->
              <v-row dense class="mt-2" v-if="invForm.export_details.length">
                <v-col cols="7"></v-col>
                <v-col cols="5">
                  <div class="d-flex justify-space-between text-body-2"><span>{{ $t('sub_total') }}:</span><b>{{ main.formatDisplayNumber(exportSubTotal()) }} {{ invForm.currency || '' }}</b></div>
                  <div class="d-flex justify-space-between text-body-2"><span>{{ $t('tax_percentage') }}:</span><b>{{ invForm.tax_percentage || 0 }}%</b></div>
                  <v-divider class="my-1"></v-divider>
                  <div class="d-flex justify-space-between text-subtitle-2 font-weight-bold" style="color:#4643a0"><span>{{ $t('grand_total') }}:</span><span>{{ main.formatDisplayNumber(exportGrandTotal()) }} {{ invForm.currency || '' }}</span></div>
                </v-col>
              </v-row>
            </div>
          </v-expand-transition>

          <v-row dense class="mt-3"><v-col cols="6"><v-btn block color="#4643a0" variant="flat" rounded size="small" :disabled="!invForm.trackingNumbers.length" @click="submitInvoice" prepend-icon="mdi-content-save">{{ $t('save') }}</v-btn></v-col><v-col cols="6"><v-btn block color="error" variant="outlined" rounded size="small" @click="cancelInvoice" prepend-icon="mdi-close-circle-outline">{{ $t('cancel') }}</v-btn></v-col></v-row>

          <div class="d-flex ga-4 mt-2 text-body-2 flex-wrap"><span><b>{{ $t('invoice_weight') }}:</b> {{ main.formatDisplayNumber(selectedSells.reduce((s,c)=>s+Number(c.total_weight||0),0)) }} Kg</span><span><b>{{ $t('total_price') }}:</b> {{ main.formatDisplayNumber(selectedSells.reduce((s,c)=>s+Number(c.total_price||0),0)) }} {{ main.currency }}</span></div>
        </div></v-expand-transition>
      </v-col>
    </v-row>
    <div class="d-flex ga-2 mb-3 justify-end">
      <v-btn v-if="Object.values(colFilters).some(f=>f&&f.length)" variant="tonal" color="warning" rounded size="small" prepend-icon="mdi-filter-off" @click="colFilters={}">{{ $t('clear_filters') }}</v-btn>
      <v-btn icon variant="tonal" color="#4643a0" size="small" @click="exportExcel"><v-icon size="18">mdi-file-excel-outline</v-icon></v-btn>
    </div>
    <!-- Table -->
    <div class="ls-table-wrap">
      <table class="ls-table">
        <thead class="ls-thead"><tr>
          <th class="ls-th ls-th-pin" style="min-width:50px;left:0"><v-checkbox-btn v-model="selectAll" color="#4643a0" density="compact" hide-details></v-checkbox-btn></th>
          <th v-for="h in headers" :key="h.key" class="ls-th" :class="{'ls-th-pin':isPinned(h.key),'ls-th-dragover':dragOverCol===h.key}" :style="colPinStyle(h.key,3)" :draggable="!h.fixed" @dragstart="onDragStart(h.key)" @dragover="onDragOver(h.key,$event)" @dragend="onDragEnd" @drop="onDragEnd">
            <div class="ls-th-inner">
              <v-icon v-if="!h.fixed" size="12" class="drag-handle" color="grey-lighten-1">mdi-drag-vertical</v-icon>
              <span class="ls-th-title" @click="h.sortable!==false&&toggleSort(h.key)">{{ h.title }}</span>
              <template v-if="h.key!=='actions'&&h.key!=='expand'">
                <v-btn v-if="sortDir(h.key)" icon variant="text" size="14" @click.stop="toggleSort(h.key)"><v-icon size="12" color="#4643a0">mdi-arrow-{{ sortDir(h.key)==='asc'?'up':'down' }}</v-icon></v-btn>
                <span v-if="sortIdx(h.key)" class="sort-badge-s">{{ sortIdx(h.key) }}</span>
                <v-menu v-model="filterMenus[h.key]" :close-on-content-click="false" location="bottom"><template #activator="{props}"><v-btn v-bind="props" icon variant="text" size="14"><v-icon size="12" :color="colFilters[h.key]&&colFilters[h.key].length?'success':'grey'">mdi-filter</v-icon></v-btn></template><v-card min-width="220" class="rounded-lg pa-2"><v-autocomplete v-model="colFilters[h.key]" :items="filterOpts(h.key)" density="compact" variant="outlined" color="#4643a0" hide-details multiple chips closable-chips :placeholder="$t('filter')" style="color: #4643a0"></v-autocomplete></v-card></v-menu>
                <v-btn icon variant="text" size="14" @click.stop="togglePin(h.key)"><v-icon size="10" :color="pinSide(h.key)==='left'?'warning':pinSide(h.key)==='right'?'blue':'grey'">{{ pinSide(h.key)==='left'?'mdi-pin':pinSide(h.key)==='right'?'mdi-pin-outline':'mdi-pin-off-outline' }}</v-icon></v-btn>
              </template>
            </div>
          </th>
        </tr></thead>
        <tbody>
          <template v-if="loading"><tr><td :colspan="headers.length+1" class="text-center pa-8"><v-progress-circular indeterminate color="#4643a0"></v-progress-circular></td></tr></template>
          <template v-else v-for="item in colFilteredItems" :key="item.id">
            <tr class="ls-row" :class="{'ls-row-expanded':expanded[item.id],'ls-row-invoiced':item._is_invoiced}">
              <td class="ls-td ls-th-pin" style="left:0"><v-checkbox-btn v-model="selected" :value="item.id" color="#4643a0" density="compact" hide-details></v-checkbox-btn></td>
              <td v-for="h in headers" :key="h.key" class="ls-td" :class="{'ls-col-pin':isPinned(h.key)}" :style="colPinStyle(h.key,2)">
                <template v-if="h.key==='expand'"><v-btn icon variant="text" size="24" @click="toggleExpand(item.id)"><v-icon size="16">{{ expanded[item.id]?'mdi-chevron-up':'mdi-chevron-down' }}</v-icon></v-btn></template>
                <template v-else-if="h.key==='date'">{{ main.formateDate(item.date) }}</template>
                <template v-else-if="h.key==='shipping_category_id'"><v-chip size="small" color="#4643a0" variant="tonal">{{ catName(item.shipping_category_id) }}</v-chip></template>
                <template v-else-if="h.key==='_tracking'"><div class="d-flex flex-wrap ga-1 justify-center"><v-chip v-for="tn in (item.shipping_tracking_number||[])" :key="tn" size="x-small" color="#4643a0" variant="tonal">{{ tn }}</v-chip><span v-if="!(item.shipping_tracking_number||[]).length" class="text-grey">-</span></div></template>
                <template v-else-if="h.key==='direction'"><v-chip v-if="item.direction" size="x-small" :color="item.direction==='local'?'info':'warning'" variant="tonal">{{ $t(item.direction) }}</v-chip><span v-else class="text-grey">-</span></template>
                <template v-else-if="h.key==='total_weight'">{{ main.formatDisplayNumber(item.total_weight) }} Kg</template>
                <template v-else-if="h.key==='total_price'">{{ main.formatDisplayNumber(item.total_price) }} {{ main.currency }}</template>
                <template v-else-if="h.key==='_invoice_state'">
                  <v-chip v-if="item._invoice_state" size="x-small" color="success" variant="tonal" prepend-icon="mdi-check-decagram" style="cursor:pointer" @click="$router.push({name:'view_client_invoice',query:{ids:[item.invoice_id]}})">{{ item._invoice_state }}</v-chip>
                  <v-chip v-else size="x-small" color="grey" variant="tonal">{{ $t('not_invoiced') }}</v-chip>
                </template>
                <template v-else-if="h.key==='actions'">
                  <v-btn icon size="x-small" variant="tonal" color="#4643a0" @click="$router.push({name:'view_shipping',params:{id:item.id}})" class="mr-1"><v-icon size="14">mdi-eye</v-icon></v-btn>
                  <v-btn icon size="x-small" variant="tonal" color="warning" @click="$router.push({name:'edit_shipping',params:{id:item.id}})" class="mr-1"><v-icon size="14">mdi-pencil</v-icon></v-btn>
                  <v-btn icon size="x-small" variant="tonal" color="error" @click="confirmDelete(item.id)"><v-icon size="14">mdi-delete</v-icon></v-btn>
                </template>
                <!-- origin -->
                <template v-else-if="h.key==='_origin'"><v-chip v-if="item._origin && item._origin!=='-'" size="x-small" color="amber-darken-2" variant="tonal" prepend-icon="mdi-map-marker">{{ item._origin }}</v-chip><span v-else class="text-grey">-</span></template>
                <!-- trucks -->
                <template v-else-if="h.key==='_trucks'"><div class="d-flex flex-wrap ga-1 justify-center"><v-chip v-for="t in (item._trucks && item._trucks!=='-' ? item._trucks.split(', ') : [])" :key="t" size="x-small" color="deep-orange" variant="tonal" prepend-icon="mdi-truck">{{ t }}</v-chip><span v-if="!item._trucks || item._trucks==='-'" class="text-grey">-</span></div></template>
                <!-- generic text columns -->
                <template v-else>{{ item[h.key] || '-' }}</template>
              </td>
            </tr>
            <tr v-if="expanded[item.id]"><td :colspan="headers.length+1" class="pa-0">
              <div class="ls-expand-content">
                <div v-for="(grp,gi) in groupDetails(item)" :key="gi" class="ls-detail-group mb-2">
                  <div class="ls-detail-group-header"><v-icon size="16" color="#4643a0" class="mr-1">mdi-tag</v-icon><b>{{ grp.category?grp.category[`name_${main.lang?.code}`]||grp.category.name:'-' }}</b></div>
                  <table class="ls-detail-table"><thead><tr><th>{{ $t('classification') }}</th><th>{{ $t('product') }}</th><th>{{ $t('product_family') }}</th><th>{{ $t('warehouse') }}</th><th>{{ $t('total_weight') }}</th><th>{{ $t('unit_price') }}</th><th>{{ $t('total_price') }}</th><th>{{ $t('package_type') }}</th><th>{{ $t('total_package_count') }}</th></tr></thead>
                  <tbody><tr v-for="dc in grp.items" :key="dc.id"><td><v-chip size="x-small" color="#4643a0" variant="tonal">{{ dc.stockable_type||$t('text') }}</v-chip></td><td class="font-weight-bold">{{ articleName(dc) }}</td><td>{{ articleFamily(dc) }}</td><td><span v-if="dc.warehouse">{{ dc.warehouse[`name_${main.lang?.code}`]||dc.warehouse.name }}</span><span v-else>{{ $t('non_stockable') }}</span></td><td>{{ main.formatDisplayNumber(dc.weight) }}</td><td>{{ main.formatDisplayNumber(dc.unit_price) }}</td><td>{{ main.formatDisplayNumber(dc.total_price) }}</td><td>{{ dc.package_type?dc.package_type.name:'N/A' }}</td><td>{{ dc.nbr_box||'-' }}</td></tr></tbody>
                  <tfoot><tr class="ls-detail-foot"><td colspan="4" class="text-right font-weight-bold">{{ $t('total') }}:</td><td class="font-weight-bold">{{ main.formatDisplayNumber(grp.totalW) }}</td><td></td><td class="font-weight-bold">{{ main.formatDisplayNumber(grp.totalP) }}</td><td colspan="2"></td></tr></tfoot></table>
                </div>
              </div>
            </td></tr>
          </template>
        </tbody>
        <tfoot class="ls-tfoot"><tr><td :colspan="headers.length-1"></td><td class="text-center font-weight-bold">Σ = {{ main.formatDisplayNumber(totalWeight) }}</td><td class="text-center font-weight-bold">Σ = {{ main.formatDisplayNumber(totalPrice) }}</td><td></td></tr></tfoot>
      </table>
    </div>
  </v-card-text>
  <!-- TAB: Add Sell -->
  <v-card-text v-else-if="activeTab === 1" class="pa-0">
    <AddShipping />
  </v-card-text>
  <v-dialog v-model="deleteDialog" max-width="450"><v-card class="rounded-xl"><v-card-title class="pa-5 pb-2 d-flex align-center"><v-icon color="error" class="mr-2">mdi-alert-circle-outline</v-icon>{{ $t('confirm_delete') }}</v-card-title><v-card-text class="pa-5 pt-2">{{ deleteBulk?$t('confirm_bulk_delete_message',{count:selected.length}):$t('confirm_delete_sell_message') }}</v-card-text><v-card-actions class="pa-5 pt-0"><v-spacer></v-spacer><v-btn @click="deleteDialog=false" variant="text">{{ $t('cancel') }}</v-btn><v-btn color="error" variant="flat" rounded @click="execDelete" :loading="deleting">{{ $t('delete') }}</v-btn></v-card-actions></v-card></v-dialog>
</v-card>
</template>
<script>
import script from './script.js';
import AddShipping from '../AddShipping/index.vue';
export default {
  ...script,
  components:{...script.components,AddShipping},
  data(){return{...script.data.call(this),activeTab:0,sellTabs:[{value:0,label:'list_shipping',icon:'mdi-view-list-outline'},{value:1,label:'add_shipping',icon:'mdi-sticker-plus-outline'}]}},
  computed:{...script.computed,selectAll:{get(){return this.selected.length===this.colFilteredItems.length&&this.colFilteredItems.length>0},set(v){this.selected=v?this.colFilteredItems.map(i=>i.id):[]}}},
  methods:{...script.methods,colPinStyle(key,z){const side=this.pinSide(key);if(!side)return{};if(side==='left')return{position:'sticky',left:this.getPinLeft(key)+'px',zIndex:z,background:'#F5F5F5'};return{position:'sticky',right:this.getPinRight(key)+'px',zIndex:z,background:'#F5F5F5'};},measureColWidths(){this.$nextTick(()=>{const ths=this.$refs.tableWrap?.querySelectorAll('thead th');if(!ths)return;const w={};ths.forEach((th,i)=>{const key=i===0?'__checkbox':this.headers[i-1]?.key;if(key)w[key]=th.offsetWidth});this.colWidths=w})}},
  watch:{...script.watch,headers(){this.measureColWidths()},pinState:{handler(){this.measureColWidths()},deep:true},activeTab(v){const q=v===1?{tab:'add'}:{};if(JSON.stringify(this.$route.query)!==JSON.stringify(q))this.$router.replace({query:q}).catch(()=>{});if(v===0)this.fetchShippings()}},
  mounted(){script.mounted.call(this);this.$nextTick(()=>this.measureColWidths());if(this.$route.query.tab==='add')this.activeTab=1},
  beforeUnmount(){if(script.beforeUnmount)script.beforeUnmount.call(this)},
  updated(){this.measureColWidths()},
};
</script>
<style scoped>
.ls-card{border-radius:20px!important;font-family:'Inter',sans-serif}
.ls-tab-slider{border-radius:12px}
.ls-hero{background:linear-gradient(135deg,#372e7d 0%,#4643a0 40%,#6c66bb 100%);border-radius:inherit;border-bottom-left-radius:0;border-bottom-right-radius:0;min-height:100px}
.ls-hero-inner{position:relative;z-index:1;padding:22px 26px;color:#fff;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px}
.ls-hero-icon{width:52px;height:52px;border-radius:14px;background:rgba(255,255,255,.18);display:flex;align-items:center;justify-content:center}
.ls-hero-title{font-size:19px;font-weight:800}.ls-hero-actions{display:flex;gap:8px;flex-wrap:wrap}
.adv-panel{border:1.5px solid rgba(70,67,160,.15);background:rgba(70,67,160,.03)}
.inv-panel{border:1.5px solid rgba(0,150,136,.15);background:rgba(0,150,136,.03)}
.ls-table-wrap{overflow:auto;max-height:calc(100vh - 300px);border-radius:12px;border:1px solid rgba(var(--v-border-color),.12)}
.ls-table{width:100%;border-collapse:collapse;font-size:13px}
.ls-thead{position:sticky;top:0;z-index:4;background:rgb(var(--v-theme-surface))}
.ls-th{padding:10px 12px;text-align:center;white-space:nowrap;border-bottom:2px solid rgba(var(--v-border-color),.12);background:rgb(var(--v-theme-surface));transition:background .15s}
.ls-th-pin{position:sticky!important;z-index:5!important;background:#F5F5F5!important;box-shadow:2px 0 4px -2px rgba(0,0,0,.1)}
.ls-th-dragover{background:rgba(70,67,160,.08)!important}
.ls-th-inner{display:flex;align-items:center;justify-content:center;gap:2px}
.ls-th-title{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.3px;opacity:.7;cursor:pointer}
.drag-handle{cursor:grab;opacity:.5;transition:opacity .15s}.drag-handle:hover{opacity:1}
.sort-badge-s{font-size:9px;background:#4643a0;color:#fff;border-radius:50%;width:14px;height:14px;display:inline-flex;align-items:center;justify-content:center}
.ls-td{padding:8px 12px;text-align:center;border-bottom:1px solid rgba(var(--v-border-color),.06);white-space:nowrap}
.ls-col-pin{position:sticky!important;z-index:2!important;background:#F5F5F5!important;box-shadow:2px 0 4px -2px rgba(0,0,0,.1)}
.ls-row:hover{background:rgba(70,67,160,.04)}.ls-row-expanded{background:rgba(70,67,160,.06)}
.ls-row-invoiced{background:rgba(76,175,80,.06)}.ls-row-invoiced:hover{background:rgba(76,175,80,.1)}.ls-row-invoiced td{border-bottom-color:rgba(76,175,80,.12)}
.ls-tfoot td{padding:10px 12px;border-top:2px solid rgba(70,67,160,.2);color:#4643a0;font-size:14px}
.ls-expand-content{padding:12px 24px;background:rgba(var(--v-border-color),.03)}
.ls-detail-group{border:1px solid rgba(var(--v-border-color),.12);border-radius:10px;overflow:hidden}
.ls-detail-group-header{padding:8px 14px;background:rgba(70,67,160,.06);font-size:13px}
.ls-detail-table{width:100%;border-collapse:collapse;font-size:12px}
.ls-detail-table th{padding:6px 10px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.3px;opacity:.6;text-align:center;border-bottom:1px solid rgba(var(--v-border-color),.1)}
.ls-detail-table td{padding:6px 10px;text-align:center;border-bottom:1px solid rgba(var(--v-border-color),.05)}
.ls-detail-foot td{padding:8px 10px;background:rgba(70,67,160,.04);border-top:1px solid rgba(70,67,160,.15)}
.ls-panel-card{border:1.5px solid rgba(70,67,160,.12);border-radius:14px;padding:12px;background:rgba(var(--v-theme-surface),1)}
.ls-panel-header{font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;opacity:.7;margin-bottom:8px;display:flex;align-items:center}
.ls-stats-panel{max-height:400px}
.ls-stat-item{padding:8px 10px;border-radius:10px;background:rgba(70,67,160,.03);border:1px solid rgba(70,67,160,.06)}
.export-detail-table-wrap{overflow-x:auto;border:1px solid rgba(70,67,160,.15);border-radius:10px;margin-top:4px}
.export-detail-table{width:100%;border-collapse:collapse;font-size:12px}
.export-detail-table thead{background:rgba(70,67,160,.08)}
.export-detail-table th{padding:6px 10px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.3px;color:#4643a0;text-align:center;border-bottom:1.5px solid rgba(70,67,160,.2);white-space:nowrap}
.export-detail-table td{padding:4px 8px;text-align:center;border-bottom:1px solid rgba(70,67,160,.06)}
.export-detail-table tbody tr:hover{background:rgba(70,67,160,.04)}
.export-input .v-field__input{font-size:12px;min-height:28px!important;padding:2px 6px!important}
</style>



