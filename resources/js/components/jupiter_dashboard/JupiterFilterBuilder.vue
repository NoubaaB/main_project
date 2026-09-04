<template>
  <v-card class="jupiter-filter-card rounded-xl mb-4" elevation="1">
    <v-card-text class="pa-4">
      <!-- Header -->
      <div class="d-flex align-center ga-3 mb-3">
        <v-icon style="color: #FF6F00">mdi-filter-variant</v-icon>
        <span class="text-subtitle-2 font-weight-bold">{{ $t('jupiter_filters') }}</span>
        <v-chip v-if="filterGroups.length" size="x-small" style="background: rgba(255,111,0,.12); color: #FF6F00" variant="flat">
          {{ filterGroups.length }}
        </v-chip>
        <v-spacer />
        <v-btn
          v-if="filterGroups.length"
          variant="tonal"
          color="warning"
          size="small"
          rounded
          prepend-icon="mdi-filter-off"
          @click="clearAll"
          id="jupiter-filter-clear-btn"
        >{{ $t('clear_filters') }}</v-btn>
        <v-btn
          variant="tonal"
          style="color: #FF6F00"
          size="small"
          rounded
          prepend-icon="mdi-plus"
          @click="addGroup"
          id="jupiter-filter-add-btn"
        >{{ $t('add_filter') }}</v-btn>
      </div>

      <!-- Filter Groups -->
      <div v-for="(group, gi) in filterGroups" :key="gi" class="jupiter-filter-group mb-3">
        <v-card variant="outlined" class="rounded-lg" style="border-color: rgba(255,111,0,.25)">
          <v-card-text class="pa-3">
            <!-- Group Header -->
            <div class="d-flex align-center ga-2 mb-3">
              <v-icon size="18" style="color: #FF6F00">mdi-filter-cog-outline</v-icon>
              <span class="text-caption font-weight-bold text-uppercase" style="color: #FF6F00">{{ $t('filter_group') }} #{{ gi + 1 }}</span>
              <v-spacer />
              <v-btn icon size="x-small" variant="text" color="error" @click="removeGroup(gi)" :id="'jupiter-filter-remove-' + gi">
                <v-icon size="16">mdi-close</v-icon>
              </v-btn>
            </div>

            <!-- Table Selector -->
            <v-select
              v-model="group.table"
              :items="tableOptions"
              item-title="label"
              item-value="value"
              :label="$t('filter_table')"
              variant="outlined"
              density="compact"
              rounded="lg"
              style="color: #FF6F00"
              color="#FF6F00"
              class="mb-3"
              hide-details
              @update:modelValue="onTableChange(gi)"
              :id="'jupiter-filter-table-' + gi"
            />

            <template v-if="group.table">
              <!-- Parent Rules -->
              <div class="mb-2">
                <div class="d-flex align-center ga-2 mb-2">
                  <v-icon size="14" style="color: #FF6F00">mdi-table</v-icon>
                  <span class="text-caption font-weight-medium">{{ $t('parent_rules') }}</span>
                  <v-spacer />
                  <v-btn size="x-small" variant="text" style="color: #FF6F00" @click="addRule(gi, 'rules')" :id="'jupiter-filter-add-rule-' + gi">
                    <v-icon start size="14">mdi-plus</v-icon>
                    {{ $t('add_rule') }}
                  </v-btn>
                </div>
                <div v-for="(rule, ri) in group.rules" :key="'r-' + ri" class="d-flex align-center ga-2 mb-2 flex-wrap">
                  <v-select
                    v-model="rule.column"
                    :items="getColumnsFor(group.table)"
                    item-title="label"
                    item-value="key"
                    :label="$t('filter_column')"
                    variant="outlined"
                    density="compact"
                    rounded="lg"
                    color="#FF6F00"
                    hide-details
                    style="max-width: 200px"
                    @update:modelValue="onColumnChange(rule, group.table)"
                  />
                  <v-select
                    v-model="rule.operator"
                    :items="operatorOptions"
                    :label="$t('filter_operator')"
                    variant="outlined"
                    density="compact"
                    rounded="lg"
                    color="#FF6F00"
                    hide-details
                    style="max-width: 130px"
                    @update:modelValue="onOperatorChange(rule)"
                  />
                  <!-- Value input — select for category columns, dual inputs for between, text otherwise -->
                  <template v-if="rule.operator === 'between' && Array.isArray(rule.value)">
                    <v-text-field
                      v-model.number="rule.value[0]"
                      type="number"
                      :label="$t('min')"
                      variant="outlined"
                      density="compact"
                      rounded="lg"
                      color="#FF6F00"
                      hide-details
                      style="max-width: 120px"
                    />
                    <v-text-field
                      v-model.number="rule.value[1]"
                      type="number"
                      :label="$t('max')"
                      variant="outlined"
                      density="compact"
                      rounded="lg"
                      color="#FF6F00"
                      hide-details
                      style="max-width: 120px"
                    />
                  </template>
                  <template v-else-if="getColumnDef(rule.column, group.table)?.type === 'select'">
                    <v-select
                      v-model="rule.value"
                      :items="getCategoryItems(rule.column, group.table)"
                      item-title="label"
                      item-value="id"
                      :label="$t('filter_value')"
                      variant="outlined"
                      density="compact"
                      rounded="lg"
                      color="#FF6F00"
                      hide-details
                      style="max-width: 200px"
                    />
                  </template>
                  <template v-else>
                    <v-text-field
                      v-model="rule.value"
                      :type="getColumnDef(rule.column, group.table)?.type === 'number' ? 'number' : 'text'"
                      :label="$t('filter_value')"
                      variant="outlined"
                      density="compact"
                      rounded="lg"
                      color="#FF6F00"
                      hide-details
                      style="max-width: 180px"
                    />
                  </template>
                  <v-btn icon size="x-small" variant="text" color="error" @click="removeRule(gi, 'rules', ri)">
                    <v-icon size="14">mdi-close</v-icon>
                  </v-btn>
                </div>
                <div v-if="!group.rules.length" class="text-caption text-medium-emphasis ml-5">{{ $t('no_rules_added') }}</div>
              </div>

              <v-divider class="mb-2" />

              <!-- Detail Rules -->
              <div>
                <div class="d-flex align-center ga-2 mb-2">
                  <v-icon size="14" style="color: #FF6F00">mdi-table-row</v-icon>
                  <span class="text-caption font-weight-medium">{{ $t('detail_rules') }}</span>
                  <v-spacer />
                  <v-btn size="x-small" variant="text" style="color: #FF6F00" @click="addRule(gi, 'detail_rules')" :id="'jupiter-filter-add-detail-rule-' + gi">
                    <v-icon start size="14">mdi-plus</v-icon>
                    {{ $t('add_rule') }}
                  </v-btn>
                </div>
                <div v-for="(rule, ri) in group.detail_rules" :key="'dr-' + ri" class="d-flex align-center ga-2 mb-2 flex-wrap">
                  <v-select
                    v-model="rule.column"
                    :items="getDetailColumnsFor(group.table)"
                    item-title="label"
                    item-value="key"
                    :label="$t('filter_column')"
                    variant="outlined"
                    density="compact"
                    rounded="lg"
                    color="#FF6F00"
                    hide-details
                    style="max-width: 200px"
                    @update:modelValue="onColumnChange(rule, getDetailTableName(group.table))"
                  />
                  <v-select
                    v-model="rule.operator"
                    :items="operatorOptions"
                    :label="$t('filter_operator')"
                    variant="outlined"
                    density="compact"
                    rounded="lg"
                    color="#FF6F00"
                    hide-details
                    style="max-width: 130px"
                    @update:modelValue="onOperatorChange(rule)"
                  />
                  <template v-if="rule.operator === 'between' && Array.isArray(rule.value)">
                    <v-text-field
                      v-model.number="rule.value[0]"
                      type="number"
                      :label="$t('min')"
                      variant="outlined"
                      density="compact"
                      rounded="lg"
                      color="#FF6F00"
                      hide-details
                      style="max-width: 120px"
                    />
                    <v-text-field
                      v-model.number="rule.value[1]"
                      type="number"
                      :label="$t('max')"
                      variant="outlined"
                      density="compact"
                      rounded="lg"
                      color="#FF6F00"
                      hide-details
                      style="max-width: 120px"
                    />
                  </template>
                  <template v-else-if="getColumnDef(rule.column, getDetailTableName(group.table))?.type === 'select'">
                    <v-select
                      v-model="rule.value"
                      :items="getCategoryItems(rule.column, getDetailTableName(group.table))"
                      item-title="label"
                      item-value="id"
                      :label="$t('filter_value')"
                      variant="outlined"
                      density="compact"
                      rounded="lg"
                      color="#FF6F00"
                      hide-details
                      style="max-width: 200px"
                    />
                  </template>
                  <template v-else>
                    <v-text-field
                      v-model="rule.value"
                      :type="getColumnDef(rule.column, getDetailTableName(group.table))?.type === 'number' ? 'number' : 'text'"
                      :label="$t('filter_value')"
                      variant="outlined"
                      density="compact"
                      rounded="lg"
                      color="#FF6F00"
                      hide-details
                      style="max-width: 180px"
                    />
                  </template>
                  <v-btn icon size="x-small" variant="text" color="error" @click="removeRule(gi, 'detail_rules', ri)">
                    <v-icon size="14">mdi-close</v-icon>
                  </v-btn>
                </div>
                <div v-if="!group.detail_rules.length" class="text-caption text-medium-emphasis ml-5">{{ $t('no_rules_added') }}</div>
              </div>
            </template>
          </v-card-text>
        </v-card>
      </div>

      <!-- Apply button -->
      <div v-if="filterGroups.length" class="d-flex justify-end ga-2 mt-2">
        <v-btn
          variant="flat"
          style="background: #FF6F00; color: white"
          rounded
          prepend-icon="mdi-check"
          @click="apply"
          id="jupiter-filter-apply-btn"
        >{{ $t('apply_filters') }}</v-btn>
      </div>
    </v-card-text>
  </v-card>
