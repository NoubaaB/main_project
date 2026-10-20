<template>
  <v-card class="jupiter-filter-card rounded-2xl mb-4" elevation="1">
    <v-card-text class="pa-3 pa-sm-4">
      <!-- Header -->
      <div class="d-flex align-center justify-space-between flex-wrap ga-3 mb-2">
        <div class="d-flex align-center ga-3">
          <div class="filter-mini-icon">
            <v-icon size="20" color="#0891B2">mdi-filter-variant</v-icon>
          </div>
          <div>
            <div class="d-flex align-center ga-2">
              <span class="text-subtitle-2 font-weight-bold text-slate-800">{{ $t('jupiter_filters') }}</span>
              <v-chip
                size="x-small"
                variant="flat"
                class="font-weight-bold ml-1"
                :class="filterGroups.length ? 'active-filter-badge' : 'inactive-filter-badge'"
              >
                {{ filterGroups.length }} {{ $t('filter_groups_count') }}
              </v-chip>
            </div>
            <div class="text-caption text-medium-emphasis mt-1" style="font-size: 11px !important; line-height: 1.4;">
              {{ filterGroups.length ? $t('adjust_filters_hint') : $t('no_filter_groups_desc') }}
            </div>
          </div>
        </div>

        <!-- Action Buttons with generous padding and icon margins -->
        <div class="d-flex align-center flex-wrap ga-2.5">
          <v-btn
            v-if="filterGroups.length"
            variant="tonal"
            color="warning"
            size="small"
            rounded="pill"
            @click="clearAll"
            id="jupiter-filter-clear-btn"
            class="text-caption font-weight-bold px-3.5"
          >
            <v-icon start size="15" class="mr-1.5">mdi-filter-off</v-icon>
            {{ $t('clear_filters') }}
          </v-btn>

          <v-btn
            variant="flat"
            size="small"
            rounded="pill"
            @click="addGroup"
            id="jupiter-filter-add-btn"
            class="add-filter-btn text-caption font-weight-bold text-white text-none px-4"
          >
            <v-icon start size="15" class="mr-1.5">mdi-plus</v-icon>
            {{ $t('add_filter') }}
          </v-btn>
        </div>
      </div>

      <!-- Filter Groups List -->
      <v-expand-transition>
        <div v-if="filterGroups.length" class="mt-3 pt-3 border-t">
          <div v-for="(group, gi) in filterGroups" :key="gi" class="filter-group-card mb-3">
            <v-card variant="outlined" class="rounded-xl filter-group-inner">
              <v-card-text class="pa-3 pa-sm-4">
                <!-- Group Header -->
                <div class="d-flex align-center justify-space-between mb-3 flex-wrap ga-2">
                  <div class="d-flex align-center ga-2">
                    <span class="group-number-pill">#{{ gi + 1 }}</span>
                    <span class="text-caption font-weight-bold text-uppercase text-cyan-darken-3">
                      {{ $t('filter_group') }}
                    </span>
                    <v-chip v-if="group.table" size="x-small" :color="getTableColor(group.table)" variant="tonal" label>
                      {{ getTableLabel(group.table) }}
                    </v-chip>
                  </div>
                  <v-btn
                    icon="mdi-close"
                    size="x-small"
                    variant="text"
                    color="error"
                    @click="removeGroup(gi)"
                    :id="'jupiter-filter-remove-' + gi"
                  />
                </div>

                <!-- Table Selector -->
                <v-row dense class="mb-2">
                  <v-col cols="12" sm="6" md="4">
                    <v-select
                      v-model="group.table"
                      :items="tableOptions"
                      item-title="label"
                      item-value="value"
                      :label="$t('filter_table')"
                      variant="outlined"
                      density="compact"
                      rounded="lg"
                      color="#0891B2"
                      hide-details
                      @update:modelValue="onTableChange(gi)"
                      :id="'jupiter-filter-table-' + gi"
                    />
                  </v-col>
                </v-row>

                <!-- When Table is Selected -->
                <template v-if="group.table">
                  <!-- 1. Parent Rules -->
                  <div class="rules-section mb-3 pa-2 rounded-lg bg-slate-50">
                    <div class="d-flex align-center justify-space-between mb-2">
                      <div class="d-flex align-center ga-1.5">
                        <v-icon size="14" color="#0891B2">mdi-table</v-icon>
                        <span class="text-caption font-weight-bold">{{ $t('parent_rules') }}</span>
                      </div>
                      <v-btn
                        size="x-small"
                        variant="tonal"
                        color="#0891B2"
                        rounded="pill"
                        @click="addRule(gi, 'rules')"
                        :id="'jupiter-filter-add-rule-' + gi"
                      >
                        <v-icon start size="12">mdi-plus</v-icon>
                        {{ $t('add_rule') }}
                      </v-btn>
                    </div>

                    <div v-for="(rule, ri) in group.rules" :key="'r-' + ri" class="rule-row mb-2">
                      <v-row dense align="center">
                        <v-col cols="12" sm="4" md="4">
                          <v-select
                            v-model="rule.column"
                            :items="getColumnsFor(group.table)"
                            item-title="label"
                            item-value="key"
                            :label="$t('filter_column')"
                            variant="outlined"
                            density="compact"
                            rounded="lg"
                            color="#0891B2"
                            hide-details
                            @update:modelValue="onColumnChange(rule, group.table)"
                          />
                        </v-col>
                        <v-col cols="6" sm="3" md="2">
                          <v-select
                            v-model="rule.operator"
                            :items="operatorOptions"
                            :label="$t('filter_operator')"
                            variant="outlined"
                            density="compact"
                            rounded="lg"
                            color="#0891B2"
                            hide-details
                            @update:modelValue="onOperatorChange(rule)"
                          />
                        </v-col>
                        <v-col cols="6" sm="4" md="5">
                          <template v-if="rule.operator === 'between' && Array.isArray(rule.value)">
                            <div class="d-flex ga-1">
                              <v-text-field
                                v-model.number="rule.value[0]"
                                type="number"
                                :label="$t('min')"
                                variant="outlined"
                                density="compact"
                                rounded="lg"
                                color="#0891B2"
                                hide-details
                              />
                              <v-text-field
                                v-model.number="rule.value[1]"
                                type="number"
                                :label="$t('max')"
                                variant="outlined"
                                density="compact"
                                rounded="lg"
                                color="#0891B2"
                                hide-details
                              />
                            </div>
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
                              color="#0891B2"
                              hide-details
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
                              color="#0891B2"
                              hide-details
                            />
                          </template>
                        </v-col>
                        <v-col cols="12" sm="1" md="1" class="d-flex justify-end">
                          <v-btn icon size="x-small" variant="text" color="error" @click="removeRule(gi, 'rules', ri)">
                            <v-icon size="16">mdi-close</v-icon>
                          </v-btn>
                        </v-col>
                      </v-row>
                    </div>

                    <div v-if="!group.rules.length" class="text-caption text-medium-emphasis pa-1 font-italic">
                      {{ $t('no_rules_added') }}
                    </div>
                  </div>

                  <!-- 2. Detail Rules -->
                  <div class="rules-section pa-2 rounded-lg bg-slate-50">
                    <div class="d-flex align-center justify-space-between mb-2">
                      <div class="d-flex align-center ga-1.5">
                        <v-icon size="14" color="#0891B2">mdi-table-row</v-icon>
                        <span class="text-caption font-weight-bold">{{ $t('detail_rules') }}</span>
                      </div>
                      <v-btn
                        size="x-small"
                        variant="tonal"
                        color="#0891B2"
                        rounded="pill"
                        @click="addRule(gi, 'detail_rules')"
                        :id="'jupiter-filter-add-detail-rule-' + gi"
                      >
                        <v-icon start size="12">mdi-plus</v-icon>
                        {{ $t('add_rule') }}
                      </v-btn>
                    </div>

                    <div v-for="(rule, ri) in group.detail_rules" :key="'dr-' + ri" class="rule-row mb-2">
                      <v-row dense align="center">
                        <v-col cols="12" sm="4" md="4">
                          <v-select
                            v-model="rule.column"
                            :items="getDetailColumnsFor(group.table)"
                            item-title="label"
                            item-value="key"
                            :label="$t('filter_column')"
                            variant="outlined"
                            density="compact"
                            rounded="lg"
                            color="#0891B2"
                            hide-details
                            @update:modelValue="onColumnChange(rule, getDetailTableName(group.table))"
                          />
                        </v-col>
                        <v-col cols="6" sm="3" md="2">
                          <v-select
                            v-model="rule.operator"
                            :items="operatorOptions"
                            :label="$t('filter_operator')"
                            variant="outlined"
                            density="compact"
                            rounded="lg"
                            color="#0891B2"
                            hide-details
                            @update:modelValue="onOperatorChange(rule)"
                          />
                        </v-col>
                        <v-col cols="6" sm="4" md="5">
                          <template v-if="rule.operator === 'between' && Array.isArray(rule.value)">
                            <div class="d-flex ga-1">
                              <v-text-field
                                v-model.number="rule.value[0]"
                                type="number"
                                :label="$t('min')"
                                variant="outlined"
                                density="compact"
                                rounded="lg"
                                color="#0891B2"
                                hide-details
                              />
                              <v-text-field
                                v-model.number="rule.value[1]"
                                type="number"
                                :label="$t('max')"
                                variant="outlined"
                                density="compact"
                                rounded="lg"
                                color="#0891B2"
                                hide-details
                              />
                            </div>
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
                              color="#0891B2"
                              hide-details
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
                              color="#0891B2"
                              hide-details
                            />
                          </template>
                        </v-col>
                        <v-col cols="12" sm="1" md="1" class="d-flex justify-end">
                          <v-btn icon size="x-small" variant="text" color="error" @click="removeRule(gi, 'detail_rules', ri)">
                            <v-icon size="16">mdi-close</v-icon>
                          </v-btn>
                        </v-col>
                      </v-row>
                    </div>

                    <div v-if="!group.detail_rules.length" class="text-caption text-medium-emphasis pa-1 font-italic">
                      {{ $t('no_rules_added') }}
                    </div>
                  </div>
                </template>
              </v-card-text>
            </v-card>
          </div>

          <!-- Apply Filter CTA -->
          <div class="d-flex justify-end ga-2 mt-3">
            <v-btn
              variant="flat"
              rounded="pill"
              prepend-icon="mdi-check"
              @click="apply"
              id="jupiter-filter-apply-btn"
              class="apply-btn text-white font-weight-bold"
            >
              {{ $t('apply_filters') }}
            </v-btn>
          </div>
        </div>
      </v-expand-transition>
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
        { label: this.$t('charges'), value: 'charges' },
        { label: this.$t('production'), value: 'productions' },
        { label: this.$t('shippings'), value: 'shippings' },
      ];
    },
    operatorOptions() {
      return [
        { title: '=', value: '=' },
        { title: '≠', value: '!=' },
        { title: '>', value: '>' },
        { title: '<', value: '<' },
        { title: '≥', value: '>=' },
        { title: '≤', value: '<=' },
        { title: this.$t('between'), value: 'between' },
        { title: this.$t('like'), value: 'like' },
      ];
    },
  },
  methods: {
    getTableColor(table) {
      if (table === 'charges') return '#1565C0';
      if (table === 'productions') return '#00897B';
      if (table === 'shippings') return '#4643a0';
      return '#0891B2';
    },
    getTableLabel(table) {
      if (table === 'charges') return this.$t('charges');
      if (table === 'productions') return this.$t('production');
      if (table === 'shippings') return this.$t('shippings');
      return table;
    },
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
      if (!this.filterGroups.length) {
        this.apply();
      }
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
      this.filterGroups[gi].rules = [];
      this.filterGroups[gi].detail_rules = [];
    },
    onColumnChange(rule, tableName) {
      rule.operator = '=';
      rule.value = null;
    },
    onOperatorChange(rule) {
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
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(12px);
  border: 1px solid rgba(8, 145, 178, 0.18);
  box-shadow: 0 4px 20px rgba(8, 145, 178, 0.05);
  transition: all 0.25s ease;
}

