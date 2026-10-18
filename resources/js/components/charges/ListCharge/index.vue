<template>
<v-card class="lc-card" elevation="0">
  <!-- Hero -->
  <div class="lc-hero">
    <div class="lc-hero-inner">
      <div class="d-flex align-center ga-4">
        <div class="lc-hero-icon"><v-icon size="28" color="white">mdi-truck-cargo-container</v-icon></div>
        <div>
          <div class="lc-hero-title">{{ $t('charges') }}</div>
          <div class="text-white text-caption" style="opacity:.8">{{ $t('datatable_charges') }}</div>
        </div>
      </div>

      <!-- Live KPI Badges -->
      <div class="d-flex align-center ga-2 flex-wrap">
        <div class="lc-hero-kpi">
          <v-icon size="14" color="white" class="mr-1">mdi-counter</v-icon>
          <span class="lc-kpi-val">{{ colFilteredItems.length }}</span>
          <span class="lc-kpi-lbl">{{ $t('records') }}</span>
        </div>
        <div class="lc-hero-kpi">
          <v-icon size="14" color="white" class="mr-1">mdi-weight-kilogram</v-icon>
          <span class="lc-kpi-val">{{ main.formatDisplayNumber(totalWeight) }}</span>
          <span class="lc-kpi-lbl">Kg</span>
        </div>
        <div class="lc-hero-kpi">
          <v-icon size="14" color="white" class="mr-1">mdi-cash</v-icon>
          <span class="lc-kpi-val">{{ main.formatDisplayNumber(totalPrice) }}</span>
          <span class="lc-kpi-lbl">{{ main.currency }}</span>
        </div>
        <div class="lc-hero-kpi" v-if="totalInvoiced > 0">
          <v-icon size="14" color="#81C784" class="mr-1">mdi-check-decagram</v-icon>
          <span class="lc-kpi-val">{{ totalInvoiced }}</span>
          <span class="lc-kpi-lbl">{{ $t('invoiced') }}</span>
        </div>
      </div>

      <div class="lc-hero-actions">
        <v-btn variant="tonal" color="white" rounded size="small" prepend-icon="mdi-refresh" @click="fetchCharges" :loading="loading">{{ $t('refresh') }}</v-btn>
        <v-btn v-if="selected.length && auth.hasPermission('charges.delete')" variant="tonal" color="error" rounded size="small" prepend-icon="mdi-delete" @click="confirmBulkDelete">{{ $t('delete_selected') }} ({{ selected.length }})</v-btn>
      </div>
    </div>
  </div>

  <!-- Tab Slider (Matching Activity Breakdown Entity Toggle) -->
  <div class="d-flex align-center justify-space-between mx-4 mt-3">
    <SegmentedPillToggle
      v-model="activeTab"
      :items="chargeTabs.map(t => ({ value: t.value, label: $t(t.label), icon: t.icon }))"
      color="#1976D2"
    />
  </div>

  <!-- TAB: List Charge -->
  <v-card-text v-if="activeTab === 0" class="pa-4">
    <!-- Analytics Row: Calendar | Chart | Statistics -->
    <v-row dense class="mb-3">
      <!-- Calendar (left) -->
      <v-col cols="12" md="3">
        <div class="lc-panel-card">
          <div class="lc-panel-header"><v-icon size="16" color="#42A5F5" class="mr-1">mdi-calendar-range</v-icon><span>{{ $t('date_range') }}</span></div>
          <DatePicker v-model.range="dateRangeVC" :is-dark="main.is_dark" :locale="main.lang?.short||'en'" mode="date" color="blue" :max-date="new Date()" expanded @update:model-value="onDateRangeChange" />
        </div>
      </v-col>
      <!-- Chart (center) -->
      <v-col cols="12" md="6">
        <div class="lc-panel-card" style="height:100%">
          <div class="lc-panel-header d-flex align-center justify-space-between">
            <div class="d-flex align-center"><v-icon size="16" color="#42A5F5" class="mr-1">mdi-chart-areaspline</v-icon><span>{{ $t('analytics') }}</span></div>
            <v-btn-toggle v-model="chartMode" mandatory density="compact" color="#42A5F5" variant="outlined" rounded="lg" divided>
              <v-btn value="daily" size="x-small">{{ $t('daily') }}</v-btn>
              <v-btn value="monthly" size="x-small">{{ $t('monthly') }}</v-btn>
              <v-btn value="yearly" size="x-small">{{ $t('yearly') }}</v-btn>
            </v-btn-toggle>
          </div>
          <apexchart v-if="chartSeries[0].data.length" type="line" :height="260" :options="chartOptions" :series="chartSeries"></apexchart>
          <div v-else class="d-flex align-center justify-center" style="height:220px;opacity:.4"><v-icon size="48" color="grey">mdi-chart-line-variant</v-icon></div>
        </div>
      </v-col>
      <!-- Statistics (right) -->
      <v-col cols="12" md="3">
        <div class="lc-panel-card lc-stats-panel" style="height:100%;overflow-y:auto">
          <div class="lc-panel-header"><v-icon size="16" color="#42A5F5" class="mr-1">mdi-chart-box-outline</v-icon><span>{{ $t('statistics') }}</span></div>
          <!-- Per-category stats -->
          <div v-for="(stat,si) in categoryStats" :key="si" class="lc-stat-item mb-2">
            <div class="d-flex align-center justify-space-between mb-1">
              <v-chip size="x-small" color="#42A5F5" variant="tonal" class="font-weight-bold">{{ stat.name }} ({{ stat.count }})</v-chip>
              <v-chip size="x-small" color="#42A5F5" variant="elevated" class="font-weight-bold">{{ totalWeight>0?Math.round(stat.weight/totalWeight*1000)/10:0 }}%</v-chip>
            </div>
            <v-progress-linear :model-value="totalWeight>0?(stat.weight/totalWeight*100):0" color="#42A5F5" bg-color="blue-lighten-4" height="5" rounded class="mb-1"></v-progress-linear>
            <div class="d-flex flex-column ga-1 text-caption">
              <div class="d-flex align-center ga-1"><v-icon size="12" color="#42A5F5">mdi-weight-kilogram</v-icon><span class="text-medium-emphasis">{{ $t('weight') }}:</span><b style="color: #42A5F5">{{ main.formatDisplayNumber(stat.weight) }} Kg</b></div>
              <div class="d-flex align-center ga-1"><v-icon size="12" color="teal">mdi-scale-balance</v-icon><span class="text-medium-emphasis">{{ $t('total_weight_invoice') }}:</span><b class="text-teal">{{ main.formatDisplayNumber(stat.weightInvoice) }} Kg</b></div>
              <div class="d-flex align-center ga-1"><v-icon size="12" color="deep-orange">mdi-cash</v-icon><span class="text-medium-emphasis">{{ $t('total_price') }}:</span><b class="text-deep-orange">{{ main.formatDisplayNumber(stat.price) }} {{ main.currency }}</b></div>
            </div>
          </div>
          <v-divider class="my-2"></v-divider>
          <!-- Invoice stats -->
          <div class="d-flex flex-column ga-1">
            <div class="d-flex align-center ga-2"><v-icon size="14" color="success">mdi-check-decagram</v-icon><span class="text-caption text-medium-emphasis">{{ $t('invoiced') }}:</span><v-chip size="x-small" color="success" variant="tonal"><b>{{ totalInvoiced }}</b></v-chip></div>
            <div class="d-flex align-center ga-2"><v-icon size="14" color="grey">mdi-close-circle-outline</v-icon><span class="text-caption text-medium-emphasis">{{ $t('not_invoiced') }}:</span><v-chip size="x-small" color="grey" variant="tonal"><b>{{ totalNotInvoiced }}</b></v-chip></div>
          </div>
          <!-- Totals -->
          <v-divider class="my-2"></v-divider>
          <div class="d-flex flex-column ga-1">
            <div class="d-flex align-center ga-1"><v-icon size="14" color="#42A5F5">mdi-sigma</v-icon><span class="text-caption font-weight-bold">{{ $t('total_weight') }}:</span><b style="color: #42A5F5" class="text-caption">{{ main.formatDisplayNumber(totalWeight) }} Kg</b></div>
            <div class="d-flex align-center ga-1"><v-icon size="14" color="teal">mdi-sigma</v-icon><span class="text-caption font-weight-bold">{{ $t('total_weight_invoice') }}:</span><b class="text-teal text-caption">{{ main.formatDisplayNumber(totalWeightInvoice) }} Kg</b></div>
            <div class="d-flex align-center ga-1"><v-icon size="14" color="deep-orange">mdi-sigma</v-icon><span class="text-caption font-weight-bold">{{ $t('total_price') }}:</span><b class="text-deep-orange text-caption">{{ main.formatDisplayNumber(totalPrice) }} {{ main.currency }}</b></div>
          </div>
        </div>
      </v-col>
    </v-row>

    <!-- Unified Command & Filter Toolbar -->
    <div class="lc-toolbar mb-3 pa-2 rounded-xl">
      <v-row dense align="center">
        <!-- Quick Search Field -->
        <v-col cols="12" md="4" sm="6">
          <v-text-field
            v-model="searchQuery"
            density="compact"
            variant="outlined"
            rounded
            hide-details
            clearable
            color="#42A5F5"
            class="text-charge"
            prepend-inner-icon="mdi-magnify"
            :placeholder="$t('search_records_placeholder') || 'Search tracking #, supplier, origin...'"
          ></v-text-field>
        </v-col>

        <!-- Filter Toggle & Invoice Workflow -->
        <v-col cols="12" md="5" sm="6" class="d-flex align-center ga-2 flex-wrap">
          <v-btn
            :variant="showAdvanced ? 'flat' : 'tonal'"
            :color="showAdvanced ? '#42A5F5' : undefined"
            rounded
            size="small"
            class="lc-filter-btn"
            @click="showAdvanced = !showAdvanced"
          >
            <v-icon size="16" class="mr-1">{{ showAdvanced ? 'mdi-chevron-up' : 'mdi-filter-variant' }}</v-icon>
            {{ $t('advanced_filters') }}
            <v-badge
              v-if="activeFilterCount"
              :content="activeFilterCount"
              color="#FF7043"
              inline
              class="ml-1"
            ></v-badge>
          </v-btn>

          <v-btn
            :variant="showInvoice ? 'flat' : 'tonal'"
            :color="showInvoice ? '#00897B' : undefined"
            rounded
            size="small"
            class="lc-filter-btn"
            :disabled="!canSetupInvoice"
            @click="showInvoice = !showInvoice"
          >
            <v-icon size="16" class="mr-1">mdi-receipt-text-outline</v-icon>
            {{ $t('supplier_invoices') }}
            <v-badge
              v-if="uninvoicedSelectedCharges.length"
              :content="uninvoicedSelectedCharges.length"
              color="#00897B"
              inline
              class="ml-1"
            ></v-badge>
          </v-btn>

          <v-btn
            v-if="activeFilterCount > 0"
            variant="text"
            color="warning"
            rounded
            size="small"
            prepend-icon="mdi-filter-off"
            @click="clearAllFilters"
          >
            {{ $t('clear_filters') }}
          </v-btn>
        </v-col>

        <!-- Right: Actions (Records Counter & Excel) -->
        <v-col cols="12" md="3" sm="12" class="d-flex justify-end align-center ga-2">
          <v-chip size="small" variant="tonal" color="#42A5F5" class="font-weight-medium">
            {{ colFilteredItems.length }} / {{ charges.length }} {{ $t('records') }}
          </v-chip>
          <v-btn
            icon
            variant="tonal"
            color="#4CAF50"
            size="small"
            :title="$t('export_excel')"
            @click="exportExcel"
          >
            <v-icon size="18">mdi-file-excel-outline</v-icon>
          </v-btn>
        </v-col>
      </v-row>

      <!-- Advanced Filters Expandable Panel -->
      <v-expand-transition>
        <div v-if="showAdvanced" class="adv-panel pa-3 mt-2 rounded-xl">
          <v-row dense>
            <v-col cols="12" sm="6" md="3"><v-autocomplete v-model="advFilter.articleType" :items="[{title:$t('text'),value:'text'},{title:'FishType',value:'FishType'},{title:'GuanoType',value:'GuanoType'},{title:'PackageType',value:'PackageType'}]" :label="$t('article_type')" variant="outlined" density="compact" rounded color="#42A5F5" class="text-charge" hide-details clearable></v-autocomplete></v-col>
            <v-col v-if="advFilter.articleType==='FishType'" cols="12" sm="6" md="3"><v-autocomplete v-model="advFilter.fishFamilies" :items="fishFamilies.map(f=>({title:f[`name_${main.lang?.code}`]||f.name,value:f.id}))" :label="$t('fish_family')" variant="outlined" density="compact" rounded color="#42A5F5" class="text-charge" hide-details clearable multiple chips closable-chips></v-autocomplete></v-col>
            <v-col v-if="advFilter.articleType==='FishType'" cols="12" sm="6" md="3"><v-autocomplete v-model="advFilter.fishFreshFrozen" :items="['fresh','frozen']" :label="$t('fish_type_fresh_frozen')" variant="outlined" density="compact" rounded color="#42A5F5" class="text-charge" hide-details clearable multiple chips closable-chips></v-autocomplete></v-col>
            <v-col v-if="advFilter.articleType==='FishType'" cols="12" sm="6" md="3"><v-autocomplete v-model="advFilter.fishProcessTypes" :items="fishProcessTypes.map(f=>({title:f[`name_${main.lang?.code}`]||f.name,value:f.id}))" :label="$t('fish_process_type')" variant="outlined" density="compact" rounded color="#42A5F5" class="text-charge" hide-details clearable multiple chips closable-chips></v-autocomplete></v-col>
            <v-col v-if="advFilter.articleType==='GuanoType'" cols="12" sm="6" md="3"><v-autocomplete v-model="advFilter.guanoTypes" :items="guanoTypes.map(g=>({title:g[`name_${main.lang?.code}`]||g.name,value:g.id}))" :label="$t('guano_type')" variant="outlined" density="compact" rounded color="#42A5F5" class="text-charge" hide-details clearable multiple chips closable-chips></v-autocomplete></v-col>
            <v-col v-if="advFilter.articleType==='PackageType'" cols="12" sm="6" md="3"><v-autocomplete v-model="advFilter.packageTypes" :items="packageTypes.map(p=>({title:p[`name_${main.lang?.code}`]||p.name,value:p.id}))" :label="$t('package_type')" variant="outlined" density="compact" rounded color="#42A5F5" class="text-charge" hide-details clearable multiple chips closable-chips></v-autocomplete></v-col>
            <v-col cols="12" sm="6" md="3"><v-autocomplete v-model="advFilter.warehouses" :items="warehouses.map(w=>({title:w[`name_${main.lang?.code}`]||w.name,value:w.id}))" :label="$t('warehouse')" variant="outlined" density="compact" rounded color="#42A5F5" class="text-charge" hide-details clearable multiple chips closable-chips></v-autocomplete></v-col>
          </v-row>
        </div>
      </v-expand-transition>

      <!-- Invoice Setup Expandable Panel -->
      <v-expand-transition>
        <div v-if="showInvoice&&uninvoicedSelectedCharges.length" class="inv-panel pa-3 mt-2 rounded-xl">
          <v-row dense>
            <v-col cols="12" sm="6" md="3">
              <DatePicker v-model="invForm.date" :is-dark="main.is_dark" :locale="main.lang?.short||'en'" mode="date" color="teal" :disabled-dates="invDisabledDates" :min-date="invMinDate" :max-date="invMaxDate">
                <template #default="{ togglePopover, inputValue }">
                  <v-text-field :model-value="inputValue" :label="$t('invoice_date')" variant="outlined" density="compact" rounded color="teal" class="text-teal" hide-details readonly prepend-inner-icon="mdi-calendar" @click="togglePopover"></v-text-field>
                </template>
              </DatePicker>
            </v-col>
            <v-col cols="12" sm="6" md="3"><v-text-field :model-value="invoiceSuppliers.join(', ')" :label="$t('supplier')" variant="outlined" density="compact" rounded color="teal" class="text-teal" hide-details readonly></v-text-field></v-col>
            <v-col cols="12" sm="6" md="3"><v-autocomplete v-model="invForm.type_invoice" :items="[{title:$t('local'),value:'local'},{title:$t('export_type'),value:'export'}]" :label="$t('invoice_type')" variant="outlined" density="compact" rounded color="teal" class="text-teal" hide-details @update:model-value="onTypeInvoiceChange"></v-autocomplete></v-col>
            <v-col cols="12" sm="6" md="3"><v-combobox v-model="invForm.trackingNumbers" :label="$t('invoice_tracking_number')" variant="outlined" density="compact" rounded color="teal" class="text-teal" hide-details multiple chips closable-chips :loading="invChecking" prepend-inner-icon="mdi-barcode" @update:model-value="onTrackingUpdate"></v-combobox></v-col>
          </v-row>
          <div class="d-flex align-center justify-space-between mt-3 flex-wrap ga-2">
            <div class="d-flex ga-4 text-body-2 flex-wrap"><span><b>{{ $t('invoice_weight') }}:</b> {{ main.formatDisplayNumber(uninvoicedSelectedCharges.reduce((s,c)=>s+Number(c.total_weight||0),0)) }} Kg</span><span><b>{{ $t('total_price') }}:</b> {{ main.formatDisplayNumber(uninvoicedSelectedCharges.reduce((s,c)=>s+Number(c.total_price||0),0)) }} {{ main.currency }}</span></div>
            <div class="d-flex ga-2">
              <v-btn color="error" variant="outlined" rounded size="small" @click="cancelInvoice" prepend-icon="mdi-close-circle-outline">{{ $t('cancel') }}</v-btn>
              <v-btn color="teal" variant="flat" rounded size="small" :disabled="!invForm.trackingNumbers.length" @click="submitInvoice" prepend-icon="mdi-content-save">{{ $t('save') }}</v-btn>
            </div>
          </div>
        </div>
      </v-expand-transition>
    </div>

    <!-- Data Table -->
    <div class="lc-table-wrap" ref="tableWrap">
      <table class="lc-table">
        <thead class="lc-thead"><tr>
          <th class="lc-th lc-th-pin" style="min-width:50px;left:0"><v-checkbox-btn v-model="selectAll" color="#42A5F5" density="compact" hide-details></v-checkbox-btn></th>
          <th v-for="h in headers" :key="h.key" class="lc-th" :class="{'lc-th-pin':isPinned(h.key),'lc-th-dragover':dragOverCol===h.key}" :style="colPinStyle(h.key,3)" :draggable="!h.fixed" @dragstart="onDragStart(h.key)" @dragover="onDragOver(h.key,$event)" @dragend="onDragEnd" @drop="onDragEnd">
            <div class="lc-th-inner">
              <v-icon v-if="!h.fixed" size="12" class="drag-handle" color="grey-lighten-1">mdi-drag-vertical</v-icon>
              <span class="lc-th-title" @click="h.sortable!==false&&toggleSort(h.key)">{{ h.title }}</span>
              <template v-if="h.key!=='actions'&&h.key!=='expand'">
                <v-btn v-if="sortDir(h.key)" icon variant="text" size="14" @click.stop="toggleSort(h.key)"><v-icon size="12" color="#42A5F5">mdi-arrow-{{ sortDir(h.key)==='asc'?'up':'down' }}</v-icon></v-btn>
                <span v-if="sortIdx(h.key)" class="sort-badge">{{ sortIdx(h.key) }}</span>
                <v-menu v-model="filterMenus[h.key]" :close-on-content-click="false" location="bottom"><template #activator="{props}"><v-btn v-bind="props" icon variant="text" size="14"><v-icon size="12" :color="colFilters[h.key]&&colFilters[h.key].length?'#42A5F5':'grey'">mdi-filter</v-icon></v-btn></template><v-card min-width="220" class="rounded-lg pa-2"><v-autocomplete v-model="colFilters[h.key]" :items="filterOpts(h.key)" density="compact" variant="outlined" color="#42A5F5" hide-details multiple chips closable-chips :placeholder="$t('filter')" class="text-charge"><template #chip="{item,props:p}"><v-chip v-bind="p" size="x-small" color="#42A5F5" variant="tonal" closable>{{item.title}}</v-chip></template></v-autocomplete></v-card></v-menu>
                <v-btn icon variant="text" size="14" @click.stop="togglePin(h.key)"><v-icon size="10" :color="pinSide(h.key)==='left'?'warning':pinSide(h.key)==='right'?'blue':'grey'">{{ pinSide(h.key)==='left'?'mdi-pin':pinSide(h.key)==='right'?'mdi-pin-outline':'mdi-pin-off-outline' }}</v-icon></v-btn>
              </template>
            </div>
          </th>
        </tr></thead>
        <tbody>
          <template v-if="loading"><tr><td :colspan="headers.length+1" class="text-center pa-8"><v-progress-circular indeterminate color="#42A5F5"></v-progress-circular></td></tr></template>
          <template v-else v-for="item in colFilteredItems" :key="item.id">
            <tr class="lc-row" :class="{'lc-row-expanded':expanded[item.id],'lc-row-invoiced':item._is_invoiced}">
              <td class="lc-td lc-th-pin" style="left:0"><v-checkbox-btn v-model="selected" :value="item.id" color="#42A5F5" density="compact" hide-details></v-checkbox-btn></td>
              <td v-for="h in headers" :key="h.key" class="lc-td" :class="{'lc-col-pin':isPinned(h.key)}" :style="colPinStyle(h.key,2)">
                <!-- expand -->
                <template v-if="h.key==='expand'"><v-btn icon variant="text" size="24" @click="toggleExpand(item.id)"><v-icon size="16">{{ expanded[item.id]?'mdi-chevron-up':'mdi-chevron-down' }}</v-icon></v-btn></template>
                <!-- date -->
                <template v-else-if="h.key==='date'">{{ main.formateDate(item.date) }}</template>
                <!-- category -->
                <template v-else-if="h.key==='charge_category_id'"><v-chip size="small" color="info" variant="tonal">{{ catName(item.charge_category_id) }}</v-chip></template>
                <!-- tracking chips -->
                <template v-else-if="h.key==='_tracking'"><div class="d-flex flex-wrap ga-1 justify-center"><v-chip v-for="tn in (item.reception_tracking_number||[])" :key="tn" size="x-small" color="teal" variant="tonal">{{ tn }}</v-chip><span v-if="!(item.reception_tracking_number||[]).length" class="text-grey">-</span></div></template>
                <!-- onp tracking chips -->
                <template v-else-if="h.key==='_onp_tracking'"><div class="d-flex flex-wrap ga-1 justify-center"><v-chip v-for="tn in (item.tracking_onp_numbers||[])" :key="tn" size="x-small" color="deep-orange" variant="tonal">{{ tn }}</v-chip><span v-if="!(item.tracking_onp_numbers||[]).length" class="text-grey">-</span></div></template>
                <!-- weight/price -->
                <template v-else-if="h.key==='total_weight'">{{ main.formatDisplayNumber(item.total_weight) }} Kg</template>
                <template v-else-if="h.key==='_total_weight_invoice'">{{ main.formatDisplayNumber(item._total_weight_invoice) }} Kg</template>
                <template v-else-if="h.key==='total_price'">{{ main.formatDisplayNumber(item.total_price) }} {{ main.currency }}</template>
                <!-- invoice state -->
                <template v-else-if="h.key==='_invoice_state'">
                  <v-chip v-if="item._is_invoiced" size="x-small" color="success" variant="tonal" prepend-icon="mdi-check-circle-outline">{{ item._invoice_state }}</v-chip>
                  <v-chip v-else size="x-small" color="grey" variant="tonal" prepend-icon="mdi-close-circle-outline">{{ $t('not_invoiced') }}</v-chip>
                </template>
                <!-- qc status -->
                <template v-else-if="h.key==='_qc_status'">
                  <template v-if="catHasQualityControl(item)">
                    <v-chip v-if="item._qc_status==='passed'" size="x-small" color="success" variant="tonal" prepend-icon="mdi-check-circle" @click="openQcDialog(item)" style="cursor:pointer">{{ $t('qc_passed') }}</v-chip>
                    <v-chip v-else-if="item._qc_status==='failed'" size="x-small" color="error" variant="tonal" prepend-icon="mdi-close-circle" @click="openQcDialog(item)" style="cursor:pointer">{{ $t('qc_failed') }}</v-chip>
                    <v-chip v-else-if="item._qc_status==='pending'" size="x-small" color="warning" variant="tonal" prepend-icon="mdi-clock-outline" @click="openQcDialog(item)" style="cursor:pointer">{{ $t('qc_pending') }}</v-chip>
                    <v-btn v-else icon size="x-small" variant="tonal" color="#00838F" @click="openQcDialog(item)"><v-icon size="14">mdi-clipboard-plus-outline</v-icon></v-btn>
                  </template>
                  <span v-else class="text-grey">-</span>
                </template>
                <!-- actions -->
                <template v-else-if="h.key==='actions'">
                  <v-btn icon size="x-small" variant="tonal" color="info" @click="$router.push({name:'view_charge',params:{id:item.id}})" class="mr-1"><v-icon size="14">mdi-eye</v-icon></v-btn>
                  <v-btn v-if="auth.hasPermission('charges.edit')" icon size="x-small" variant="tonal" color="warning" @click="$router.push({name:'edit_charge',params:{id:item.id}})" class="mr-1" :disabled="item._is_invoiced"><v-icon size="14">mdi-pencil</v-icon></v-btn>
                  <v-btn v-if="auth.hasPermission('charges.delete')" icon size="x-small" variant="tonal" color="error" @click="confirmDelete(item.id)" class="mr-1" :disabled="item._is_invoiced"><v-icon size="14">mdi-delete</v-icon></v-btn>
                  <v-btn icon size="x-small" variant="tonal" color="blue-grey" @click="openAuditLog(item.id)" class="mr-1"><v-icon size="14">mdi-history</v-icon><v-tooltip activator="parent" location="top">{{ $t('audit_log') }}</v-tooltip></v-btn>
                  <v-btn icon size="x-small" variant="tonal" :color="(item._open_issues||0) > 0 ? 'error' : '#42A5F5'" @click="openIssueDialog(item.id)" class="mr-1"><v-badge v-if="(item._open_issues||0) > 0" :content="item._open_issues" color="error" floating><v-icon size="14">mdi-flag-outline</v-icon></v-badge><v-icon v-else size="14">mdi-flag-outline</v-icon><v-tooltip activator="parent" location="top">{{ $t('record_issues') }}</v-tooltip></v-btn>
                  <template v-if="catHasProduction(item)"><v-btn v-if="!item.production" icon size="x-small" variant="tonal" color="purple" @click="$router.push({name:'add_production',query:{charge_id:item.id}})"><v-icon size="14">mdi-factory</v-icon></v-btn><v-btn v-else icon size="x-small" variant="flat" color="purple" @click="$router.push({name:'view_production',params:{id:item.production.id}})"><v-icon size="14">mdi-pencil</v-icon></v-btn></template>
                </template>
                <!-- fishery -->
                <template v-else-if="h.key==='_fishery'"><v-chip v-if="item._fishery && item._fishery!=='-'" size="x-small" color="cyan" variant="tonal" prepend-icon="mdi-fish">{{ item._fishery }}</v-chip><span v-else class="text-grey">-</span></template>
                <!-- origin -->
                <template v-else-if="h.key==='_origin'"><v-chip v-if="item._origin && item._origin!=='-'" size="x-small" color="amber-darken-2" variant="tonal" prepend-icon="mdi-map-marker">{{ item._origin }}</v-chip><span v-else class="text-grey">-</span></template>
                <!-- ship -->
                <template v-else-if="h.key==='_ship'"><v-chip v-if="item._ship && item._ship!=='-'" size="x-small" color="indigo" variant="tonal" prepend-icon="mdi-ferry">{{ item._ship }}</v-chip><span v-else class="text-grey">-</span></template>
                <!-- trucks -->
                <template v-else-if="h.key==='_trucks'"><div class="d-flex flex-wrap ga-1 justify-center"><v-chip v-for="t in (item._trucks && item._trucks!=='-' ? item._trucks.split(', ') : [])" :key="t" size="x-small" color="deep-orange" variant="tonal" prepend-icon="mdi-truck">{{ t }}</v-chip><span v-if="!item._trucks || item._trucks==='-'" class="text-grey">-</span></div></template>
                <!-- generic text columns (suppliers) -->
                <template v-else>{{ item[h.key] || '-' }}</template>
              </td>
            </tr>
            <!-- Expanded detail rows -->
            <tr v-if="expanded[item.id]"><td :colspan="headers.length+1" class="pa-0">
              <div class="lc-expand-content">
                <div v-for="(grp,gi) in groupDetails(item)" :key="gi" class="lc-detail-group mb-2">
                  <div class="lc-detail-group-header"><v-icon size="16" color="info" class="mr-1">mdi-tag</v-icon><b>{{ grp.category?grp.category[`name_${main.lang?.code}`]||grp.category.name:'-' }}</b></div>
                  <table class="lc-detail-table"><thead><tr><th>{{ $t('classification') }}</th><th>{{ $t('product') }}</th><th>{{ $t('product_family') }}</th><th>{{ $t('warehouse') }}</th><th>{{ $t('total_weight') }} (Kg)</th><th>{{ $t('total_weight_invoice') }} (Kg)</th><th>{{ $t('gap') }}</th><th>{{ $t('unit_price') }}</th><th>{{ $t('total_price') }}</th><th>{{ $t('package_type') }}</th><th>{{ $t('total_package_count') }}</th></tr></thead>
                  <tbody><tr v-for="dc in grp.items" :key="dc.id">
                    <td><v-chip size="x-small" color="info" variant="tonal">{{ dc.stockable_type||$t('text') }}</v-chip></td>
                    <td class="font-weight-bold">{{ articleName(dc) }}</td><td>{{ articleFamily(dc) }}</td>
                    <td><v-chip v-if="dc.warehouse" size="x-small" variant="tonal">{{ dc.warehouse[`name_${main.lang?.code}`]||dc.warehouse.name }}</v-chip><span v-else>{{ $t('non_stockable') }}</span></td>
                    <td>{{ main.formatDisplayNumber(dc.weight) }}</td>
                    <td>{{ main.formatDisplayNumber(dc.weight_invoice) }}</td>
                    <td><v-chip size="x-small" :color="Math.abs(Number(dc.weight||0)-Number(dc.weight_invoice||0))<0.01?'success':'warning'" variant="tonal">{{ main.formatDisplayNumber(Math.abs(Number(dc.weight||0)-Number(dc.weight_invoice||0))) }}</v-chip></td>
                    <td>{{ main.formatDisplayNumber(dc.unit_price) }}</td><td>{{ main.formatDisplayNumber(dc.total_price) }}</td>
                    <td>{{ dc.package_type?dc.package_type.name:'N/A' }}</td>
                    <td><v-chip v-if="dc.nbr_box" size="x-small" color="teal" variant="tonal"><v-icon size="12" class="mr-1">mdi-package-variant</v-icon>{{ dc.nbr_box }}</v-chip><span v-else>-</span></td>
                  </tr></tbody>
                  <tfoot><tr class="lc-detail-foot"><td colspan="4" class="text-right font-weight-bold">{{ $t('total') }}:</td><td class="font-weight-bold">{{ main.formatDisplayNumber(grp.totalW) }}</td><td class="font-weight-bold">{{ main.formatDisplayNumber(grp.totalWI) }}</td><td class="font-weight-bold"><v-chip size="x-small" :color="Math.abs(grp.totalW-grp.totalWI)<0.01?'success':'warning'" variant="tonal">{{ main.formatDisplayNumber(Math.abs(grp.totalW-grp.totalWI)) }}</v-chip></td><td></td><td class="font-weight-bold">{{ main.formatDisplayNumber(grp.totalP) }}</td><td colspan="2"></td></tr></tfoot>
                  </table>
                </div>
              </div>
            </td></tr>
          </template>
        </tbody>
        <tfoot class="lc-tfoot"><tr><td class="lc-td"></td><td v-for="h in headers" :key="'foot-'+h.key" class="lc-td text-center font-weight-bold"><template v-if="h.key==='total_weight'">Σ {{ main.formatDisplayNumber(totalWeight) }} Kg</template><template v-else-if="h.key==='_total_weight_invoice'">Σ {{ main.formatDisplayNumber(totalWeightInvoice) }} Kg</template><template v-else-if="h.key==='total_price'">Σ {{ main.formatDisplayNumber(totalPrice) }} {{ main.currency }}</template></td></tr></tfoot>
      </table>
    </div>
  </v-card-text>
  <!-- TAB: Add Charge -->
  <v-card-text v-else-if="activeTab === 1" class="pa-0">
    <AddCharge />
  </v-card-text>
  <v-dialog v-model="deleteDialog" max-width="450"><v-card class="rounded-xl"><v-card-title class="pa-5 pb-2 d-flex align-center"><v-icon color="error" class="mr-2">mdi-alert-circle-outline</v-icon>{{ $t('confirm_delete') }}</v-card-title><v-card-text class="pa-5 pt-2">{{ deleteBulk?$t('confirm_bulk_delete_message',{count:selected.length}):$t('confirm_delete_charge_message') }}</v-card-text><v-card-actions class="pa-5 pt-0"><v-spacer></v-spacer><v-btn @click="deleteDialog=false" variant="text">{{ $t('cancel') }}</v-btn><v-btn color="error" variant="flat" rounded @click="execDelete" :loading="deleting">{{ $t('delete') }}</v-btn></v-card-actions></v-card></v-dialog>
