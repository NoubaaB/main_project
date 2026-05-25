<template>
  <v-card class="view-prod-card" elevation="0">
    <div class="vp-hero"><div class="vp-hero-inner">
      <div class="d-flex align-center ga-4"><div class="vp-hero-icon"><v-icon size="28" color="white">mdi-eye</v-icon></div><div><div class="vp-hero-title">{{ $t('view_production') }} #{{ production?.id }}</div><div class="text-white text-caption" style="opacity:.7">{{ production ? main.formateDate(production.date) : '' }}</div></div></div>
      <div class="d-flex ga-2">
        <v-btn variant="tonal" color="white" rounded size="small" prepend-icon="mdi-arrow-left" @click="$router.push({ name: 'production_list' })">{{ $t('back') }}</v-btn>
        <v-btn variant="tonal" color="white" rounded size="small" prepend-icon="mdi-history" @click="auditDialog = true" v-if="production">{{ $t('audit_log') }}</v-btn>
      </div>
    </div></div>

    <!-- Audit Log Dialog -->
    <AuditLogDialog v-model="auditDialog" auditable-type="Production" :auditable-id="Number($route.params.id)" module-color="#00897B" />
    <v-card-text v-if="loading" class="pa-10 text-center"><v-progress-circular indeterminate color="#00897B"></v-progress-circular></v-card-text>
    <v-card-text v-else-if="production" class="pa-5">
      <v-row>
        <v-col cols="12" sm="6" md="3"><div class="info-card"><v-icon size="20" color="#00897B" class="mb-1">mdi-tag-outline</v-icon><div class="info-label">{{ $t('production_category') }}</div><div class="info-value">{{ production.production_category?.[`name_${lang}`] || production.production_category?.name || `#${production.production_category_id}` }}</div></div></v-col>
        <v-col cols="12" sm="6" md="3"><div class="info-card"><v-icon size="20" color="#00897B" class="mb-1">mdi-calendar</v-icon><div class="info-label">{{ $t('date') }}</div><div class="info-value">{{ main.formateDate(production.date) }}</div></div></v-col>
        <v-col cols="12" sm="6" md="3"><div class="info-card"><v-icon size="20" color="#00897B" class="mb-1">mdi-weight-kilogram</v-icon><div class="info-label">{{ $t('total_weight') }}</div><div class="info-value">{{ main.formatDisplayNumber(production.total_weight) }} Kg</div></div></v-col>
        <v-col cols="12" sm="6" md="3"><div class="info-card"><v-icon size="20" color="success" class="mb-1">mdi-cash</v-icon><div class="info-label">{{ $t('total_price') }}</div><div class="info-value">{{ main.formatDisplayNumber(production.total_price) }} {{ main.currency }}</div></div></v-col>
      </v-row>
      <div v-if="production.suppliers?.length" class="mt-4"><div class="text-subtitle-2 font-weight-bold mb-2"><v-icon size="18" color="orange" class="mr-2">mdi-truck-delivery</v-icon>{{ $t('suppliers') }}</div><div class="d-flex flex-wrap ga-2"><v-chip v-for="s in production.suppliers" :key="s.id" color="orange" variant="tonal">{{ s.name }}</v-chip></div></div>
      <div v-if="production.production_tracking_number?.length" class="mt-4"><div class="text-subtitle-2 font-weight-bold mb-2"><v-icon size="18" color="#00897B" class="mr-2">mdi-factory</v-icon>{{ $t('production_tracking_numbers') }}</div><div class="d-flex flex-wrap ga-2"><v-chip v-for="tn in production.production_tracking_number" :key="tn" color="#00897B" variant="tonal">{{ tn }}</v-chip></div></div>
      <div class="mt-5"><div class="text-subtitle-1 font-weight-bold mb-3"><v-icon size="20" color="#00897B" class="mr-2">mdi-table</v-icon>{{ $t('details') }}</div>
        <v-data-table :headers="detailHeaders" :items="production.detail_productions || []" density="comfortable" class="rounded-lg" hover>
          <template v-slot:[`item.article`]="{ item }"><span v-if="item.article_description">{{ item.article_description }}</span><span v-else-if="item.stockable_type">{{ main.getStockableName(item.stockable_id, item.stockable_type) }}</span><span v-else class="text-grey">-</span></template>
          <template v-slot:[`item.weight`]="{ item }">{{ item.weight ? main.formatDisplayNumber(item.weight) + ' Kg' : '-' }}</template>
          <template v-slot:[`item.total_price`]="{ item }">{{ item.total_price ? main.formatDisplayNumber(item.total_price) + ' ' + main.currency : '-' }}</template>
          <template v-slot:[`item.warehouse`]="{ item }">{{ item.warehouse ? item.warehouse.name : '-' }}</template>
        </v-data-table>
      </div>

      <!-- Analytics & Statistics -->
      <div class="mt-5">
        <div class="text-subtitle-1 font-weight-bold mb-3 d-flex align-center">
          <v-icon size="20" color="#00897B" class="mr-2">mdi-chart-box-outline</v-icon>{{ $t('analytics') }} & {{ $t('statistics') }}
        </div>
        <v-row dense>
          <!-- Weight & Price by Detail Category -->
          <v-col cols="12" md="5">
            <div class="vp-stat-panel">
              <div class="vp-stat-panel-header"><v-icon size="16" color="#00897B" class="mr-1">mdi-tag-multiple</v-icon>{{ $t('production_category') }}</div>
              <div v-for="(grp, gi) in detailCategoryStats" :key="gi" class="vp-stat-item mb-2">
                <div class="d-flex align-center justify-space-between mb-1">
                  <v-chip size="x-small" color="#00897B" variant="tonal" class="font-weight-bold">{{ grp.name }} ({{ grp.count }})</v-chip>
                  <v-chip size="x-small" color="#00897B" variant="elevated" class="font-weight-bold">{{ vpTotalWeight > 0 ? Math.round(grp.weight / vpTotalWeight * 1000) / 10 : 0 }}%</v-chip>
                </div>
                <v-progress-linear :model-value="vpTotalWeight > 0 ? (grp.weight / vpTotalWeight * 100) : 0" color="#00897B" bg-color="#B2DFDB" height="5" rounded class="mb-1"></v-progress-linear>
                <div class="d-flex flex-column ga-1 text-caption">
                  <div class="d-flex align-center ga-1"><v-icon size="12" color="#00897B">mdi-weight-kilogram</v-icon><span class="text-medium-emphasis">{{ $t('weight') }}:</span><b style="color:#00897B">{{ main.formatDisplayNumber(grp.weight) }} Kg</b></div>
                  <div class="d-flex align-center ga-1"><v-icon size="12" color="deep-orange">mdi-cash</v-icon><span class="text-medium-emphasis">{{ $t('total_price') }}:</span><b class="text-deep-orange">{{ main.formatDisplayNumber(grp.price) }} {{ main.currency }}</b></div>
                  <div v-if="grp.weight > 0" class="d-flex align-center ga-1"><v-icon size="12" color="blue">mdi-calculator</v-icon><span class="text-medium-emphasis">{{ $t('unit_price') }}:</span><b class="text-blue">{{ main.formatDisplayNumber(grp.price / grp.weight) }} {{ main.currency }}/Kg</b></div>
                </div>
              </div>
              <div v-if="!detailCategoryStats.length" class="text-center text-caption text-medium-emphasis pa-4">—</div>
            </div>
          </v-col>

          <!-- Production Balance -->
          <v-col cols="12" md="4">
            <div class="vp-stat-panel">
              <div class="vp-stat-panel-header"><v-icon size="16" color="#00897B" class="mr-1">mdi-scale-balance</v-icon>{{ $t('production_balance') }}</div>
              <!-- Fresh Reception from Charge -->
              <div class="vp-stat-item mb-2">
                <div class="d-flex align-center ga-2 mb-1">
                  <v-icon size="16" color="#42A5F5">mdi-fishbowl-outline</v-icon>
                  <span class="text-caption font-weight-bold">{{ $t('fresh_reception') }}</span>
                  <v-spacer></v-spacer>
                  <b style="color:#42A5F5">{{ main.formatDisplayNumber(vpFreshReception) }} Kg</b>
                </div>
                <v-progress-linear :model-value="vpFreshReception > 0 ? 100 : 0" color="#42A5F5" bg-color="#BBDEFB" height="4" rounded></v-progress-linear>
              </div>
              <!-- Frozen Production -->
              <div class="vp-stat-item mb-2">
                <div class="d-flex align-center ga-2 mb-1">
                  <v-icon size="16" color="#AB47BC">mdi-snowflake</v-icon>
                  <span class="text-caption font-weight-bold">{{ $t('frozen_production') }}</span>
                  <v-spacer></v-spacer>
                  <b style="color:#AB47BC">{{ main.formatDisplayNumber(vpFrozenProduction) }} Kg</b>
                </div>
                <v-progress-linear :model-value="vpFreshReception > 0 ? (vpFrozenProduction / vpFreshReception * 100) : 0" color="#AB47BC" bg-color="#E1BEE7" height="4" rounded></v-progress-linear>
              </div>
              <!-- Guano Production -->
              <div class="vp-stat-item mb-2">
                <div class="d-flex align-center ga-2 mb-1">
                  <v-icon size="16" color="#8D6E63">mdi-leaf</v-icon>
                  <span class="text-caption font-weight-bold">{{ $t('guano_production') }}</span>
                  <v-spacer></v-spacer>
                  <b style="color:#8D6E63">{{ main.formatDisplayNumber(vpGuanoProduction) }} Kg</b>
                </div>
                <v-progress-linear :model-value="vpFreshReception > 0 ? (vpGuanoProduction / vpFreshReception * 100) : 0" color="#8D6E63" bg-color="#D7CCC8" height="4" rounded></v-progress-linear>
              </div>
              <v-divider class="my-2"></v-divider>
              <!-- Equilibrium -->
              <div class="vp-stat-item" :style="{borderColor: vpEquilibrium >= 0 ? 'rgba(76,175,80,.3)' : 'rgba(239,83,80,.3)', background: vpEquilibrium >= 0 ? 'rgba(76,175,80,.05)' : 'rgba(239,83,80,.05)'}">
                <div class="d-flex align-center ga-2">
                  <v-icon size="18" :color="vpEquilibrium >= 0 ? '#4CAF50' : '#EF5350'">{{ vpEquilibrium >= 0 ? 'mdi-arrow-up-bold-circle' : 'mdi-arrow-down-bold-circle' }}</v-icon>
                  <span class="text-caption font-weight-bold">{{ $t('equilibrium') }}</span>
                  <v-spacer></v-spacer>
                  <b :style="{color: vpEquilibrium >= 0 ? '#4CAF50' : '#EF5350', fontSize: '15px'}">{{ main.formatDisplayNumber(Math.abs(vpEquilibrium)) }} Kg</b>
                  <v-chip size="x-small" :color="vpEquilibrium >= 0 ? 'success' : 'error'" variant="tonal" class="font-weight-bold">{{ $t(vpEquilibrium >= 0 ? 'surplus' : 'shortage') }}</v-chip>
                </div>
              </div>
              <v-divider class="my-2"></v-divider>
              <!-- Yield -->
              <div v-if="vpFreshReception > 0" class="d-flex flex-column ga-1">
                <div class="d-flex align-center ga-1"><v-icon size="14" color="#FF9800">mdi-percent</v-icon><span class="text-caption font-weight-bold">{{ $t('yield') }} ({{ $t('frozen_production') }}):</span><b style="color:#FF9800" class="text-caption">{{ Math.round(vpFrozenProduction / vpFreshReception * 1000) / 10 }}%</b></div>
                <div class="d-flex align-center ga-1"><v-icon size="14" color="#795548">mdi-percent</v-icon><span class="text-caption font-weight-bold">{{ $t('yield') }} ({{ $t('guano_production') }}):</span><b style="color:#795548" class="text-caption">{{ Math.round(vpGuanoProduction / vpFreshReception * 1000) / 10 }}%</b></div>
                <div class="d-flex align-center ga-1"><v-icon size="14" color="#00897B">mdi-percent</v-icon><span class="text-caption font-weight-bold">{{ $t('yield') }} ({{ $t('total') }}):</span><b style="color:#00897B" class="text-caption">{{ Math.round((vpFrozenProduction + vpGuanoProduction) / vpFreshReception * 1000) / 10 }}%</b></div>
              </div>
              <!-- Fish Process Type Breakdown -->
              <template v-if="vpProcessTypeStats.length">
                <v-divider class="my-2"></v-divider>
                <div class="vp-stat-panel-header" style="margin-bottom:6px"><v-icon size="14" color="#FF9800" class="mr-1">mdi-cog-outline</v-icon>{{ $t('fish_process_type') }}</div>
                <div v-for="pt in vpProcessTypeStats" :key="pt.id" class="vp-stat-item mb-2">
                  <div class="d-flex align-center ga-2 mb-1">
                    <div class="vp-pt-dot" :style="{background: pt.color}"></div>
                    <span class="text-caption font-weight-bold">{{ pt.name }}</span>
                    <v-spacer></v-spacer>
                    <b :style="{color: pt.color}" class="text-caption">{{ main.formatDisplayNumber(pt.productionWeight) }} Kg</b>
                  </div>
                  <v-progress-linear :model-value="vpTotalWeight > 0 ? (pt.productionWeight / vpTotalWeight * 100) : 0" :color="pt.color" bg-color="#E0E0E0" height="4" rounded class="mb-1"></v-progress-linear>
                  <div class="d-flex align-center ga-2 text-caption">
                    <span class="text-medium-emphasis">{{ $t('charge') }}: <b :style="{color: '#42A5F5'}">{{ main.formatDisplayNumber(pt.chargeWeight) }} Kg</b></span>
                    <v-spacer></v-spacer>
                    <span class="text-medium-emphasis">{{ $t('yield') }}: <b :style="{color: pt.color}">{{ pt.chargeWeight > 0 ? Math.round(pt.productionWeight / pt.chargeWeight * 1000) / 10 : 0 }}%</b></span>
                  </div>
                </div>
              </template>
            </div>
          </v-col>

          <!-- Donut Chart -->
          <v-col cols="12" md="3">
            <div class="vp-stat-panel" style="height:100%;display:flex;flex-direction:column">
              <div class="vp-stat-panel-header"><v-icon size="16" color="#00897B" class="mr-1">mdi-chart-donut</v-icon>{{ $t('weight_distribution') }}</div>
              <apexchart v-if="balanceDonutSeries.some(v => v > 0)" type="donut" :height="240" :options="balanceDonutOptions" :series="balanceDonutSeries"></apexchart>
              <div v-else class="d-flex align-center justify-center flex-grow-1" style="opacity:.3"><v-icon size="48" color="grey">mdi-chart-donut</v-icon></div>
            </div>
          </v-col>
        </v-row>
      </div>

      <!-- Quality Control -->
      <div v-if="production.production_category && production.production_category.enable_quality_control" class="mt-5">
        <div class="text-subtitle-1 font-weight-bold mb-3 d-flex align-center">
          <v-icon size="20" color="#00838F" class="mr-2">mdi-clipboard-check-outline</v-icon>{{ $t('quality_control') }}
          <v-chip v-if="production.quality_controls && production.quality_controls.length" size="small" color="#00838F" variant="tonal" class="ml-2">{{ production.quality_controls.length }}</v-chip>
          <v-spacer></v-spacer>
          <v-btn v-if="production.quality_controls && production.quality_controls.length" v-print="vpPrintCfg" size="small" variant="tonal" color="#00838F" rounded prepend-icon="mdi-printer" class="mr-2">{{ $t('qc_print_all') }}</v-btn>
          <v-btn size="small" variant="tonal" color="#00838F" rounded prepend-icon="mdi-plus" @click="qcDialogOpen = true">{{ $t('add_quality_control') }}</v-btn>
        </div>

        <div v-if="production.quality_controls && production.quality_controls.length">
          <div v-for="qc in production.quality_controls" :key="qc.id" class="qc-summary-card mb-3" style="cursor:pointer" @click="openEditQc(qc.id)">
            <div class="d-flex align-center mb-2">
              <v-icon size="20" color="#00838F" class="mr-2">mdi-fish</v-icon>
              <span class="text-subtitle-2 font-weight-bold">{{ qc.species || '-' }}</span>
              <v-spacer></v-spacer>
              <v-chip v-if="qc.overall_grade" :color="gradeColors[qc.overall_grade]" variant="flat" size="small" class="mr-2"><v-icon size="14" class="mr-1">{{ gradeIcons[qc.overall_grade] }}</v-icon>{{ $t('qc_grade_'+qc.overall_grade) }}</v-chip>
              <v-chip v-if="qc.decision" :color="decisionColors[qc.decision]" variant="tonal" size="small">{{ $t('qc_decision_'+qc.decision) }}</v-chip>
            </div>
            <v-row dense>
              <v-col cols="6" sm="3"><div class="info-card"><div class="info-label">{{ $t('qc_inspector') }}</div><div class="info-value">{{ qc.inspector_name||'-' }}</div></div></v-col>
              <v-col cols="6" sm="3"><div class="info-card"><div class="info-label">{{ $t('qc_inspection_date') }}</div><div class="info-value">{{ qc.inspection_date ? main.formateDate(qc.inspection_date) : '-' }}</div></div></v-col>
              <v-col v-for="c in criteriaKeys" :key="c" cols="3">
                <div class="info-card" style="padding:6px">
                  <div class="info-label" style="font-size:9px">{{ $t('qc_'+c) }}</div>
                  <v-chip v-if="qc[c]" :color="gradeColors[qc[c]]" variant="tonal" size="x-small" class="mt-1">{{ $t('qc_grade_'+qc[c]) }}</v-chip>
                  <span v-else class="text-grey text-caption">—</span>
                </div>
              </v-col>
            </v-row>
            <div class="mt-2 d-flex align-center ga-2 flex-wrap">
              <v-chip :color="approvalColors[qc.approval_status]" variant="tonal" size="x-small">{{ $t('qc_approval_'+qc.approval_status) }}</v-chip>
              <v-btn v-if="qc.approval_status!=='approved'" size="x-small" variant="tonal" color="success" rounded prepend-icon="mdi-check" @click.stop="approveQc(qc.id,'approved')">{{ $t('qc_approve') }}</v-btn>
              <v-btn v-if="qc.approval_status!=='rejected'" size="x-small" variant="tonal" color="error" rounded prepend-icon="mdi-close" @click.stop="approveQc(qc.id,'rejected')">{{ $t('qc_reject') }}</v-btn>
            </div>
          </div>
        </div>

        <div v-else class="text-center pa-6" style="border:1px dashed rgba(0,131,143,.2);border-radius:12px;background:rgba(0,131,143,.02)">
          <v-icon size="40" color="grey-lighten-1">mdi-clipboard-text-off-outline</v-icon>
          <div class="text-body-2 text-medium-emphasis mt-2">{{ $t('qc_no_record') }}</div>
        </div>
      </div>
    </v-card-text>
  </v-card>
  <QualityControlDialog v-model="qcDialogOpen" :production="production" @saved="fetchProduction" @deleted="fetchProduction" />

  <!-- Hidden print area - prints ALL QC records -->
  <div style="display:none"><div id="vp-qc-print" v-if="production && production.quality_controls && production.quality_controls.length">
    <div v-for="(qc, idx) in production.quality_controls" :key="qc.id" :style="idx > 0 ? 'page-break-before:always' : ''" style="padding:24px 32px;max-width:900px;margin:0 auto;font-family:'Segoe UI',Arial,sans-serif;font-size:13px;color:#1a1a1a">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px">
        <div><img :src="logoUrl" alt="Logo" style="height:52px" /></div>
        <div style="text-align:center;flex:1"><h1 style="font-size:20px;font-weight:800;color:#00695C;margin:0">{{ $t('qc_report_title') }}</h1><p style="font-size:12px;color:#607D8B;margin:2px 0 0">{{ vpProductionLabel }} — {{ qc.species || '' }}</p></div>
        <div style="text-align:right;font-size:11px;color:#546E7A"><div><b>{{ $t('date') }}:</b> {{ new Date().toLocaleDateString() }}</div><div><b>QC #{{ qc.id }}</b></div></div>
      </div>
      <div style="height:3px;background:linear-gradient(90deg,#006064,#26C6DA,#006064);border-radius:2px;margin:12px 0 20px"></div>
      <table style="width:100%;border-collapse:collapse;margin-bottom:16px">
        <tr><td class="pi-l">{{ $t('qc_lot_id') }}</td><td class="pi-v">{{ qc.lot_id||'-' }}</td><td class="pi-l">{{ $t('qc_species') }}</td><td class="pi-v">{{ qc.species||'-' }}</td></tr>
        <tr><td class="pi-l">{{ $t('qc_quantity_kg') }}</td><td class="pi-v">{{ qc.quantity_kg ? qc.quantity_kg+' Kg' : '-' }}</td><td class="pi-l">{{ $t('qc_temperature') }}</td><td class="pi-v">{{ qc.temperature!=null ? qc.temperature+'°C' : '-' }}</td></tr>
        <tr><td class="pi-l">{{ $t('qc_inspector') }}</td><td class="pi-v">{{ qc.inspector_name||'-' }}</td><td class="pi-l">{{ $t('qc_inspection_date') }}</td><td class="pi-v">{{ qc.inspection_date ? main.formateDate(qc.inspection_date) : '-' }}</td></tr>
      </table>
      <table style="width:100%;border-collapse:collapse">
        <thead><tr><th class="pg-h" style="text-align:left">{{ $t('qc_criteria') }}</th><th v-for="g in vpGrades" :key="g" class="pg-h">{{ $t('qc_grade_'+g) }}</th></tr></thead>
        <tbody><tr v-for="c in vpCriteria" :key="c.key">
          <td class="pg-c">{{ $t('qc_'+c.key) }}</td>
          <td v-for="g in vpGrades" :key="g" class="pg-v" :style="qc[c.key]===g ? {background: gradeColors[g]+'22', fontWeight:700} : {}">{{ qc[c.key]===g ? '\u25cf '+c.descriptions[g] : c.descriptions[g] }}</td>
        </tr></tbody>
      </table>
      <div style="display:flex;justify-content:center;gap:32px;margin:20px 0;padding:14px;background:#F5F5F5;border-radius:8px;border:1px solid #E0E0E0">
        <div style="text-align:center"><div style="font-size:11px;text-transform:uppercase;color:#78909C;font-weight:600">{{ $t('qc_overall_grade') }}</div><div style="font-size:22px;font-weight:800" :style="{color:qc.overall_grade?gradeColors[qc.overall_grade]:'#999'}">{{ qc.overall_grade ? $t('qc_grade_'+qc.overall_grade) : '-' }}</div></div>
        <div style="text-align:center"><div style="font-size:11px;text-transform:uppercase;color:#78909C;font-weight:600">{{ $t('qc_decision') }}</div><div style="font-size:22px;font-weight:800" :style="{color:qc.decision?decisionColors[qc.decision]:'#999'}">{{ qc.decision ? $t('qc_decision_'+qc.decision) : '-' }}</div></div>
        <div style="text-align:center"><div style="font-size:11px;text-transform:uppercase;color:#78909C;font-weight:600">{{ $t('qc_approval_status') }}</div><div style="font-size:22px;font-weight:800" :style="{color:approvalColors[qc.approval_status]||'#999'}">{{ qc.approval_status ? $t('qc_approval_'+qc.approval_status) : '-' }}</div></div>
      </div>
      <div v-if="qc.approver" style="margin-bottom:8px"><b>{{ $t('qc_approved_by') }}:</b> {{ qc.approver.name }}</div>
      <div v-if="qc.notes" style="margin-bottom:8px"><b>{{ $t('qc_notes') }}:</b> {{ qc.notes }}</div>
      <div v-if="qc.corrective_action" style="margin-bottom:8px"><b>{{ $t('qc_corrective_action') }}:</b> {{ qc.corrective_action }}</div>
      <div style="display:flex;justify-content:space-between;margin-top:48px;gap:60px">
        <div style="flex:1;text-align:center"><div style="border-bottom:1px solid #333;height:40px;margin-bottom:6px"></div><div style="font-size:11px;font-weight:600;color:#546E7A;text-transform:uppercase">{{ $t('qc_inspector') }}</div></div>
        <div style="flex:1;text-align:center"><div style="border-bottom:1px solid #333;height:40px;margin-bottom:6px"></div><div style="font-size:11px;font-weight:600;color:#546E7A;text-transform:uppercase">{{ $t('qc_supervisor_signature') }}</div></div>
      </div>
    </div>
  </div></div>