:deep(.v-theme--dark) .jupiter-filter-card,
.v-theme--dark .jupiter-filter-card {
  background: rgba(15, 23, 42, 0.9);
  border: 1px solid rgba(6, 182, 212, 0.2);
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
}

.filter-mini-icon {
  width: 38px;
  height: 38px;
  border-radius: 10px;
  background: linear-gradient(135deg, rgba(8, 145, 178, 0.15) 0%, rgba(6, 182, 212, 0.08) 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid rgba(8, 145, 178, 0.2);
  flex-shrink: 0;
}

.active-filter-badge {
  background: rgba(8, 145, 178, 0.15) !important;
  color: #0891B2 !important;
  font-weight: 700;
}

.inactive-filter-badge {
  background: rgba(148, 163, 184, 0.15) !important;
  color: #64748B !important;
}

.add-filter-btn {
  background: linear-gradient(135deg, #0891B2 0%, #06B6D4 100%) !important;
  box-shadow: 0 2px 8px rgba(8, 145, 178, 0.25);
  white-space: nowrap;
}

.filter-group-inner {
  border-color: rgba(8, 145, 178, 0.22) !important;
  background: rgba(248, 250, 252, 0.6);
}

:deep(.v-theme--dark) .filter-group-inner,
.v-theme--dark .filter-group-inner {
  background: rgba(30, 41, 59, 0.6) !important;
  border-color: rgba(6, 182, 212, 0.25) !important;
}

.group-number-pill {
  font-size: 11px;
  font-weight: 800;
  padding: 2px 8px;
  border-radius: 6px;
  background: rgba(8, 145, 178, 0.15);
  color: #0891B2;
}

.rules-section {
  background: rgba(241, 245, 249, 0.6);
  border: 1px solid rgba(226, 232, 240, 0.8);
}

:deep(.v-theme--dark) .rules-section,
.v-theme--dark .rules-section {
  background: rgba(15, 23, 42, 0.5) !important;
  border-color: rgba(51, 65, 85, 0.6) !important;
}

.apply-btn {
  background: linear-gradient(135deg, #0891B2 0%, #06B6D4 100%) !important;
  box-shadow: 0 4px 12px rgba(8, 145, 178, 0.3);
}
</style>
