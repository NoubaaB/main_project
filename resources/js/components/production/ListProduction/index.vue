<template>
<v-card class="lp-card" elevation="0">
  <div class="lp-hero"><div class="lp-hero-inner">
    <div class="d-flex align-center ga-4"><div class="lp-hero-icon"><v-icon size="28" color="white">mdi-factory</v-icon></div><div><div class="lp-hero-title">{{ $t('productions') }}</div><div class="text-white text-caption" style="opacity:.7">{{ $t('production_list_desc') }}</div></div></div>
    <div class="lp-hero-actions">
      <v-btn variant="tonal" color="white" rounded size="small" prepend-icon="mdi-refresh" @click="fetchProductions(); fetchAnalytics()" :loading="loading">{{ $t('refresh') }}</v-btn>
      <v-btn v-if="selected.length && auth.hasPermission('production.delete')" variant="tonal" color="error" rounded size="small" prepend-icon="mdi-delete" @click="confirmBulkDelete">{{ $t('delete_selected') }} ({{ selected.length }})</v-btn>
    </div>
  </div></div>
  <!-- Tab Slider -->
  <v-slide-group v-model="activeTab" mandatory show-arrows class="lp-tab-slider mx-4 mt-3">
    <v-slide-group-item v-for="tab in productionTabs" :key="tab.value" v-slot="{ isSelected, toggle }">
      <v-btn :color="isSelected ? '#00897B' : undefined" :variant="isSelected ? 'flat' : 'text'" rounded class="mx-1" @click="toggle" :prepend-icon="tab.icon">{{ $t(tab.label) }}</v-btn>
    </v-slide-group-item>
  </v-slide-group>
  <v-divider class="mx-4 mt-2" color="#00897B"></v-divider>
  <!-- TAB: List Production -->
  <v-card-text v-if="activeTab === 0" class="pa-4">
    <!-- Analytics Row: Calendar | Chart | Statistics -->
    <v-row dense class="mb-3">
      <v-col cols="3">
        <div class="lp-panel-card">
          <div class="lp-panel-header"><v-icon size="16" color="#00897B" class="mr-1">mdi-calendar-range</v-icon><span>{{ $t('date_range') }}</span></div>
          <DatePicker v-model.range="dateRangeVC" :is-dark="main.is_dark" :locale="main.lang?.short||'en'" mode="date" color="teal" :max-date="new Date()" expanded @update:model-value="onDateRangeChange" />
        </div>
      </v-col>
      <v-col cols="6">
        <div class="lp-panel-card" style="height:100%">
          <div class="lp-panel-header d-flex align-center justify-space-between">
            <div class="d-flex align-center"><v-icon size="16" color="#00897B" class="mr-1">mdi-chart-areaspline</v-icon><span>{{ $t('analytics') }}</span></div>
            <v-btn-toggle v-model="chartMode" mandatory density="compact" color="#00897B" variant="outlined" rounded="lg" divided>
              <v-btn value="daily" size="x-small">{{ $t('daily') }}</v-btn>
              <v-btn value="monthly" size="x-small">{{ $t('monthly') }}</v-btn>
              <v-btn value="yearly" size="x-small">{{ $t('yearly') }}</v-btn>
            </v-btn-toggle>
          </div>
          <apexchart v-if="chartSeries.length>1 || (chartSeries[0] && chartSeries[0].data.length)" type="line" :height="300" :options="chartOptions" :series="chartSeries"></apexchart>
          <div v-else class="d-flex align-center justify-center" style="height:220px;opacity:.4"><v-icon size="48" color="grey">mdi-chart-line-variant</v-icon></div>
        </div>
      </v-col>
      <v-col cols="3">
        <div class="lp-panel-card lp-stats-panel" style="height:100%;overflow-y:auto">
          <div class="lp-panel-header"><v-icon size="16" color="#00897B" class="mr-1">mdi-chart-box-outline</v-icon><span>{{ $t('statistics') }}</span></div>
          <div v-for="(stat,si) in categoryStats" :key="si" class="lp-stat-item mb-2">
            <div class="d-flex align-center justify-space-between mb-1">
              <v-chip size="x-small" color="#00897B" variant="tonal" class="font-weight-bold">{{ stat.name }} ({{ stat.count }})</v-chip>
              <v-chip size="x-small" color="#00897B" variant="elevated" class="font-weight-bold">{{ totalWeight>0?Math.round(stat.weight/totalWeight*1000)/10:0 }}%</v-chip>
            </div>
            <v-progress-linear :model-value="totalWeight>0?(stat.weight/totalWeight*100):0" color="#00897B" bg-color="#B2DFDB" height="5" rounded class="mb-1"></v-progress-linear>
            <div class="d-flex flex-column ga-1 text-caption">
              <div class="d-flex align-center ga-1"><v-icon size="12" color="#00897B">mdi-weight-kilogram</v-icon><span class="text-medium-emphasis">{{ $t('weight') }}:</span><b style="color: #00897B">{{ main.formatDisplayNumber(stat.weight) }} Kg</b></div>
              <div class="d-flex align-center ga-1"><v-icon size="12" color="teal-lighten-1">mdi-scale-balance</v-icon><span class="text-medium-emphasis">{{ $t('invoice_weight') }}:</span><b class="text-teal-lighten-1">{{ main.formatDisplayNumber(stat.invoiceWeight) }} Kg</b></div>
              <div class="d-flex align-center ga-1"><v-icon size="12" color="deep-orange">mdi-cash</v-icon><span class="text-medium-emphasis">{{ $t('total_price') }}:</span><b class="text-deep-orange">{{ main.formatDisplayNumber(stat.price) }} {{ main.currency }}</b></div>
              <div class="d-flex align-center ga-1"><v-icon size="12" color="amber-darken-2">mdi-cash-check</v-icon><span class="text-medium-emphasis">{{ $t('invoice_price') }}:</span><b class="text-amber-darken-2">{{ main.formatDisplayNumber(stat.invoicePrice) }} {{ main.currency }}</b></div>
            </div>
          </div>
          <v-divider class="my-2"></v-divider>
          <!-- Invoice stats -->
          <div class="d-flex flex-column ga-1">
            <div class="d-flex align-center ga-2"><v-icon size="14" color="success">mdi-check-decagram</v-icon><span class="text-caption text-medium-emphasis">{{ $t('invoiced') }}:</span><v-chip size="x-small" color="success" variant="tonal"><b>{{ totalInvoiced }}</b></v-chip></div>
            <div class="d-flex align-center ga-2"><v-icon size="14" color="grey">mdi-close-circle-outline</v-icon><span class="text-caption text-medium-emphasis">{{ $t('not_invoiced') }}:</span><v-chip size="x-small" color="grey" variant="tonal"><b>{{ totalNotInvoiced }}</b></v-chip></div>
            <div class="d-flex align-center ga-1"><v-icon size="14" color="teal-lighten-1">mdi-scale-balance</v-icon><span class="text-caption font-weight-bold">{{ $t('invoice_weight') }}:</span><b class="text-teal-lighten-1 text-caption">{{ main.formatDisplayNumber(totalInvoiceWeight) }} Kg</b></div>
            <div class="d-flex align-center ga-1"><v-icon size="14" color="amber-darken-2">mdi-cash-check</v-icon><span class="text-caption font-weight-bold">{{ $t('invoice_price') }}:</span><b class="text-amber-darken-2 text-caption">{{ main.formatDisplayNumber(totalInvoicePrice) }} {{ main.currency }}</b></div>
          </div>
          <v-divider class="my-2"></v-divider>
          <div class="d-flex flex-column ga-1">
            <div class="d-flex align-center ga-1"><v-icon size="14" color="#42A5F5">mdi-fishbowl-outline</v-icon><span class="text-caption font-weight-bold">{{ $t('fresh_reception') }}:</span><b style="color:#42A5F5" class="text-caption">{{ main.formatDisplayNumber(totalFreshReception) }} Kg</b></div>
            <div class="d-flex align-center ga-1"><v-icon size="14" color="#AB47BC">mdi-snowflake</v-icon><span class="text-caption font-weight-bold">{{ $t('frozen_production') }}:</span><b style="color:#AB47BC" class="text-caption">{{ main.formatDisplayNumber(totalFrozenProduction) }} Kg</b></div>
            <div class="d-flex align-center ga-1"><v-icon size="14" color="#8D6E63">mdi-leaf</v-icon><span class="text-caption font-weight-bold">{{ $t('guano_production') }}:</span><b style="color:#8D6E63" class="text-caption">{{ main.formatDisplayNumber(totalGuanoProduction) }} Kg</b></div>
            <div class="d-flex align-center ga-1">
              <v-icon size="14" :color="totalEquilibrium >= 0 ? '#4CAF50' : '#EF5350'">{{ totalEquilibrium >= 0 ? 'mdi-arrow-up-bold' : 'mdi-arrow-down-bold' }}</v-icon>
              <span class="text-caption font-weight-bold">{{ $t('equilibrium') }}:</span>
              <b :style="{ color: totalEquilibrium >= 0 ? '#4CAF50' : '#EF5350' }" class="text-caption">{{ main.formatDisplayNumber(Math.abs(totalEquilibrium)) }} Kg ({{ $t(totalEquilibrium >= 0 ? 'surplus' : 'shortage') }})</b>
            </div>
            <v-divider class="my-1"></v-divider>
            <div class="lp-stat-item mt-1 pa-2 rounded-lg" style="background: rgba(84, 110, 122, 0.05); border: 1px solid rgba(84, 110, 122, 0.15)">
              <div class="d-flex align-center justify-space-between mb-1">
                <div class="d-flex align-center ga-1">
                  <v-icon size="14" color="#546E7A">mdi-layers-outline</v-icon>
                  <span class="text-caption font-weight-bold" style="color: #37474F">{{ $t('secondary_packaging_config') }}</span>
                </div>
                <v-chip size="x-small" color="#546E7A" variant="elevated" class="font-weight-bold">
                  {{ main.formatDisplayNumber(secondaryPackagingStats.total_cost) }} {{ main.currency }}
                </v-chip>
              </div>
              <div class="d-flex flex-column ga-1 mt-1">
                <div
                  v-for="comp in secondaryPackagingStats.items"
                  :key="comp.key"
                  class="d-flex align-center justify-space-between px-2 py-1 rounded"
                  :style="{ background: comp.qte > 0 ? 'rgba(84, 110, 122, 0.08)' : 'transparent', opacity: comp.qte > 0 ? 1 : 0.5 }"
                >
                  <span class="text-caption font-weight-medium">{{ comp.label }} <span v-if="comp.unit" class="text-grey text-caption font-weight-bold" style="opacity: 0.8">({{ comp.unit }})</span></span>
                  <div class="d-flex align-center ga-2 text-caption">
                    <span v-if="comp.qte > 0" class="text-medium-emphasis"><b>{{ main.formatDisplayNumber(comp.qte) }}</b> {{ comp.unit || '' }}</span>
                    <span v-else class="text-grey">-</span>
                    <b class="text-blue-grey-darken-3">{{ main.formatDisplayNumber(comp.total_cost) }} {{ main.currency }}</b>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </v-col>
    </v-row>
    <!-- Action Buttons + Panels Row -->
    <v-row dense class="mb-3" align="start">
      <v-col cols="6">
        <div class="d-flex justify-center mb-2"><v-btn block :variant="showAdvanced?'flat':'outlined'" :color="showAdvanced?'#00897B':'grey'" rounded size="small" :prepend-icon="showAdvanced?'mdi-chevron-up':'mdi-chevron-down'" @click="showAdvanced=!showAdvanced">{{ $t('advanced_filters') }}</v-btn></div>
        <v-expand-transition><div v-if="showAdvanced" class="adv-panel pa-3 rounded-lg">
          <v-row dense>
            <v-col cols="6"><v-autocomplete v-model="advFilter.articleType" :items="[{title:$t('text'),value:'text'},{title:'FishType',value:'FishType'},{title:'GuanoType',value:'GuanoType'},{title:'PackageType',value:'PackageType'}]" :label="$t('article_type')" variant="outlined" density="compact" rounded color="#00897B" style="color: #00897B" hide-details clearable></v-autocomplete></v-col>
            <v-col v-if="advFilter.articleType==='FishType'" cols="6"><v-autocomplete v-model="advFilter.fishFamilies" :items="fishFamilies.map(f=>({title:f[`name_${main.lang?.code}`]||f.name,value:f.id}))" :label="$t('fish_family')" variant="outlined" density="compact" rounded color="#00897B" style="color: #00897B" hide-details clearable multiple chips closable-chips></v-autocomplete></v-col>
            <v-col v-if="advFilter.articleType==='FishType'" cols="6"><v-autocomplete v-model="advFilter.fishFreshFrozen" :items="['fresh','frozen']" :label="$t('fish_type_fresh_frozen')" variant="outlined" density="compact" rounded color="#00897B" style="color: #00897B" hide-details clearable multiple chips closable-chips></v-autocomplete></v-col>
            <v-col v-if="advFilter.articleType==='FishType'" cols="6"><v-autocomplete v-model="advFilter.fishProcessTypes" :items="fishProcessTypes.map(f=>({title:f[`name_${main.lang?.code}`]||f.name,value:f.id}))" :label="$t('fish_process_type')" variant="outlined" density="compact" rounded color="#00897B" style="color: #00897B" hide-details clearable multiple chips closable-chips></v-autocomplete></v-col>
            <v-col v-if="advFilter.articleType==='GuanoType'" cols="6"><v-autocomplete v-model="advFilter.guanoTypes" :items="guanoTypes.map(g=>({title:g[`name_${main.lang?.code}`]||g.name,value:g.id}))" :label="$t('guano_type')" variant="outlined" density="compact" rounded color="#00897B" style="color: #00897B" hide-details clearable multiple chips closable-chips></v-autocomplete></v-col>
            <v-col v-if="advFilter.articleType==='PackageType'" cols="6"><v-autocomplete v-model="advFilter.packageTypes" :items="packageTypes.map(p=>({title:p[`name_${main.lang?.code}`]||p.name,value:p.id}))" :label="$t('package_type')" variant="outlined" density="compact" rounded color="#00897B" style="color: #00897B" hide-details clearable multiple chips closable-chips></v-autocomplete></v-col>
            <v-col cols="6"><v-autocomplete v-model="advFilter.warehouses" :items="warehouses.map(w=>({title:w[`name_${main.lang?.code}`]||w.name,value:w.id}))" :label="$t('warehouse')" variant="outlined" density="compact" rounded color="#00897B" style="color: #00897B" hide-details clearable multiple chips closable-chips></v-autocomplete></v-col>
          </v-row>
        </div></v-expand-transition>
      </v-col>
      <v-col cols="6">
        <div class="d-flex flex-column align-center mb-2"><v-btn block :variant="showInvoice?'flat':'outlined'" :color="showInvoice?'teal':'grey'" rounded size="small" :prepend-icon="showInvoice?'mdi-chevron-up':'mdi-chevron-down'" @click="showInvoice=!showInvoice" :disabled="!canSetupInvoice">{{ $t('supplier_invoices') }} / {{ $t('client_invoices') }}</v-btn>
          <v-alert v-if="uninvoicedSelectedProductions.length && !canSetupInvoice" type="warning" density="compact" variant="tonal" class="mt-1 text-caption" closable>{{ $t('selected_records_different_owners') }}</v-alert>
        </div>
        <v-expand-transition><div v-if="showInvoice&&uninvoicedSelectedProductions.length" class="inv-panel pa-3 rounded-lg">
          <div class="d-flex justify-center mb-2">
            <v-btn-toggle v-model="invForm.invoiceKind" mandatory density="compact" rounded color="teal" variant="outlined">
              <v-btn value="supplier" size="small"><v-icon size="14" class="mr-1">mdi-truck-delivery</v-icon>{{ $t('supplier_invoices') }}</v-btn>
              <v-btn value="client" size="small"><v-icon size="14" class="mr-1">mdi-account-group</v-icon>{{ $t('client_invoices') }}</v-btn>
            </v-btn-toggle>
          </div>
          <v-row dense>
            <v-col cols="6">
              <DatePicker v-model="invForm.date" :is-dark="main.is_dark" :locale="main.lang?.short||'en'" mode="date" color="teal" :disabled-dates="invDisabledDates" :min-date="invMinDate" :max-date="invMaxDate">
                <template #default="{ togglePopover, inputValue }">
                  <v-text-field :model-value="inputValue" :label="$t('invoice_date')" variant="outlined" density="compact" rounded color="teal" class="text-teal" hide-details readonly prepend-inner-icon="mdi-calendar" @click="togglePopover"></v-text-field>
                </template>
              </DatePicker>
            </v-col>
            <v-col cols="6"><v-text-field :model-value="invoiceSuppliers.join(', ')" :label="invForm.invoiceKind === 'supplier' ? $t('supplier') : $t('client')" variant="outlined" density="compact" rounded color="teal" class="text-teal" hide-details readonly></v-text-field></v-col>
            <v-col cols="6"><v-autocomplete v-model="invForm.type_invoice" :items="[{title:$t('local'),value:'local'},{title:$t('export_type'),value:'export'}]" :label="$t('invoice_type')" variant="outlined" density="compact" rounded color="teal" class="text-teal" hide-details @update:model-value="onTypeInvoiceChange"></v-autocomplete></v-col>
            <v-col cols="6"><v-combobox v-model="invForm.trackingNumbers" :label="$t('invoice_tracking_number')" variant="outlined" density="compact" rounded color="teal" class="text-teal" hide-details multiple chips closable-chips :loading="invChecking" prepend-inner-icon="mdi-barcode" @update:model-value="onTrackingUpdate"></v-combobox></v-col>
          </v-row>
          <v-row dense class="mt-3"><v-col cols="6"><v-btn block color="teal" variant="flat" rounded size="small" :disabled="!invForm.trackingNumbers.length" @click="submitInvoice" prepend-icon="mdi-content-save">{{ $t('save') }}</v-btn></v-col><v-col cols="6"><v-btn block color="error" variant="outlined" rounded size="small" @click="cancelInvoice" prepend-icon="mdi-close-circle-outline">{{ $t('cancel') }}</v-btn></v-col></v-row>

          <div class="d-flex ga-4 mt-2 text-body-2 flex-wrap"><span><b>{{ $t('invoice_weight') }}:</b> {{ main.formatDisplayNumber(uninvoicedSelectedProductions.reduce((s,c)=>s+Number(c.total_weight||0),0)) }} Kg</span><span><b>{{ $t('total_price') }}:</b> {{ main.formatDisplayNumber(uninvoicedSelectedProductions.reduce((s,c)=>s+Number(c.total_price||0),0)) }} {{ main.currency }}</span></div>
        </div></v-expand-transition>
      </v-col>
    </v-row>
    <div class="d-flex ga-2 mb-3 justify-end">
      <v-btn v-if="Object.values(colFilters).some(f=>f&&f.length)" variant="tonal" color="warning" rounded size="small" prepend-icon="mdi-filter-off" @click="colFilters={}">{{ $t('clear_filters') }}</v-btn>
      <v-btn icon variant="tonal" color="success" size="small" @click="exportExcel"><v-icon size="18">mdi-file-excel-outline</v-icon></v-btn>
    </div>
    <!-- Table -->
    <div class="lp-table-wrap">
      <table class="lp-table">
        <thead class="lp-thead"><tr>
          <th class="lp-th lp-th-pin" style="min-width:50px;left:0"><v-checkbox-btn v-model="selectAll" color="#00897B" density="compact" hide-details></v-checkbox-btn></th>
          <th v-for="h in headers" :key="h.key" class="lp-th" :class="{'lp-th-pin':isPinned(h.key),'lp-th-dragover':dragOverCol===h.key}" :style="colPinStyle(h.key,3)" :draggable="!h.fixed" @dragstart="onDragStart(h.key)" @dragover="onDragOver(h.key,$event)" @dragend="onDragEnd" @drop="onDragEnd">
            <div class="lp-th-inner">
              <v-icon v-if="!h.fixed" size="12" class="drag-handle" color="grey-lighten-1">mdi-drag-vertical</v-icon>
              <span class="lp-th-title" @click="h.sortable!==false&&toggleSort(h.key)">{{ h.title }}</span>
              <template v-if="h.key!=='actions'&&h.key!=='expand'">
                <v-btn v-if="sortDir(h.key)" icon variant="text" size="14" @click.stop="toggleSort(h.key)"><v-icon size="12" color="#00897B">mdi-arrow-{{ sortDir(h.key)==='asc'?'up':'down' }}</v-icon></v-btn>
                <span v-if="sortIdx(h.key)" class="sort-badge-p">{{ sortIdx(h.key) }}</span>
                <v-menu v-model="filterMenus[h.key]" :close-on-content-click="false" location="bottom"><template #activator="{props}"><v-btn v-bind="props" icon variant="text" size="14"><v-icon size="12" :color="colFilters[h.key]&&colFilters[h.key].length?'purple':'grey'">mdi-filter</v-icon></v-btn></template><v-card min-width="220" class="rounded-lg pa-2"><v-autocomplete v-model="colFilters[h.key]" :items="filterOpts(h.key)" density="compact" variant="outlined" color="#00897B" hide-details multiple chips closable-chips :placeholder="$t('filter')" style="color: #00897B"></v-autocomplete></v-card></v-menu>
                <v-btn icon variant="text" size="14" @click.stop="togglePin(h.key)"><v-icon size="10" :color="pinSide(h.key)==='left'?'warning':pinSide(h.key)==='right'?'blue':'grey'">{{ pinSide(h.key)==='left'?'mdi-pin':pinSide(h.key)==='right'?'mdi-pin-outline':'mdi-pin-off-outline' }}</v-icon></v-btn>
              </template>
            </div>
          </th>
        </tr></thead>
        <tbody>
          <template v-if="loading"><tr><td :colspan="headers.length+1" class="text-center pa-8"><v-progress-circular indeterminate color="#00897B"></v-progress-circular></td></tr></template>
          <template v-else v-for="item in colFilteredItems" :key="item.id">
            <tr class="lp-row" :class="{'lp-row-expanded':expanded[item.id],'lp-row-invoiced':item._is_invoiced}">
              <td class="lp-td lp-th-pin" style="left:0"><v-checkbox-btn v-model="selected" :value="item.id" color="#00897B" density="compact" hide-details></v-checkbox-btn></td>
              <td v-for="h in headers" :key="h.key" class="lp-td" :class="{'lp-col-pin':isPinned(h.key)}" :style="colPinStyle(h.key,2)">
                <template v-if="h.key==='expand'"><v-btn icon variant="text" size="24" @click="toggleExpand(item.id)"><v-icon size="16">{{ expanded[item.id]?'mdi-chevron-up':'mdi-chevron-down' }}</v-icon></v-btn></template>
                <template v-else-if="h.key==='date'">{{ main.formateDate(item.date) }}</template>
                <template v-else-if="h.key==='production_category_id'"><v-chip size="small" color="#00897B" variant="tonal">{{ catName(item.production_category_id) }}</v-chip></template>
                <template v-else-if="h.key==='production_type'"><v-chip v-if="item.production_type" size="x-small" :color="item.production_type==='intern'?'info':'warning'" variant="tonal">{{ $t(item.production_type) }}</v-chip><span v-else class="text-grey">-</span></template>
                <template v-else-if="h.key==='_tracking'"><div class="d-flex flex-wrap ga-1 justify-center"><v-chip v-for="tn in (item.production_tracking_number||[])" :key="tn" size="x-small" color="deep-purple" variant="tonal">{{ tn }}</v-chip><span v-if="!(item.production_tracking_number||[]).length" class="text-grey">-</span></div></template>
                <template v-else-if="h.key==='_charge'"><v-chip v-if="item.charge" size="x-small" color="#00897B" variant="tonal" @click="$router.push({name:'view_charge',params:{id:item.charge.id}})"><v-icon size="12" class="mr-1">mdi-link</v-icon>{{ item._charge }}</v-chip><span v-else class="text-grey">-</span></template>
                <template v-else-if="h.key==='total_weight'">{{ main.formatDisplayNumber(item.total_weight) }} Kg</template>
                <template v-else-if="h.key==='total_price'">{{ main.formatDisplayNumber(item.total_price) }} {{ main.currency }}</template>
                <template v-else-if="h.key==='actions'">
                  <v-btn icon size="x-small" variant="tonal" color="#00897B" @click="$router.push({name:'view_production',params:{id:item.id}})" class="mr-1"><v-icon size="14">mdi-eye</v-icon></v-btn>
                  <v-btn v-if="auth.hasPermission('production.edit')" icon size="x-small" variant="tonal" color="warning" @click="$router.push({name:'edit_production',params:{id:item.id}})" class="mr-1"><v-icon size="14">mdi-pencil</v-icon></v-btn>
                  <v-btn v-if="auth.hasPermission('production.delete')" icon size="x-small" variant="tonal" color="error" @click="confirmDelete(item.id)" class="mr-1"><v-icon size="14">mdi-delete</v-icon></v-btn>
                  <v-btn icon size="x-small" variant="tonal" color="blue-grey" @click="openAuditLog(item.id)"><v-icon size="14">mdi-history</v-icon><v-tooltip activator="parent" location="top">{{ $t('audit_log') }}</v-tooltip></v-btn>
                </template>
                <template v-else-if="h.key==='_invoice_state'">
                  <v-chip v-if="item._is_invoiced && item._invoice_type === 'supplier'" size="x-small" color="orange-darken-4" variant="tonal" prepend-icon="mdi-truck-delivery">{{ item._invoice_state }}</v-chip>
                  <v-chip v-else-if="item._is_invoiced && item._invoice_type === 'client'" size="x-small" color="cyan-darken-3" variant="tonal" prepend-icon="mdi-account-group">{{ item._invoice_state }}</v-chip>
                  <v-chip v-else size="x-small" color="grey" variant="tonal" prepend-icon="mdi-close-circle-outline">{{ $t('not_invoiced') }}</v-chip>
                </template>
                <template v-else>{{ item[h.key] || '-' }}</template>
              </td>
            </tr>
            <tr v-if="expanded[item.id]"><td :colspan="headers.length+1" class="pa-0">
              <div class="lp-expand-content">
                <div v-for="(grp,gi) in groupDetails(item)" :key="gi" class="lp-detail-group mb-2">
                  <div class="lp-detail-group-header"><v-icon size="16" color="#00897B" class="mr-1">mdi-tag</v-icon><b>{{ grp.category?grp.category[`name_${main.lang?.code}`]||grp.category.name:'-' }}</b></div>
                  <table class="lp-detail-table"><thead><tr><th>{{ $t('classification') }}</th><th>{{ $t('product') }}</th><th>{{ $t('product_family') }}</th><th>{{ $t('warehouse') }}</th><th>{{ $t('total_weight') }}</th><th>{{ $t('unit_price') }}</th><th>{{ $t('total_price') }}</th><th>{{ $t('secondary_packaging_cost') }}</th><th>{{ $t('package_type') }}</th><th>{{ $t('total_package_count') }}</th></tr></thead>
                  <tbody><tr v-for="dc in grp.items" :key="dc.id">
                    <td><v-chip size="x-small" color="#00897B" variant="tonal">{{ dc.stockable_type||$t('text') }}</v-chip></td>
                    <td class="font-weight-bold">{{ articleName(dc) }}</td><td>{{ articleFamily(dc) }}</td>
                    <td><span v-if="dc.warehouse">{{ dc.warehouse[`name_${main.lang?.code}`]||dc.warehouse.name }}</span><span v-else>{{ $t('non_stockable') }}</span></td>
                    <td>{{ main.formatDisplayNumber(dc.weight) }}</td><td>{{ main.formatDisplayNumber(dc.unit_price) }}</td><td>{{ main.formatDisplayNumber(dc.total_price) }}</td>
                    <td><span v-if="dc.secondary_packaging_cost > 0" class="font-weight-bold text-blue-grey">{{ main.formatDisplayNumber(dc.secondary_packaging_cost) }} {{ main.currency }}</span><span v-else class="text-grey">-</span></td>
                    <td>{{ dc.package_type?dc.package_type.name:'N/A' }}</td><td>{{ dc.nbr_box||'-' }}</td>
                  </tr></tbody>
                  <tfoot><tr class="lp-detail-foot"><td colspan="4" class="text-right font-weight-bold">{{ $t('total') }}:</td><td class="font-weight-bold">{{ main.formatDisplayNumber(grp.totalW) }}</td><td></td><td class="font-weight-bold">{{ main.formatDisplayNumber(grp.totalP) }}</td><td colspan="3"></td></tr></tfoot>
                  </table>
                </div>
              </div>
            </td></tr>
          </template>
        </tbody>
        <tfoot class="lp-tfoot"><tr><td :colspan="headers.length-1"></td><td class="text-center font-weight-bold">Σ = {{ main.formatDisplayNumber(totalWeight) }} Kg</td><td class="text-center font-weight-bold">Σ = {{ main.formatDisplayNumber(totalPrice) }} {{ main.currency }}</td><td></td></tr></tfoot>
      </table>
    </div>
  </v-card-text>
  <!-- TAB: Add Production -->
  <v-card-text v-else-if="activeTab === 1" class="pa-0">
    <AddProduction />
  </v-card-text>
  <v-dialog v-model="deleteDialog" max-width="450"><v-card class="rounded-xl"><v-card-title class="pa-5 pb-2 d-flex align-center"><v-icon color="error" class="mr-2">mdi-alert-circle-outline</v-icon>{{ $t('confirm_delete') }}</v-card-title><v-card-text class="pa-5 pt-2">{{ deleteBulk?$t('confirm_bulk_delete_message',{count:selected.length}):$t('confirm_delete_production_message') }}</v-card-text><v-card-actions class="pa-5 pt-0"><v-spacer></v-spacer><v-btn @click="deleteDialog=false" variant="text">{{ $t('cancel') }}</v-btn><v-btn color="error" variant="flat" rounded @click="execDelete" :loading="deleting">{{ $t('delete') }}</v-btn></v-card-actions></v-card></v-dialog>