</template>

<script>
import axios from 'axios';
import { useMain } from '@/stores/Main';
import { useFishProcessType } from '@/stores/FishProcessType';
import QualityControlDialog from '../QualityControl/QualityControlDialog.vue';
import AuditLogDialog from '@/components/shared/AuditLogDialog.vue';
import { CRITERIA, GRADES, GRADE_COLORS, GRADE_ICONS, DECISION_COLORS, APPROVAL_COLORS } from '../QualityControl/qcCriteria';
import print from 'vue3-print-nb';

export default {
  name: 'ViewProduction',
  components: { QualityControlDialog, AuditLogDialog },
  directives: { print },
  data() { return { production: null, loading: true, qcDialogOpen: false, auditDialog: false, vpPrintCfg: { id: 'vp-qc-print', popTitle: 'Quality Control Report' } }; },
  computed: {
    main() { return useMain(); },
    lang() { return this.main.lang?.code || 'en'; },
    gradeColors() { return GRADE_COLORS; },
    gradeIcons() { return GRADE_ICONS; },
    decisionColors() { return DECISION_COLORS; },
    approvalColors() { return APPROVAL_COLORS; },
    criteriaKeys() { return ['skin_grade','eye_grade','gills_grade','abdomen_grade','backbone_grade','organs_grade','texture_grade','odor_grade']; },
    vpCriteria() { return CRITERIA; },
    vpGrades() { return GRADES; },
    logoUrl() { return new URL('/storage/images/logo_login.png', window.location.origin).href; },
    vpProductionLabel() {
      if (!this.production) return '';
      const cat = this.production.production_category?.name || '';
      const date = this.production.date ? new Date(this.production.date).toLocaleDateString() : '';
      return `${cat} — ${date}`;
    },
    vpSpeciesDisplay() {
      const sp = this.production?.quality_control?.species;
      return Array.isArray(sp) && sp.length ? sp.join(', ') : '-';
    },
    detailHeaders() { return [
      { title: this.$t('type_article'), key: 'article', align: 'center', sortable: false },
      { title: this.$t('weight'), key: 'weight', align: 'center' },
      { title: this.$t('total_price'), key: 'total_price', align: 'center' },
      { title: this.$t('warehouse'), key: 'warehouse', align: 'center', sortable: false },
    ]; },
    vpTotalWeight() { return (this.production?.detail_productions || []).reduce((s, d) => s + Number(d.weight || 0), 0); },
    vpTotalPrice() { return (this.production?.detail_productions || []).reduce((s, d) => s + Number(d.total_price || 0), 0); },
    detailCategoryStats() {
      const dps = this.production?.detail_productions || [];
      const map = {};
      dps.forEach(dc => {
        const catId = dc.detail_production_category_id || 'none';
        if (!map[catId]) {
          const cat = dc.detail_production_category;
          map[catId] = { name: cat ? (cat[`name_${this.lang}`] || cat.name || `#${catId}`) : this.$t('uncategorized'), weight: 0, price: 0, count: 0 };
        }
        map[catId].weight += Number(dc.weight || 0);
        map[catId].price += Number(dc.total_price || 0);
        map[catId].count++;
      });
      return Object.values(map).sort((a, b) => b.weight - a.weight);
    },
    vpFreshReception() {
      // Fresh fish weight from the linked charge's detail_charges
      const charge = this.production?.charge;
      if (!charge || !charge.detail_charges) return 0;
      return charge.detail_charges
        .filter(dc => dc.stockable_type === 'FishType' && dc.stockable && dc.stockable.type === 'fresh')
        .reduce((s, dc) => s + Number(dc.weight || 0), 0);
    },
    vpFrozenProduction() {
      // Frozen fish weight from production detail_productions
      return (this.production?.detail_productions || [])
        .filter(dp => dp.stockable_type === 'FishType' && dp.stockable && dp.stockable.type === 'frozen')
        .reduce((s, dp) => s + Number(dp.weight || 0), 0);
    },
    vpGuanoProduction() {
      // Guano weight from production detail_productions
      return (this.production?.detail_productions || [])
        .filter(dp => dp.stockable_type === 'GuanoType')
        .reduce((s, dp) => s + Number(dp.weight || 0), 0);
    },
    vpEquilibrium() {
      // Equilibrium = (Frozen + Guano) - Fresh Reception
      return Math.round((this.vpFrozenProduction + this.vpGuanoProduction - this.vpFreshReception) * 100) / 100;
    },
    balanceDonutSeries() {
      return [
        Math.round(this.vpFreshReception * 100) / 100,
        Math.round(this.vpFrozenProduction * 100) / 100,
        Math.round(this.vpGuanoProduction * 100) / 100,
      ];
    },
    balanceDonutOptions() {
      return {
        chart: { type: 'donut', fontFamily: 'Inter, sans-serif', background: 'transparent' },
        labels: [this.$t('fresh_reception'), this.$t('frozen_production'), this.$t('guano_production')],
        colors: ['#42A5F5', '#AB47BC', '#8D6E63'],
        legend: { position: 'bottom', fontSize: '11px', fontWeight: 600 },
        dataLabels: { enabled: true, formatter: (val) => Math.round(val) + '%', style: { fontSize: '11px', fontWeight: 700 } },
        tooltip: { y: { formatter: (v) => this.main.formatDisplayNumber(v) + ' Kg' } },
        plotOptions: { pie: { donut: { size: '60%' } } },
        theme: { mode: this.main.is_dark ? 'dark' : 'light' },
      };
    },
    fishProcessTypes() { return useFishProcessType().list; },
    vpProcessTypeStats() {
      // Yield per FishProcessType = (frozen production weight / fresh charge weight) * 100
      const ptMap = {};
      const lang = this.lang;
      // Frozen production weight per process type (output)
      (this.production?.detail_productions || [])
        .filter(dp => dp.stockable_type === 'FishType' && dp.stockable && dp.stockable.type === 'frozen')
        .forEach(dp => {
          const ptId = dp.stockable.fish_process_type_id;
          if (!ptId) return;
          if (!ptMap[ptId]) {
            const pt = this.fishProcessTypes.find(p => p.id === ptId);
            ptMap[ptId] = { id: ptId, name: pt ? (pt[`name_${lang}`] || pt.name || `#${ptId}`) : `#${ptId}`, color: pt?.productivity_color || '#FF9800', productionWeight: 0, chargeWeight: 0 };
          }
          ptMap[ptId].productionWeight += Number(dp.weight || 0);
        });
      // Fresh charge weight per process type (input)
      const charge = this.production?.charge;
      if (charge && charge.detail_charges) {
        charge.detail_charges
          .filter(dc => dc.stockable_type === 'FishType' && dc.stockable && dc.stockable.type === 'fresh')
          .forEach(dc => {
            const ptId = dc.stockable.fish_process_type_id;
            if (!ptId) return;
            if (!ptMap[ptId]) {
              const pt = this.fishProcessTypes.find(p => p.id === ptId);
              ptMap[ptId] = { id: ptId, name: pt ? (pt[`name_${lang}`] || pt.name || `#${ptId}`) : `#${ptId}`, color: pt?.productivity_color || '#FF9800', productionWeight: 0, chargeWeight: 0 };
            }
            ptMap[ptId].chargeWeight += Number(dc.weight || 0);
          });
      }
      return Object.values(ptMap).sort((a, b) => b.productionWeight - a.productionWeight);
    },
  },
  mounted() { this.fetchProduction(); },
  methods: {
    async fetchProduction() { this.loading = true; try { const res = await axios.get(`/api/productions/${this.$route.params.id}`); this.production = res.data.production; } catch (e) { this.production = null; } this.loading = false; },
    async approveQc(qcId, status) {
      try {
        await axios.post(`/api/quality-control/${qcId}/approve`, { approval_status: status });
        this.main.add_snackbar({ show: true, message: this.$t('qc_approval_updated'), color: 'success' });
        this.fetchProduction();
      } catch (e) { this.main.add_snackbar({ show: true, message: this.$t('qc_approval_error'), color: 'error' }); }
    },
    openEditQc(qcId) {
      this.qcDialogOpen = true;
    },
  },
};
</script>

