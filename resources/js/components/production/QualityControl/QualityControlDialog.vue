<template>
  <v-dialog v-model="dialogModel" max-width="1100" persistent scrollable>
    <v-card class="rounded-xl" style="font-family:'Inter',sans-serif">
      <!-- Hero -->
      <div style="background:linear-gradient(135deg,#006064,#00838F 40%,#26C6DA);padding:18px 22px;color:#fff;display:flex;align-items:center;border-radius:inherit;border-bottom-left-radius:0;border-bottom-right-radius:0">
        <div style="width:52px;height:52px;border-radius:14px;background:rgba(255,255,255,.18);display:flex;align-items:center;justify-content:center;margin-right:16px"><v-icon size="28" color="white">mdi-clipboard-check-outline</v-icon></div>
        <div><div style="font-size:18px;font-weight:800">{{ isEditing ? $t('edit_quality_control') : $t('add_quality_control') }}</div><div style="opacity:.7;font-size:12px">{{ productionLabel }}</div></div>
        <v-spacer></v-spacer>
        <v-btn icon variant="text" color="white" @click="close" size="small"><v-icon>mdi-close</v-icon></v-btn>
      </div>

      <v-card-text class="pa-5" style="max-height:68vh;overflow-y:auto">
        <!-- Existing QC Records List -->
        <div v-if="qcList.length" class="mb-5">
          <div class="text-subtitle-2 font-weight-bold mb-2 d-flex align-center">
            <v-icon color="#00838F" class="mr-2" size="20">mdi-format-list-bulleted</v-icon>{{ $t('qc_existing_records') }} ({{ qcList.length }})
            <v-spacer></v-spacer>
            <v-btn v-print="printAllCfg" size="small" variant="tonal" color="#00838F" rounded prepend-icon="mdi-printer" class="mr-2">{{ $t('qc_print_all') }}</v-btn>
            <v-btn size="small" variant="tonal" color="#00838F" rounded prepend-icon="mdi-plus" @click="newQc">{{ $t('qc_add_new') }}</v-btn>
          </div>
          <div v-for="qc in qcList" :key="qc.id" class="qc-list-item" :class="{'qc-list-active': editingQcId === qc.id}" @click="editQc(qc)">
            <v-icon size="18" :color="qc.overall_grade ? gradeColors[qc.overall_grade] : '#999'" class="mr-2">mdi-fish</v-icon>
            <span class="font-weight-bold mr-2">{{ qc.species || '-' }}</span>
            <v-chip v-if="qc.overall_grade" :color="gradeColors[qc.overall_grade]" variant="tonal" size="x-small" class="mr-2">{{ $t('qc_grade_'+qc.overall_grade) }}</v-chip>
            <v-chip v-if="qc.decision" :color="decisionColors[qc.decision]" variant="tonal" size="x-small">{{ $t('qc_decision_'+qc.decision) }}</v-chip>
            <v-spacer></v-spacer>
            <span class="text-caption text-medium-emphasis">{{ qc.inspection_date ? qc.inspection_date.substring(0,10) : '' }}</span>
            <v-icon size="16" color="#00838F" class="ml-2">mdi-pencil</v-icon>
          </div>
          <v-divider class="my-4"></v-divider>
        </div>

        <!-- Section A: Lot Info -->
        <div class="text-subtitle-2 font-weight-bold mb-3 d-flex align-center">
          <v-icon color="#00838F" class="mr-2" size="20">mdi-information-outline</v-icon>{{ $t('qc_lot_information') }}
        </div>
        <v-row dense>
          <v-col cols="12" sm="4"><v-text-field v-model="form.lot_id" :label="$t('qc_lot_id')" variant="outlined" density="compact" rounded color="#00838F" hide-details prepend-inner-icon="mdi-identifier" readonly></v-text-field></v-col>
          <v-col cols="12" sm="4"><v-autocomplete v-model="form.species" :items="fishTypeNames" :label="$t('qc_species')" variant="outlined" density="compact" rounded color="#00838F" hide-details prepend-inner-icon="mdi-fish" clearable></v-autocomplete></v-col>
          <v-col cols="12" sm="4"><v-text-field v-model="form.quantity_kg" :label="$t('qc_quantity_kg')" type="number" step="0.01" variant="outlined" density="compact" rounded color="#00838F" hide-details prepend-inner-icon="mdi-weight-kilogram" suffix="Kg"></v-text-field></v-col>
          <v-col cols="12" sm="4">
            <v-menu v-model="catchDateMenu" :close-on-content-click="false" location="bottom">
              <template #activator="{ props }">
                <v-text-field v-bind="props" v-model="form.catch_date" :label="$t('qc_catch_date')" variant="outlined" density="compact" rounded color="#00838F" hide-details prepend-inner-icon="mdi-calendar" readonly></v-text-field>
              </template>
              <v-date-picker v-model="catchDatePicker" @update:model-value="onCatchDatePick" color="#00838F" :min="minDate" :max="maxDate"></v-date-picker>
            </v-menu>
          </v-col>
          <v-col cols="12" sm="4">
            <v-select v-model="form.storage_type" :items="storageTypes" :label="$t('qc_storage_type')" variant="outlined" density="compact" rounded color="#00838F" hide-details prepend-inner-icon="mdi-snowflake"></v-select>
          </v-col>
          <v-col cols="12" sm="4">
            <v-select v-model="form.inspection_type" :items="inspectionTypes" :label="$t('qc_inspection_type')" variant="outlined" density="compact" rounded color="#00838F" hide-details prepend-inner-icon="mdi-clipboard-search-outline"></v-select>
          </v-col>
          <v-col cols="12" sm="4"><v-text-field v-model="form.inspector_name" :label="$t('qc_inspector')" variant="outlined" density="compact" rounded color="#00838F" hide-details prepend-inner-icon="mdi-account-hard-hat" readonly></v-text-field></v-col>
          <v-col cols="12" sm="4">
            <v-menu v-model="inspDateMenu" :close-on-content-click="false" location="bottom">
              <template #activator="{ props }">
                <v-text-field v-bind="props" v-model="form.inspection_date" :label="$t('qc_inspection_date')" variant="outlined" density="compact" rounded color="#00838F" hide-details prepend-inner-icon="mdi-calendar-check" readonly></v-text-field>
              </template>
              <v-date-picker v-model="inspDatePicker" @update:model-value="onInspDatePick" color="#00838F" :min="minDate" :max="maxDate"></v-date-picker>
            </v-menu>
          </v-col>
          <v-col cols="12" sm="4"><v-text-field v-model="form.temperature" :label="$t('qc_temperature')" type="number" step="0.1" variant="outlined" density="compact" rounded color="#00838F" hide-details prepend-inner-icon="mdi-thermometer" suffix="°C"></v-text-field></v-col>
        </v-row>

        <v-divider class="my-5"></v-divider>

        <!-- Section B: Freshness Grid -->
        <div class="text-subtitle-2 font-weight-bold mb-3 d-flex align-center">
          <v-icon color="#00838F" class="mr-2" size="20">mdi-format-list-checks</v-icon>{{ $t('qc_freshness_inspection') }}
        </div>

        <div class="qc-grid">
          <!-- Header row -->
          <div class="qc-grid-header" style="background:linear-gradient(135deg,#006064,#00838F 40%,#26C6DA)">
            <div class="qc-grid-criteria-h">{{ $t('qc_criteria') }}</div>
            <div v-for="g in grades" :key="g" class="qc-grid-grade-h">{{ $t('qc_grade_' + g) }}</div>
          </div>
          <!-- Criteria rows -->
          <div v-for="c in criteria" :key="c.key" class="qc-grid-row" :class="{'qc-row-nonadmis': form[c.key]==='non_admis'}">
            <div class="qc-grid-criteria-cell">
              <v-icon size="18" :color="form[c.key] ? gradeColors[form[c.key]] : '#78909C'" class="mr-2">{{ c.icon }}</v-icon>
              <span class="font-weight-bold">{{ $t('qc_' + c.key) }}</span>
            </div>
            <div v-for="g in grades" :key="g" class="qc-grid-grade-cell" :class="{'qc-grade-selected': form[c.key]===g}" :style="form[c.key]===g ? {background: gradeColors[g]+'18', borderColor: gradeColors[g]} : {}" @click="form[c.key]=g">
              <v-icon v-if="form[c.key]===g" size="16" :color="gradeColors[g]" class="mb-1">{{ gradeIcons[g] }}</v-icon>
              <div class="qc-desc">{{ c.descriptions[g] }}</div>
            </div>
          </div>
        </div>

        <v-divider class="my-5"></v-divider>

        <!-- Section C: Auto Result -->
        <div class="text-subtitle-2 font-weight-bold mb-3 d-flex align-center">
          <v-icon color="#00838F" class="mr-2" size="20">mdi-chart-box-outline</v-icon>{{ $t('qc_result') }}
        </div>
        <v-row dense>
          <v-col cols="6" sm="3">
            <div class="qc-result-card" :style="{borderColor: gradeResult.overall_grade ? gradeColors[gradeResult.overall_grade] : '#ccc'}">
              <div class="text-caption font-weight-bold text-medium-emphasis">{{ $t('qc_overall_grade') }}</div>
              <v-chip v-if="gradeResult.overall_grade" :color="gradeColors[gradeResult.overall_grade]" variant="flat" size="small" class="mt-1"><v-icon size="14" class="mr-1">{{ gradeIcons[gradeResult.overall_grade] }}</v-icon>{{ $t('qc_grade_' + gradeResult.overall_grade) }}</v-chip>
              <span v-else class="text-grey">—</span>
            </div>
          </v-col>
          <v-col cols="6" sm="3">
            <div class="qc-result-card" :style="{borderColor: gradeResult.decision ? decisionColors[gradeResult.decision] : '#ccc'}">
              <div class="text-caption font-weight-bold text-medium-emphasis">{{ $t('qc_decision') }}</div>
              <v-chip v-if="gradeResult.decision" :color="decisionColors[gradeResult.decision]" variant="flat" size="small" class="mt-1">{{ $t('qc_decision_' + gradeResult.decision) }}</v-chip>
              <span v-else class="text-grey">—</span>
            </div>
          </v-col>
          <v-col cols="6" sm="3">
            <div class="qc-result-card">
              <div class="text-caption font-weight-bold text-medium-emphasis">{{ $t('qc_approval_status') }}</div>
              <v-chip :color="approvalColors[form.approval_status] || '#999'" variant="tonal" size="small" class="mt-1">{{ $t('qc_approval_' + (form.approval_status||'pending')) }}</v-chip>
            </div>
          </v-col>
          <v-col cols="6" sm="3">
            <div class="qc-result-card">
              <v-checkbox v-model="form.reinspection_required" :label="$t('qc_reinspection_required')" color="#00838F" density="compact" hide-details></v-checkbox>
            </div>
          </v-col>
        </v-row>
        <v-row dense class="mt-2">
          <v-col cols="12" sm="6"><v-textarea v-model="form.notes" :label="$t('qc_notes')" variant="outlined" density="compact" rounded color="#00838F" rows="2" hide-details></v-textarea></v-col>
          <v-col cols="12" sm="6"><v-textarea v-model="form.corrective_action" :label="$t('qc_corrective_action')" variant="outlined" density="compact" rounded color="#00838F" rows="2" hide-details></v-textarea></v-col>
        </v-row>
      </v-card-text>

      <v-card-actions class="pa-5 pt-2">
        <v-btn v-if="isEditing" color="error" variant="tonal" rounded prepend-icon="mdi-delete" @click="confirmDeleteDialog=true" :disabled="saving">{{ $t('delete') }}</v-btn>
        <v-btn v-if="existingQcId" v-print="printCfg" color="#00838F" variant="tonal" rounded prepend-icon="mdi-printer" :disabled="saving">{{ $t('print') }}</v-btn>
        <v-spacer></v-spacer>
        <v-btn @click="close" variant="text">{{ $t('cancel') }}</v-btn>
        <v-btn color="#00838F" variant="flat" rounded @click="save" :loading="saving" :disabled="!isValid">{{ $t('save') }}</v-btn>
      </v-card-actions>
    </v-card>

    <!-- Delete Confirm -->
    <v-dialog v-model="confirmDeleteDialog" max-width="400">
      <v-card class="rounded-xl">
        <v-card-title class="d-flex align-center pa-5 pb-2"><v-icon color="error" class="mr-2">mdi-alert-circle-outline</v-icon>{{ $t('confirm_delete') }}</v-card-title>
        <v-card-text class="pa-5 pt-2">{{ $t('qc_confirm_delete_message') }}</v-card-text>
        <v-card-actions class="pa-5 pt-0"><v-spacer></v-spacer><v-btn @click="confirmDeleteDialog=false" variant="text">{{ $t('cancel') }}</v-btn><v-btn color="error" variant="flat" rounded @click="executeDelete" :loading="deleting">{{ $t('delete') }}</v-btn></v-card-actions>
      </v-card>
    </v-dialog>
  </v-dialog>

  <!-- Snackbar -->
  <v-snackbar v-model="snackbar.show" :timeout="4000" :color="snackbar.color" rounded="lg" location="top right" elevation="12">
    <div class="d-flex align-center"><v-icon class="mr-2">{{ snackbar.icon }}</v-icon><span class="text-subtitle-2 font-weight-medium">{{ snackbar.message }}</span></div>
    <template #actions><v-btn variant="text" size="small" icon="mdi-close" @click="snackbar.show=false"></v-btn></template>
  </v-snackbar>

  <!-- Print Area -->
  <div style="display:none"><div id="qc-print-area">
    <div style="padding:24px 32px;max-width:900px;margin:0 auto;font-family:'Segoe UI',Arial,sans-serif;font-size:13px;color:#1a1a1a">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px">
        <div><img :src="logoUrl" alt="Logo" style="height:52px" /></div>
        <div style="text-align:center;flex:1"><h1 style="font-size:20px;font-weight:800;color:#00695C;margin:0">{{ $t('qc_report_title') }}</h1><p style="font-size:12px;color:#607D8B;margin:2px 0 0">{{ productionLabel }}</p></div>
        <div style="text-align:right;font-size:11px;color:#546E7A"><div><b>{{ $t('date') }}:</b> {{ new Date().toLocaleDateString() }}</div><div><b>QC #{{ existingQcId }}</b></div></div>
      </div>
      <div style="height:3px;background:linear-gradient(90deg,#006064,#26C6DA,#006064);border-radius:2px;margin:12px 0 20px"></div>
      <!-- Print info -->
      <table style="width:100%;border-collapse:collapse;margin-bottom:16px">
        <tr><td class="pi-l">{{ $t('qc_lot_id') }}</td><td class="pi-v">{{ form.lot_id||'-' }}</td><td class="pi-l">{{ $t('qc_species') }}</td><td class="pi-v">{{ speciesDisplay }}</td></tr>
        <tr><td class="pi-l">{{ $t('qc_quantity_kg') }}</td><td class="pi-v">{{ form.quantity_kg ? form.quantity_kg+' Kg' : '-' }}</td><td class="pi-l">{{ $t('qc_temperature') }}</td><td class="pi-v">{{ form.temperature!=null ? form.temperature+'°C' : '-' }}</td></tr>
        <tr><td class="pi-l">{{ $t('qc_inspector') }}</td><td class="pi-v">{{ form.inspector_name||'-' }}</td><td class="pi-l">{{ $t('qc_inspection_date') }}</td><td class="pi-v">{{ form.inspection_date||'-' }}</td></tr>
      </table>
      <!-- Print grid -->
      <table style="width:100%;border-collapse:collapse">
        <thead><tr><th class="pg-h" style="text-align:left">{{ $t('qc_criteria') }}</th><th v-for="g in grades" :key="g" class="pg-h">{{ $t('qc_grade_'+g) }}</th></tr></thead>
        <tbody><tr v-for="c in criteria" :key="c.key">
          <td class="pg-c">{{ $t('qc_'+c.key) }}</td>
          <td v-for="g in grades" :key="g" class="pg-v" :style="form[c.key]===g ? {background: gradeColors[g]+'22', fontWeight:700} : {}">
            {{ form[c.key]===g ? '● '+c.descriptions[g] : c.descriptions[g] }}
          </td>
        </tr></tbody>
      </table>
      <!-- Print result -->
      <div style="display:flex;justify-content:center;gap:32px;margin:20px 0;padding:14px;background:#F5F5F5;border-radius:8px;border:1px solid #E0E0E0">
        <div style="text-align:center"><div style="font-size:11px;text-transform:uppercase;color:#78909C;font-weight:600">{{ $t('qc_overall_grade') }}</div><div style="font-size:22px;font-weight:800" :style="{color:gradeResult.overall_grade?gradeColors[gradeResult.overall_grade]:'#999'}">{{ gradeResult.overall_grade ? $t('qc_grade_'+gradeResult.overall_grade) : '-' }}</div></div>
        <div style="text-align:center"><div style="font-size:11px;text-transform:uppercase;color:#78909C;font-weight:600">{{ $t('qc_decision') }}</div><div style="font-size:22px;font-weight:800" :style="{color:gradeResult.decision?decisionColors[gradeResult.decision]:'#999'}">{{ gradeResult.decision ? $t('qc_decision_'+gradeResult.decision) : '-' }}</div></div>
      </div>
      <div v-if="form.notes" style="margin-bottom:8px"><b>{{ $t('qc_notes') }}:</b> {{ form.notes }}</div>
      <div v-if="form.corrective_action" style="margin-bottom:8px"><b>{{ $t('qc_corrective_action') }}:</b> {{ form.corrective_action }}</div>
      <!-- Signatures -->
      <div style="display:flex;justify-content:space-between;margin-top:48px;gap:60px">
        <div style="flex:1;text-align:center"><div style="border-bottom:1px solid #333;height:40px;margin-bottom:6px"></div><div style="font-size:11px;font-weight:600;color:#546E7A;text-transform:uppercase">{{ $t('qc_inspector') }}</div></div>
        <div style="flex:1;text-align:center"><div style="border-bottom:1px solid #333;height:40px;margin-bottom:6px"></div><div style="font-size:11px;font-weight:600;color:#546E7A;text-transform:uppercase">{{ $t('qc_supervisor_signature') }}</div></div>
      </div>
    </div>
  </div></div>

  <!-- Print All QC Records -->
  <div style="display:none"><div id="qc-print-all">
    <div v-for="(qc, idx) in qcList" :key="qc.id" :style="idx > 0 ? 'page-break-before:always' : ''" style="padding:24px 32px;max-width:900px;margin:0 auto;font-family:'Segoe UI',Arial,sans-serif;font-size:13px;color:#1a1a1a">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px">
        <div><img :src="logoUrl" alt="Logo" style="height:52px" /></div>
        <div style="text-align:center;flex:1"><h1 style="font-size:20px;font-weight:800;color:#00695C;margin:0">{{ $t('qc_report_title') }}</h1><p style="font-size:12px;color:#607D8B;margin:2px 0 0">{{ productionLabel }} — {{ qc.species || '' }}</p></div>
        <div style="text-align:right;font-size:11px;color:#546E7A"><div><b>{{ $t('date') }}:</b> {{ new Date().toLocaleDateString() }}</div><div><b>QC #{{ qc.id }}</b></div></div>
      </div>
      <div style="height:3px;background:linear-gradient(90deg,#006064,#26C6DA,#006064);border-radius:2px;margin:12px 0 20px"></div>
      <table style="width:100%;border-collapse:collapse;margin-bottom:16px">
        <tr><td class="pi-l">{{ $t('qc_lot_id') }}</td><td class="pi-v">{{ qc.lot_id||'-' }}</td><td class="pi-l">{{ $t('qc_species') }}</td><td class="pi-v">{{ qc.species||'-' }}</td></tr>
        <tr><td class="pi-l">{{ $t('qc_quantity_kg') }}</td><td class="pi-v">{{ qc.quantity_kg ? qc.quantity_kg+' Kg' : '-' }}</td><td class="pi-l">{{ $t('qc_temperature') }}</td><td class="pi-v">{{ qc.temperature!=null ? qc.temperature+'°C' : '-' }}</td></tr>
        <tr><td class="pi-l">{{ $t('qc_inspector') }}</td><td class="pi-v">{{ qc.inspector_name||'-' }}</td><td class="pi-l">{{ $t('qc_inspection_date') }}</td><td class="pi-v">{{ qc.inspection_date ? qc.inspection_date.substring(0,10) : '-' }}</td></tr>
      </table>
      <table style="width:100%;border-collapse:collapse">
        <thead><tr><th class="pg-h" style="text-align:left">{{ $t('qc_criteria') }}</th><th v-for="g in grades" :key="g" class="pg-h">{{ $t('qc_grade_'+g) }}</th></tr></thead>
        <tbody><tr v-for="c in criteria" :key="c.key">
          <td class="pg-c">{{ $t('qc_'+c.key) }}</td>
          <td v-for="g in grades" :key="g" class="pg-v" :style="qc[c.key]===g ? {background: gradeColors[g]+'22', fontWeight:700} : {}">
            {{ qc[c.key]===g ? '● '+c.descriptions[g] : c.descriptions[g] }}
          </td>
        </tr></tbody>
      </table>
      <div style="display:flex;justify-content:center;gap:32px;margin:20px 0;padding:14px;background:#F5F5F5;border-radius:8px;border:1px solid #E0E0E0">
        <div style="text-align:center"><div style="font-size:11px;text-transform:uppercase;color:#78909C;font-weight:600">{{ $t('qc_overall_grade') }}</div><div style="font-size:22px;font-weight:800" :style="{color:qc.overall_grade?gradeColors[qc.overall_grade]:'#999'}">{{ qc.overall_grade ? $t('qc_grade_'+qc.overall_grade) : '-' }}</div></div>
        <div style="text-align:center"><div style="font-size:11px;text-transform:uppercase;color:#78909C;font-weight:600">{{ $t('qc_decision') }}</div><div style="font-size:22px;font-weight:800" :style="{color:qc.decision?decisionColors[qc.decision]:'#999'}">{{ qc.decision ? $t('qc_decision_'+qc.decision) : '-' }}</div></div>
      </div>
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
import { useAuth } from '@/stores/Auth';
import { useCompanySettings } from '@/stores/CompanySettings';
import { CRITERIA, GRADES, GRADE_COLORS, GRADE_ICONS, DECISION_COLORS, APPROVAL_COLORS, computeGrade } from './qcCriteria';
import print from 'vue3-print-nb';