</v-card>
<AuditLogDialog v-model="auditLogDialogOpen" auditable-type="Production" :auditable-id="auditLogRecordId" module-color="#00897B" />
</template>
<script>
import script from './script.js';
import AddProduction from '../AddProduction/index.vue';
import AuditLogDialog from '@/components/shared/AuditLogDialog.vue';
import { useAuth } from '@/stores/Auth';
export default {
  ...script,
  components:{...script.components,AddProduction,AuditLogDialog},
  data(){return{...script.data.call(this),activeTab:0,auditLogDialogOpen:false,auditLogRecordId:0,productionTabs:[{value:0,label:'list_production',icon:'mdi-view-list-outline'},...(useAuth().hasPermission('production.create')?[{value:1,label:'add_production',icon:'mdi-sticker-plus-outline'}]:[])]}},
  computed:{...script.computed,auth(){return useAuth()},selectAll:{get(){return this.selected.length===this.colFilteredItems.length&&this.colFilteredItems.length>0},set(v){this.selected=v?this.colFilteredItems.map(i=>i.id):[]}}},
  methods:{...script.methods,openAuditLog(id){this.auditLogRecordId=id;this.auditLogDialogOpen=true},colPinStyle(key,z){const side=this.pinSide(key);if(!side)return{};if(side==='left')return{position:'sticky',left:this.getPinLeft(key)+'px',zIndex:z,background:'#F5F5F5'};return{position:'sticky',right:this.getPinRight(key)+'px',zIndex:z,background:'#F5F5F5'};},measureColWidths(){this.$nextTick(()=>{const ths=this.$refs.tableWrap?.querySelectorAll('thead th');if(!ths)return;const w={};ths.forEach((th,i)=>{const key=i===0?'__checkbox':this.headers[i-1]?.key;if(key)w[key]=th.offsetWidth});this.colWidths=w})}},
  watch:{...script.watch,headers(){this.measureColWidths()},pinState:{handler(){this.measureColWidths()},deep:true},activeTab(v){const q=v===1?{tab:'add'}:{};if(JSON.stringify(this.$route.query)!==JSON.stringify(q))this.$router.replace({query:q}).catch(()=>{});if(v===0){this.fetchProductions();this.fetchAnalytics()}},showInvoice(v){if(v&&this.uninvoicedSelectedProductions.length){this.fetchNextTracking(this.invForm.type_invoice||'local')}}},
  mounted(){script.mounted.call(this);this.$nextTick(()=>this.measureColWidths());if(this.$route.query.tab==='add')this.activeTab=1},
  beforeUnmount(){if(script.beforeUnmount)script.beforeUnmount.call(this)},
  updated(){this.measureColWidths()},
};
</script>
<style scoped>
.lp-card{border-radius:20px!important;font-family:'Inter',sans-serif}
.lp-tab-slider{border-radius:12px}
.lp-hero{background:linear-gradient(135deg,rgb(0, 105, 92) 0%,rgb(0, 137, 123) 40%,rgb(38, 166, 154) 100%);border-radius:inherit;border-bottom-left-radius:0;border-bottom-right-radius:0;min-height:100px}
.lp-hero-inner{position:relative;z-index:1;padding:22px 26px;color:#fff;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px}
.lp-hero-icon{width:52px;height:52px;border-radius:14px;background:rgba(255,255,255,.18);display:flex;align-items:center;justify-content:center}
.lp-hero-title{font-size:19px;font-weight:800}.lp-hero-actions{display:flex;gap:8px;flex-wrap:wrap}
.adv-panel{border:1.5px solid rgba(0,137,123,.15);background:rgba(0,137,123,.03)}
.inv-panel{border:1.5px solid rgba(0,150,136,.15);background:rgba(0,150,136,.03)}
.lp-table-wrap{overflow:auto;max-height:calc(100vh - 300px);border-radius:12px;border:1px solid rgba(var(--v-border-color),.12)}
.lp-table{width:100%;border-collapse:collapse;font-size:13px}
.lp-thead{position:sticky;top:0;z-index:4;background:rgb(var(--v-theme-surface))}
.lp-th{padding:10px 12px;text-align:center;white-space:nowrap;border-bottom:2px solid rgba(var(--v-border-color),.12);background:rgb(var(--v-theme-surface));transition:background .15s}
.lp-th-pin{position:sticky!important;z-index:5!important;background:#F5F5F5!important;box-shadow:2px 0 4px -2px rgba(0,0,0,.1)}
.lp-th-dragover{background:rgba(0,137,123,.08)!important}
.lp-th-inner{display:flex;align-items:center;justify-content:center;gap:2px}
.lp-th-title{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.3px;opacity:.7;cursor:pointer}
.drag-handle{cursor:grab;opacity:.5;transition:opacity .15s}.drag-handle:hover{opacity:1}
.sort-badge-p{font-size:9px;background:#00897B;color:#fff;border-radius:50%;width:14px;height:14px;display:inline-flex;align-items:center;justify-content:center}
.lp-td{padding:8px 12px;text-align:center;border-bottom:1px solid rgba(var(--v-border-color),.06);white-space:nowrap}
.lp-col-pin{position:sticky!important;z-index:2!important;background:#F5F5F5!important;box-shadow:2px 0 4px -2px rgba(0,0,0,.1)}
.lp-row:hover{background:rgba(0,137,123,.04)}.lp-row-expanded{background:rgba(0,137,123,.06)}
.lp-row-invoiced{background:rgba(76,175,80,.08)!important}
.lp-row-invoiced:hover{background:rgba(76,175,80,.14)!important}
.lp-tfoot td{padding:10px 12px;border-top:2px solid rgba(0,137,123,.2);color:#00897B;font-size:14px}
.lp-expand-content{padding:12px 24px;background:rgba(var(--v-border-color),.03)}
.lp-detail-group{border:1px solid rgba(var(--v-border-color),.12);border-radius:10px;overflow:hidden}
.lp-detail-group-header{padding:8px 14px;background:rgba(0,137,123,.06);font-size:13px}
.lp-detail-table{width:100%;border-collapse:collapse;font-size:12px}
.lp-detail-table th{padding:6px 10px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.3px;opacity:.6;text-align:center;border-bottom:1px solid rgba(var(--v-border-color),.1)}
.lp-detail-table td{padding:6px 10px;text-align:center;border-bottom:1px solid rgba(var(--v-border-color),.05)}
.lp-detail-foot td{padding:8px 10px;background:rgba(0,137,123,.04);border-top:1px solid rgba(0,137,123,.15)}
.lp-panel-card{border:1.5px solid rgba(0,137,123,.12);border-radius:14px;padding:12px;background:rgba(var(--v-theme-surface),1)}
.lp-panel-header{font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;opacity:.7;margin-bottom:8px;display:flex;align-items:center}
.lp-stats-panel{max-height:400px}
.lp-stat-item{padding:8px 10px;border-radius:10px;background:rgba(0,137,123,.03);border:1px solid rgba(0,137,123,.06)}
</style>