<style scoped>
.view-prod-card { border-radius: 20px !important; font-family: 'Inter', sans-serif; }
.vp-hero { background: linear-gradient(135deg, rgb(0, 105, 92) 0%, rgb(0, 137, 123) 40%, rgb(38, 166, 154) 100%); border-radius: inherit; border-bottom-left-radius: 0; border-bottom-right-radius: 0; min-height: 80px; }
.vp-hero-inner { position: relative; z-index: 1; padding: 22px 26px; color: #fff; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; }
.vp-hero-icon { width: 48px; height: 48px; border-radius: 12px; background: rgba(255,255,255,.18); display: flex; align-items: center; justify-content: center; }
.vp-hero-title { font-size: 18px; font-weight: 800; }
.info-card { padding: 16px; border-radius: 14px; border: 1.5px solid rgba(var(--v-border-color), .1); text-align: center; }
.info-label { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .5px; color: rgba(var(--v-theme-on-surface), .5); }
.info-value { font-size: 15px; font-weight: 800; margin-top: 2px; }
.vp-stat-panel { border: 1.5px solid rgba(0,137,123,.12); border-radius: 14px; padding: 14px; background: rgba(var(--v-theme-surface),1); }
.vp-stat-panel-header { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: .4px; opacity: .7; margin-bottom: 10px; display: flex; align-items: center; }
.vp-stat-item { padding: 8px 10px; border-radius: 10px; background: rgba(0,137,123,.03); border: 1px solid rgba(0,137,123,.06); }
.vp-pt-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
</style>

<style>
.pi-l { font-weight:700;font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:#546E7A;background:#F5F5F5;padding:8px 12px;border:1px solid #E0E0E0;width:18% }
.pi-v { font-size:13px;color:#212121;padding:8px 12px;border:1px solid #E0E0E0;width:32% }
.pg-h { background:#00695C;color:#fff;font-size:11px;font-weight:700;text-transform:uppercase;padding:10px 8px;border:1px solid #004D40;text-align:center }
.pg-c { padding:8px 12px;border:1px solid #E0E0E0;font-weight:600;font-size:13px }
.pg-v { padding:8px 8px;border:1px solid #E0E0E0;font-size:11px;text-align:center;color:#546E7A }
@media print { #vp-qc-print { display: block !important; } }
</style>