export default {
  name: 'QualityControlDialog',
  directives: { print },
  props: { modelValue: { type: Boolean, default: false }, production: { type: Object, default: null } },
  emits: ['update:modelValue', 'saved', 'deleted'],
  data() {
    return {
      form: this.emptyForm(), saving: false, deleting: false, existingQcId: null,
      qcList: [], editingQcId: null,
      confirmDeleteDialog: false,
      snackbar: { show: false, message: '', color: 'success', icon: 'mdi-check-circle' },
      printCfg: { id: 'qc-print-area', popTitle: 'Quality Control Report' },
      printAllCfg: { id: 'qc-print-all', popTitle: 'Quality Control Report' },
      catchDateMenu: false, inspDateMenu: false,
      catchDatePicker: null, inspDatePicker: null,
      criteria: CRITERIA, grades: GRADES,
      gradeColors: GRADE_COLORS, gradeIcons: GRADE_ICONS,
      decisionColors: DECISION_COLORS, approvalColors: APPROVAL_COLORS,
      storageTypes: [
        { title: this.$t('qc_fresh'), value: 'fresh' },
        { title: this.$t('qc_chilled'), value: 'chilled' },
        { title: this.$t('qc_frozen'), value: 'frozen' },
      ],
      inspectionTypes: [
        { title: this.$t('qc_reception'), value: 'reception' },
        { title: this.$t('qc_pre_freezing'), value: 'pre_freezing' },
        { title: this.$t('qc_post_freezing'), value: 'post_freezing' },
        { title: this.$t('qc_recheck'), value: 'recheck' },
        { title: this.$t('qc_export_release'), value: 'export_release' },
      ],
    };
  },
  computed: {
    main() { return useMain(); },
    auth() { return useAuth(); },
    logoUrl() {
      const cs = useCompanySettings();
      if (cs.settings.logo_invoice_url) return cs.settings.logo_invoice_url;
      return new URL('/storage/images/logo_login.png', window.location.origin).href;
    },
    fishTypeNames() {
      const lang = this.main.lang?.code || 'en';
      return (this.main.getFishTypes || []).map(ft => ft[`name_${lang}`] || ft.name).filter(Boolean);
    },
    dialogModel: { get() { return this.modelValue; }, set(v) { this.$emit('update:modelValue', v); } },
    isEditing() { return !!this.existingQcId; },
    gradeResult() { return computeGrade(this.form); },
    speciesDisplay() { return this.form.species || '-'; },
    minDate() {
      if (!this.production?.date) return undefined;
      const d = new Date(this.production.date);
      return new Date(d.getFullYear(), d.getMonth(), d.getDate());
    },
    maxDate() { const d = new Date(); return new Date(d.getFullYear(), d.getMonth(), d.getDate()); },
    isValid() {
      return this.form.inspection_date && CRITERIA.every(c => this.form[c.key] != null);
    },
    productionLabel() {
      if (!this.production) return '';
      const cat = this.production.production_category?.name || '';
      const date = this.production.date ? new Date(this.production.date).toLocaleDateString() : '';
      return `${cat} — ${date}`;
    },
  },
  watch: {
    modelValue(v) {
      if (v && this.production) this.loadExistingQc();
    },
  },
  methods: {
    formatDateVal(d) {
      if (!d) return '';
      const dt = new Date(d);
      const y = dt.getFullYear(); const m = String(dt.getMonth()+1).padStart(2,'0'); const day = String(dt.getDate()).padStart(2,'0');
      return `${y}-${m}-${day}`;
    },
    onCatchDatePick(val) { this.form.catch_date = this.formatDateVal(val); this.catchDateMenu = false; },
    onInspDatePick(val) { this.form.inspection_date = this.formatDateVal(val); this.inspDateMenu = false; },
    emptyForm() {
      const f = {
        lot_id: '', species: '', quantity_kg: null, catch_date: '', storage_type: 'fresh',
        inspection_type: 'reception', inspector_name: '', inspection_date: new Date().toISOString().substr(0, 10),
        temperature: null, notes: '', corrective_action: '', reinspection_required: false, approval_status: 'pending',
      };
      CRITERIA.forEach(c => { f[c.key] = null; });
      return f;
    },
    autoFillDefaults() {
      const rtn = this.production?.reception_tracking_number;
      this.form.lot_id = Array.isArray(rtn) ? rtn.join(', ') : (rtn || '');
      this.form.inspector_name = this.auth?.user?.name || '';
    },
    loadQcFromRecord(qc) {
      this.existingQcId = qc.id;
      this.form = {
        lot_id: qc.lot_id || '', species: qc.species || '', quantity_kg: qc.quantity_kg,
        catch_date: qc.catch_date ? qc.catch_date.substring(0, 10) : '',
        storage_type: qc.storage_type || 'fresh', inspection_type: qc.inspection_type || 'reception',
        inspector_name: qc.inspector_name || '', inspection_date: qc.inspection_date ? qc.inspection_date.substring(0, 10) : '',
        temperature: qc.temperature, notes: qc.notes || '', corrective_action: qc.corrective_action || '',
        reinspection_required: !!qc.reinspection_required, approval_status: qc.approval_status || 'pending',
        skin_grade: qc.skin_grade, eye_grade: qc.eye_grade, gills_grade: qc.gills_grade,
        abdomen_grade: qc.abdomen_grade, backbone_grade: qc.backbone_grade, organs_grade: qc.organs_grade,
        texture_grade: qc.texture_grade, odor_grade: qc.odor_grade,
      };
    },
    async loadExistingQc() {
      if (!this.production?.id) return;
      try {
        const res = await axios.get(`/api/quality-control/${this.production.id}`);
        const qcs = res.data.quality_controls || [];
        this.qcList = qcs;
        if (this.editingQcId) {
          const qc = qcs.find(q => q.id === this.editingQcId);
          if (qc) { this.loadQcFromRecord(qc); return; }
        }
        this.existingQcId = null;
        this.form = this.emptyForm();
        this.autoFillDefaults();
      } catch (e) {
        this.qcList = [];
        this.existingQcId = null;
        this.form = this.emptyForm();
        this.autoFillDefaults();
      }
    },
    async save() {
      this.saving = true;
      try {
        const payload = { ...this.form, ...this.gradeResult };
        if (!this.isEditing) {
          payload.production_id = this.production.id;
          await axios.post('/api/quality-control', payload);
          this.showSnackbar(this.$t('qc_created'), 'success', 'mdi-check-circle');
        } else {
          await axios.put(`/api/quality-control/${this.existingQcId}`, payload);
          this.showSnackbar(this.$t('qc_updated'), 'success', 'mdi-check-circle');
        }
        this.editingQcId = null; this.existingQcId = null;
        this.form = this.emptyForm(); this.autoFillDefaults();
        await this.loadExistingQc();
        this.$emit('saved');
      } catch (e) {
        this.showSnackbar(e.response?.data?.message || this.$t('qc_save_error'), 'error', 'mdi-alert-circle');
      }
      this.saving = false;
    },
    async executeDelete() {
      this.deleting = true;
      try {
        await axios.delete(`/api/quality-control/${this.existingQcId}`);
        this.showSnackbar(this.$t('qc_deleted'), 'success', 'mdi-check-circle');
        this.confirmDeleteDialog = false;
        this.editingQcId = null; this.existingQcId = null;
        this.form = this.emptyForm(); this.autoFillDefaults();
        await this.loadExistingQc();
        this.$emit('deleted');
      } catch (e) { this.showSnackbar(this.$t('qc_delete_error'), 'error', 'mdi-alert-circle'); }
      this.deleting = false;
    },
    editQc(qc) { this.editingQcId = qc.id; this.loadQcFromRecord(qc); },
    newQc() { this.editingQcId = null; this.existingQcId = null; this.form = this.emptyForm(); this.autoFillDefaults(); },
    showSnackbar(message, color, icon) { this.snackbar = { show: true, message, color, icon }; this.main.add_snackbar({ show: true, message, color }); },
    close() { this.dialogModel = false; this.existingQcId = null; this.editingQcId = null; this.qcList = []; this.form = this.emptyForm(); },
  },
};
</script>