</template>

<script>
import { useMain } from '@/stores/Main';
import axios from 'axios';

export default {
  name: 'JupiterFilterBuilder',
  emits: ['apply'],
  data() {
    return {
      filterGroups: [],
      filterOptions: null,
      loadingOptions: false,
    };
  },
  computed: {
    main() { return useMain(); },
    lang() { return this.main.lang?.code || 'en'; },
    tableOptions() {
      return [
        { label: this.$t('charges'),     value: 'charges' },
        { label: this.$t('production'),  value: 'productions' },
        { label: this.$t('shippings'),   value: 'shippings' },
      ];
    },
    operatorOptions() {
      return [
        { title: '=',       value: '=' },
        { title: '≠',       value: '!=' },
        { title: '>',       value: '>' },
        { title: '<',       value: '<' },
        { title: '≥',       value: '>=' },
        { title: '≤',       value: '<=' },
        { title: this.$t('between'), value: 'between' },
        { title: this.$t('like'),    value: 'like' },
      ];
    },
  },
  methods: {
    async fetchOptions() {
      if (this.filterOptions) return;
      this.loadingOptions = true;
      try {
        const { data } = await axios.get('/api/jupiter-dashboard/filter-options');
        this.filterOptions = data;
      } catch (e) {
        console.warn('[JupiterFilter] fetchOptions failed:', e);
      }
      this.loadingOptions = false;
    },
    addGroup() {
      this.fetchOptions();
      this.filterGroups.push({
        table: null,
        rules: [],
        detail_rules: [],
      });
    },
    removeGroup(idx) {
      this.filterGroups.splice(idx, 1);
    },
    clearAll() {
      this.filterGroups = [];
      this.$emit('apply', []);
    },
    addRule(gi, ruleType) {
      this.filterGroups[gi][ruleType].push({
        column: null,
        operator: '=',
        value: null,
      });
    },
    removeRule(gi, ruleType, ri) {
      this.filterGroups[gi][ruleType].splice(ri, 1);
    },
    onTableChange(gi) {
      // Reset rules when table changes
      this.filterGroups[gi].rules = [];
      this.filterGroups[gi].detail_rules = [];
    },
    onColumnChange(rule, tableName) {
      // Reset operator/value when column changes
      rule.operator = '=';
      rule.value = null;
    },
    onOperatorChange(rule) {
      // Initialize value as array for 'between', reset to null otherwise
      if (rule.operator === 'between') {
        rule.value = [0, 0];
      } else if (Array.isArray(rule.value)) {
        rule.value = null;
      }
    },
    getColumnsFor(table) {
      if (!this.filterOptions?.columns?.[table]) return [];
      return this.filterOptions.columns[table].map(c => ({
        ...c,
        label: this.$t(c.label) || c.key,
      }));
    },
    getDetailColumnsFor(table) {
      const detailTable = this.getDetailTableName(table);
      if (!this.filterOptions?.columns?.[detailTable]) return [];
      return this.filterOptions.columns[detailTable].map(c => ({
        ...c,
        label: this.$t(c.label) || c.key,
      }));
    },
    getDetailTableName(table) {
      const map = {
        charges: 'detail_charges',
        productions: 'detail_productions',
        shippings: 'detail_shippings',
      };
      return map[table] || '';
    },
    getColumnDef(columnKey, tableName) {
      if (!this.filterOptions?.columns) return null;
      // Search in the given table
      const cols = this.filterOptions.columns[tableName] || [];
      return cols.find(c => c.key === columnKey) || null;
    },
    getCategoryItems(columnKey, tableName) {
      if (!this.filterOptions) return [];
      const colDef = this.getColumnDef(columnKey, tableName);
      if (!colDef || !colDef.ref) return [];
      const refData = this.filterOptions[colDef.ref] || [];
      const lang = this.lang;
      return refData.map(item => ({
        id: item.id,
        label: item[`name_${lang}`] || item.name || `#${item.id}`,
      }));
    },
    apply() {
      // Build clean filter payload (strip empty rules)
      const payload = this.filterGroups
        .filter(g => g.table)
        .map(g => ({
          table: g.table,
          rules: g.rules.filter(r => r.column && r.value !== null && r.value !== ''),
          detail_rules: g.detail_rules.filter(r => r.column && r.value !== null && r.value !== ''),
        }));
      this.$emit('apply', payload);
    },
  },
};
</script>

<style scoped>
.jupiter-filter-card {
  border: 1px solid rgba(255, 111, 0, 0.15);
}

.jupiter-filter-group :deep(.v-field) {
  font-size: 13px;
}
</style>