</v-card>
<!-- Quality Control Dialog -->
<QualityControlDialog v-model="qcDialogOpen" :charge="qcDialogCharge" @saved="onQcSaved" @deleted="onQcDeleted" />
<!-- Audit Log Dialog -->
<AuditLogDialog v-model="auditLogDialogOpen" auditable-type="Charge" :auditable-id="auditLogRecordId" module-color="#42A5F5" />
<!-- Record Issue Dialog -->
<RecordIssueDialog v-model="issueDialogOpen" record-type="Charge" :record-id="issueDialogRecordId" module-color="#42A5F5" @issue-changed="fetchCharges" />
</template>
<script>
import script from './script.js';
import AddCharge from '../AddCharge/index.vue';
import QualityControlDialog from '../QualityControl/QualityControlDialog.vue';
import AuditLogDialog from '@/components/shared/AuditLogDialog.vue';
import RecordIssueDialog from '@/components/shared/RecordIssueDialog.vue';
import SegmentedPillToggle from '@/components/common/SegmentedPillToggle.vue';
import { useAuth } from '@/stores/Auth';
export default {
  ...script,
  components:{...script.components,AddCharge,QualityControlDialog,AuditLogDialog,RecordIssueDialog,SegmentedPillToggle},
  data(){return{...script.data.call(this),activeTab:0,qcDialogOpen:false,qcDialogCharge:null,auditLogDialogOpen:false,auditLogRecordId:0,issueDialogOpen:false,issueDialogRecordId:0,chargeTabs:[{value:0,label:'list_charge',icon:'mdi-view-list-outline'},...(useAuth().hasPermission('charges.create')?[{value:1,label:'add_charge',icon:'mdi-sticker-plus-outline'}]:[])]}},
  computed:{...script.computed,auth(){return useAuth()},selectAll:{get(){return this.selected.length===this.colFilteredItems.length&&this.colFilteredItems.length>0},set(v){this.selected=v?this.colFilteredItems.map(i=>i.id):[]}}},
  methods:{...script.methods,openQcDialog(item){this.qcDialogCharge=item;this.qcDialogOpen=true},onQcSaved(){this.fetchCharges()},onQcDeleted(){this.fetchCharges()},openAuditLog(id){this.auditLogRecordId=id;this.auditLogDialogOpen=true},openIssueDialog(id){this.issueDialogRecordId=id;this.issueDialogOpen=true},colPinStyle(key,z){const side=this.pinSide(key);if(!side)return{};const bg=this.main?.is_dark?'#1c1f28':'#F5F5F5';if(side==='left')return{position:'sticky',left:this.getPinLeft(key)+'px',zIndex:z,background:bg};return{position:'sticky',right:this.getPinRight(key)+'px',zIndex:z,background:bg};},measureColWidths(){this.$nextTick(()=>{const ths=this.$refs.tableWrap?.querySelectorAll('thead th');if(!ths)return;const w={};ths.forEach((th,i)=>{const key=i===0?'__checkbox':this.headers[i-1]?.key;if(key)w[key]=th.offsetWidth});this.colWidths=w})}},
  watch:{...script.watch,headers(){this.measureColWidths()},pinState:{handler(){this.measureColWidths()},deep:true},activeTab(v){const q=v===1?{tab:'add'}:{};if(JSON.stringify(this.$route.query)!==JSON.stringify(q))this.$router.replace({query:q}).catch(()=>{});if(v===0)this.fetchCharges()},showInvoice(v){if(v&&this.uninvoicedSelectedCharges.length){this.fetchNextTracking(this.invForm.type_invoice||'local')}}},
  mounted(){script.mounted.call(this);this.$nextTick(()=>this.measureColWidths());if(this.$route.query.tab==='add')this.activeTab=1},
  beforeUnmount(){if(script.beforeUnmount)script.beforeUnmount.call(this)},
  updated(){this.measureColWidths()},
};
</script>
<style scoped>
.lc-card{border-radius:20px!important;font-family:'Inter',sans-serif}
.lc-tab-toolbar{background:rgba(var(--v-theme-surface),.95);border:1px solid rgba(66,165,245,.2)!important;box-shadow:0 2px 8px rgba(13,71,161,.04)}
.lc-segmented-toggle{background:rgba(var(--v-theme-on-surface),.02)}
.lc-hero{background:linear-gradient(135deg,#0D47A1 0%,#1565C0 40%,#42A5F5 100%);border-radius:inherit;border-bottom-left-radius:0;border-bottom-right-radius:0;min-height:100px}
.lc-hero-inner{position:relative;z-index:1;padding:22px 26px;color:#fff;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px}
.lc-hero-icon{width:52px;height:52px;border-radius:14px;background:rgba(255,255,255,.18);backdrop-filter:blur(6px);display:flex;align-items:center;justify-content:center;border:1px solid rgba(255,255,255,.15)}
.lc-hero-title{font-size:19px;font-weight:800;letter-spacing:.3px}
.lc-hero-actions{display:flex;gap:8px;flex-wrap:wrap}
.lc-hero-kpi{display:flex;flex-direction:column;align-items:flex-start;padding:6px 14px;background:rgba(255,255,255,.14);backdrop-filter:blur(8px);border-radius:12px;border:1px solid rgba(255,255,255,.2);min-width:90px}
.lc-kpi-val{font-size:14px;font-weight:800;line-height:1.2;color:#ffffff}
.lc-kpi-lbl{font-size:10px;text-transform:uppercase;letter-spacing:.5px;opacity:.85;color:#e3f2fd}
.lc-toolbar{background:rgba(var(--v-theme-surface),.95);border:1px solid rgba(66,165,245,.15);box-shadow:0 2px 12px rgba(13,71,161,.04)}
.lc-search-field{max-width:320px}
.lc-filter-btn{font-weight:600;letter-spacing:.3px}
.adv-panel{border:1.5px solid rgba(25,118,210,.15);background:rgba(25,118,210,.03)}
.inv-panel{border:1.5px solid rgba(0,150,136,.15);background:rgba(0,150,136,.03)}
.lc-table-wrap{overflow:auto;max-height:calc(100vh - 300px);border-radius:12px;border:1px solid rgba(var(--v-border-color),.12)}
.lc-table{width:100%;border-collapse:collapse;font-size:13px}
.lc-thead{position:sticky;top:0;z-index:4;background:rgb(var(--v-theme-surface))}
.lc-th{padding:10px 12px;text-align:center;white-space:nowrap;border-bottom:2px solid rgba(var(--v-border-color),.12);background:rgb(var(--v-theme-surface));transition:background .15s}
.lc-td{padding:8px 12px;text-align:center;border-bottom:1px solid rgba(var(--v-border-color),.06);white-space:nowrap}
.lc-col-pin{position:sticky!important;z-index:2!important;background:#F5F5F5!important;box-shadow:2px 0 4px -2px rgba(0,0,0,.1)}
.lc-th-pin{position:sticky!important;z-index:5!important;background:#F5F5F5!important;box-shadow:2px 0 4px -2px rgba(0,0,0,.1)}
:deep(.v-theme--dark) .lc-col-pin,
:deep(.v-theme--dark) .lc-th-pin,
.v-theme--dark .lc-col-pin,
.v-theme--dark .lc-th-pin {
  background: #1c1f28 !important;
  color: #f1f5f9 !important;
  box-shadow: 2px 0 4px -2px rgba(0,0,0,.4) !important;
}
.lc-th-dragover{background:rgba(25,118,210,.08)!important}
.lc-th-inner{display:flex;align-items:center;justify-content:center;gap:2px}
.lc-th-title{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.3px;opacity:.7;cursor:pointer}
.drag-handle{cursor:grab;opacity:.5;transition:opacity .15s}.drag-handle:hover{opacity:1}
.sort-badge{font-size:9px;background:#1976D2;color:#fff;border-radius:50%;width:14px;height:14px;display:inline-flex;align-items:center;justify-content:center}
.lc-row:hover{background:rgba(25,118,210,.04)}
.lc-row-expanded{background:rgba(25,118,210,.06)}
.lc-row-invoiced{background:rgba(76,175,80,.08)!important}
.lc-row-invoiced:hover{background:rgba(76,175,80,.14)!important}
.lc-tfoot td{padding:10px 12px;border-top:2px solid rgba(25,118,210,.2);color:#1565C0;font-size:14px}
:deep(.v-theme--dark) .lc-tfoot td,
.v-theme--dark .lc-tfoot td {
  color: #64B5F6 !important;
}
.lc-expand-content{padding:12px 24px;background:rgba(var(--v-border-color),.03)}
.lc-detail-group{border:1px solid rgba(var(--v-border-color),.12);border-radius:10px;overflow:hidden}
.lc-detail-group-header{padding:8px 14px;background:rgba(25,118,210,.06);font-size:13px}
.lc-detail-table{width:100%;border-collapse:collapse;font-size:12px}
.lc-detail-table th{padding:6px 10px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.3px;opacity:.6;text-align:center;border-bottom:1px solid rgba(var(--v-border-color),.1)}
.lc-detail-table td{padding:6px 10px;text-align:center;border-bottom:1px solid rgba(var(--v-border-color),.05)}
.lc-detail-foot td{padding:8px 10px;background:rgba(25,118,210,.04);border-top:1px solid rgba(25,118,210,.15)}
.lc-panel-card{border:1.5px solid rgba(25,118,210,.12);border-radius:14px;padding:12px;background:rgba(var(--v-theme-surface),1)}
.lc-panel-header{font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;opacity:.7;margin-bottom:8px;display:flex;align-items:center}
.lc-stats-panel{max-height:400px}
.lc-stat-item{padding:8px 10px;border-radius:10px;background:rgba(25,118,210,.03);border:1px solid rgba(25,118,210,.06)}
</style>