<style scoped>
.qc-grid { border: 1px solid #e0e0e0; border-radius: 12px; overflow: hidden; }
.qc-grid-header { display: flex; color: #fff; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; }
.qc-grid-criteria-h { flex: 0 0 180px; padding: 10px 14px; }
.qc-grid-grade-h { flex: 1; padding: 10px 8px; text-align: center; }
.qc-grid-row { display: flex; border-top: 1px solid #e8e8e8; transition: background .15s; }
.qc-grid-row:hover { background: #f5fafa; }
.qc-row-nonadmis { background: #fff5f5 !important; }
.qc-grid-criteria-cell { flex: 0 0 180px; padding: 10px 14px; display: flex; align-items: center; font-size: 13px; }
.qc-grid-grade-cell { flex: 1; padding: 8px 6px; text-align: center; cursor: pointer; border-left: 1px solid #f0f0f0; border: 2px solid transparent; border-radius: 4px; margin: 4px 2px; transition: all .15s; }
.qc-grid-grade-cell:hover { background: rgba(0,131,143,.06); }
.qc-grade-selected { border-width: 2px !important; border-style: solid !important; }
.qc-desc { font-size: 10px; color: #78909C; line-height: 1.3; margin-top: 2px; }
.qc-result-card { padding: 14px; border-radius: 12px; border: 2px solid #e0e0e0; text-align: center; transition: border-color .2s; }
.qc-list-item { display: flex; align-items: center; padding: 10px 14px; border: 1px solid #e8e8e8; border-radius: 10px; margin-bottom: 6px; cursor: pointer; transition: all .15s; }
.qc-list-item:hover { background: #f5fafa; border-color: #00838F; }
.qc-list-active { background: rgba(0,131,143,.06) !important; border-color: #00838F !important; }
</style>

<style>
.pi-l { font-weight:700;font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:#546E7A;background:#F5F5F5;padding:8px 12px;border:1px solid #E0E0E0;width:18% }
.pi-v { font-size:13px;color:#212121;padding:8px 12px;border:1px solid #E0E0E0;width:32% }
.pg-h { background:#00695C;color:#fff;font-size:11px;font-weight:700;text-transform:uppercase;padding:10px 8px;border:1px solid #004D40;text-align:center }
.pg-c { padding:8px 12px;border:1px solid #E0E0E0;font-weight:600;font-size:13px }
.pg-v { padding:8px 8px;border:1px solid #E0E0E0;font-size:11px;text-align:center;color:#546E7A }
@media print { #qc-print-area { display: block !important; } #qc-print-all { display: block !important; } }
</style>
