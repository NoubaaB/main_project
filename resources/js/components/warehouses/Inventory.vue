<template>
  <div class="inv-page">
    <!-- ═══════════ HEADER ═══════════ -->
    <div class="inv-header">
      <div class="inv-header-glow"></div>
      <div class="d-flex align-center ga-4 mb-1">
        <div class="inv-icon-wrap">
          <v-icon size="28" color="white">mdi-clipboard-check-outline</v-icon>
        </div>
        <div>
          <h1 class="inv-title">{{ $t('inventory_adjustment') }}</h1>
          <p class="inv-subtitle">{{ $t('inventory_subtitle') }}</p>
        </div>
      </div>
    </div>

    <!-- ═══════════ STEP 1: SELECT WAREHOUSE ═══════════ -->
    <v-card class="inv-card mb-6" elevation="0">
      <v-card-text class="pa-5">
        <div class="d-flex align-center ga-4 flex-wrap">
          <v-select
            v-model="selectedWarehouse"
            :items="warehouses"
            item-title="name"
            item-value="id"
            :label="$t('select_warehouse')"
            density="compact"
            variant="outlined"
            rounded
            color="info"
            prepend-inner-icon="mdi-warehouse"
            hide-details
            class="inv-wh-select text-info"
            @update:model-value="onWarehouseChange"
          ></v-select>
          <v-chip v-if="selectedWarehouse && warehouseInfo" color="info" variant="tonal" class="px-5 py-2" size="default">
            <v-icon start size="18">mdi-weight-kilogram</v-icon>
            {{ fmt(warehouseInfo.total_weight) }} KG
          </v-chip>
          <v-chip v-if="selectedWarehouse && warehouseInfo" color="warning" variant="tonal" class="px-5 py-2" size="default">
            <v-icon start size="18">mdi-cash-multiple</v-icon>
            {{ fmt(warehouseInfo.total_price) }} MAD
          </v-chip>
        </div>
      </v-card-text>
    </v-card>

    <!-- ═══════════ SKELETON: No warehouse selected ═══════════ -->
    <div v-if="!selectedWarehouse && !loadingArticles">
      <v-card class="inv-card mb-6" elevation="0">
        <v-card-text class="pa-5">
          <div class="d-flex align-center ga-4 mb-4">
            <v-skeleton-loader type="chip" width="140"></v-skeleton-loader>
            <v-skeleton-loader type="chip" width="120"></v-skeleton-loader>
          </div>
          <v-skeleton-loader type="heading" class="mb-4"></v-skeleton-loader>
          <v-skeleton-loader type="table-heading, table-row-divider@4, table-tfoot" class="rounded-lg"></v-skeleton-loader>
        </v-card-text>
      </v-card>
      <v-card class="inv-card" elevation="0">
        <v-card-text class="pa-5">
          <v-skeleton-loader type="list-item-two-line@3" class="rounded-lg"></v-skeleton-loader>
        </v-card-text>
      </v-card>
    </div>

    <!-- ═══════════ SKELETON: Loading articles ═══════════ -->
    <div v-else-if="loadingArticles">
      <v-card class="inv-card mb-6" elevation="0">
        <v-card-text class="pa-5">
          <div class="d-flex align-center ga-4 mb-4">
            <v-skeleton-loader type="chip" width="140"></v-skeleton-loader>
            <v-skeleton-loader type="text" width="200"></v-skeleton-loader>
          </div>
          <v-skeleton-loader type="table-heading, table-row-divider@5, table-tfoot" class="rounded-lg"></v-skeleton-loader>
        </v-card-text>
      </v-card>
      <v-card class="inv-card" elevation="0">
        <v-card-text class="pa-5">
          <div class="d-flex align-center ga-5">
            <v-skeleton-loader type="text" width="160"></v-skeleton-loader>
            <v-skeleton-loader type="text" class="flex-grow-1"></v-skeleton-loader>
            <v-skeleton-loader type="button" width="160"></v-skeleton-loader>
          </div>
        </v-card-text>
      </v-card>
    </div>

    <!-- ═══════════ STEP 2: ARTICLES TABLE ═══════════ -->
    <template v-if="selectedWarehouse && !loadingArticles && articles.length > 0">
      <v-card class="inv-card mb-6" elevation="0">
        <v-card-title class="d-flex align-center ga-4 pa-5">
          <v-icon start color="info">mdi-package-variant-closed</v-icon>
          {{ $t('articles_tracking') }}
          <v-spacer></v-spacer>
          <v-btn variant="tonal" color="info" size="small" rounded
            prepend-icon="mdi-plus-box" @click="openAddArticleDialog" class="mr-2">
            {{ $t('add_article') }}
          </v-btn>
          <v-btn variant="tonal" color="success" size="small" rounded
            prepend-icon="mdi-microsoft-excel" @click="exportToExcel" :disabled="!articles.length" class="mr-2">
            {{ $t('export_excel') }}
          </v-btn>
          <v-text-field
            v-model="articleSearch"
            :placeholder="$t('search_article')"
            density="compact"
            variant="outlined"
            rounded
            prepend-inner-icon="mdi-magnify"
            hide-details
            clearable
            class="inv-article-search text-info"
            color="info"
          ></v-text-field>
        </v-card-title>

        <v-card-text class="pa-0">
          <v-expansion-panels variant="accordion" v-model="expandedArticle">
            <v-expansion-panel
              v-for="article in filteredArticles"
              :key="article.stockable_id"
              class="inv-article-panel"
              elevation="0"
            >
              <v-expansion-panel-title>
                <div class="d-flex align-center w-100 pe-4">
                  <v-avatar :color="typeColor(article.stockable_type)" variant="tonal" rounded="lg" class="me-3" size="40">
                    <v-icon>{{ typeIcon(article.stockable_type) }}</v-icon>
                  </v-avatar>
                  <div class="flex-grow-1">
                    <div class="font-weight-bold">{{ article.article_name }}</div>
                    <div class="text-caption text-medium-emphasis">
                      {{ article.stockable_type }} · {{ article.tracking_numbers.length }} {{ $t('tracking_numbers') }}
                    </div>
                  </div>
                  <v-chip size="small" :color="article.total_weight > 0 ? 'success' : 'error'" variant="tonal" class="me-3">
                    {{ fmt(article.total_weight) }} KG
                  </v-chip>
                </div>
              </v-expansion-panel-title>

              <v-expansion-panel-text>
                <!-- Tracking Numbers Table -->
                <v-table density="comfortable" class="inv-tn-table">
                  <thead>
                    <tr>
                      <th>{{ $t('tracking_number') }}</th>
                      <th>{{ $t('date_entered') }}</th>
                      <th class="text-right">{{ $t('current_stock') }} (KG)</th>
                      <th class="text-right">{{ $t('new_stock') }} (KG)</th>
                      <th class="text-center">{{ $t('difference') }}</th>
                      <th class="text-center">{{ $t('price') }} (MAD)</th>
                      <th class="text-center">{{ $t('actions') }}</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="tn in article.tracking_numbers" :key="tn.id" :class="rowClass(tn)">
                      <td class="py-3">
                        <div class="d-flex ga-2 flex-wrap">
                          <v-chip v-for="(num, idx) in (tn.tracking_numbers || [])" :key="idx"
                            size="x-small" color="info" variant="outlined" class="font-weight-medium">
                            {{ num }}
                          </v-chip>
                          <span v-if="!tn.tracking_numbers || tn.tracking_numbers.length === 0" class="text-medium-emphasis">—</span>
                        </div>
                      </td>
                      <td class="py-3">{{ formatDate(tn.date_in) }}</td>
                      <td class="text-right font-weight-bold py-3">{{ fmt(tn.weight) }}</td>
                      <td class="text-right py-3" style="min-width: 150px;">
                        <v-text-field
                          v-model.number="adjustments[tn.id].new_weight"
                          type="number"
                          density="compact"
                          variant="outlined"
                          rounded
                          hide-details
                          :min="0"
                          step="0.01"
                          class="inv-inline-input text-info"
                          color="info"
                        ></v-text-field>
                      </td>
                      <td class="text-center py-3">
                        <v-chip
                          :color="getDiff(tn) > 0 ? 'success' : getDiff(tn) < 0 ? 'error' : 'default'"
                          variant="tonal"
                          size="small"
                          class="font-weight-bold"
                        >
                          {{ getDiff(tn) > 0 ? '+' : '' }}{{ fmt(getDiff(tn)) }} KG
                        </v-chip>
                      </td>
                      <td class="text-center py-3" style="min-width: 140px;">
                        <v-text-field
                          v-model.number="adjustments[tn.id].new_price"
                          type="number"
                          density="compact"
                          variant="outlined"
                          rounded
                          hide-details
                          :placeholder="$t('auto_wap')"
                          step="0.01"
                          class="inv-inline-input text-info"
                          color="info"
                        ></v-text-field>
                      </td>
                      <td class="text-center py-3">
                        <v-btn icon size="x-small" variant="tonal" color="error"
                          @click="confirmDelete(tn)">
                          <v-icon size="16">mdi-delete</v-icon>
                          <v-tooltip activator="parent" location="top">{{ $t('delete') }}</v-tooltip>
                        </v-btn>
                      </td>
                    </tr>
                  </tbody>
                </v-table>

                <!-- Add New Tracking Number Button -->
                <div class="pa-4 text-center">
                  <v-btn variant="tonal" color="info" size="small" rounded
                    prepend-icon="mdi-plus" @click="openAddDialog(article)">
                    {{ $t('add_tracking_number') }}
                  </v-btn>
                </div>
              </v-expansion-panel-text>
            </v-expansion-panel>
          </v-expansion-panels>
        </v-card-text>
      </v-card>

      <!-- ═══════════ SUBMIT SECTION ═══════════ -->
      <v-card class="inv-card inv-submit-card mb-6" elevation="0">
        <v-card-text class="pa-5">
          <div v-if="totalTrackingNumbers === 0" class="text-center py-3 text-medium-emphasis">
            <v-icon size="20" class="mr-1">mdi-information-outline</v-icon>
            {{ $t('no_tracking_numbers_to_adjust') }}
          </div>
          <div class="d-flex align-center ga-5 flex-wrap">
            <v-menu v-model="adjDateMenu" :close-on-content-click="false" location="bottom start">
              <template #activator="{ props }">
                <v-text-field
                  v-bind="props"
                  :model-value="formatDate(adjustmentDate)"
                  :label="$t('adjustment_date')"
                  density="compact"
                  variant="outlined"
                  rounded
                  hide-details
                  color="info"
                  prepend-inner-icon="mdi-calendar"
                  readonly
                  class="inv-date-field text-info"
                ></v-text-field>
              </template>
              <v-date-picker
                v-model="adjDatePicker"
                color="primary"
                :max="today"
                @update:model-value="onAdjDatePick"
              ></v-date-picker>
            </v-menu>
            <v-text-field
              v-model="reason"
              :label="$t('adjustment_reason')"
              :placeholder="$t('optional')"
              density="compact"
              variant="outlined"
              rounded
              hide-details
              color="info"
              class="flex-grow-1 text-info"
              prepend-inner-icon="mdi-text-box-outline"
            ></v-text-field>
            <v-btn
              color="info"
              variant="tonal"
              rounded
              size="large"
              :loading="saving"
              :disabled="!hasChanges"
              prepend-icon="mdi-check-circle"
              @click="submitAdjustments"
              class="px-6"
            >
              {{ $t('save_adjustments') }}
              <template v-if="changesCount > 0">
                ({{ changesCount }})
              </template>
            </v-btn>
          </div>
        </v-card-text>
      </v-card>
    </template>

    <!-- Empty state -->
    <div v-if="selectedWarehouse && !loadingArticles && articles.length === 0" class="text-center py-10">
      <v-icon size="64" color="grey" class="mb-3">mdi-package-variant-remove</v-icon>
      <p class="text-h6 text-medium-emphasis">{{ $t('no_articles_in_warehouse') }}</p>
      <v-btn variant="tonal" color="success" rounded="lg" class="mt-3"
        prepend-icon="mdi-plus-box" @click="openAddArticleDialog">
        {{ $t('add_article') }}
      </v-btn>
    </div>

    <!-- ═══════════ ADD TRACKING DIALOG ═══════════ -->
    <v-dialog v-model="addDialog" max-width="520" persistent>
      <v-card rounded="xl">
        <v-card-title class="d-flex align-center pt-5 px-5">
          <v-icon start color="primary">mdi-plus-circle</v-icon>
          {{ $t('add_tracking_number') }}
        </v-card-title>
        <v-card-text class="px-5">
          <v-text-field
            v-model="addForm.tracking"
            :label="$t('tracking_number')"
            density="compact"
            variant="outlined"
            rounded
            color="info"
            hide-details
            class="mb-4 text-info"
            :hint="$t('comma_separated')"
            persistent-hint
          ></v-text-field>
          <v-text-field
            v-model.number="addForm.weight"
            type="number"
            :label="$t('weight') + ' (KG)'"
            density="compact"
            variant="outlined"
            rounded
            color="info"
            hide-details
            class="mb-4 text-info"
            :min="0.01"
            step="0.01"
          ></v-text-field>
          <v-text-field
            v-model.number="addForm.price"
            type="number"
            :label="$t('price') + ' (MAD) — ' + $t('optional')"
            density="compact"
            variant="outlined"
            rounded
            color="info"
            hide-details
            class="mb-4 text-info"
            step="0.01"
          ></v-text-field>
          <v-autocomplete
            v-model="addForm.package_type_id"
            :items="packageTypes"
            item-value="id"
            :item-title="`name_${langCode}`"
            :label="$t('package_type')"
            chips
            density="compact"
            variant="outlined"
            rounded
            color="info"
            hide-details
            class="mb-4 text-info"
          >
            <template #chip="{ item, index }">
              <v-chip :key="index" color="info" variant="tonal" closable
                @click:close="addForm.package_type_id = null">{{ item.title }}</v-chip>
            </template>
          </v-autocomplete>
          <v-text-field
            v-model.number="addForm.packages"
            type="number"
            :label="$t('nbr_boxes')"
            density="compact"
            variant="outlined"
            rounded
            color="info"
            hide-details
            readonly
            class="mb-4 text-info"
            :min="0"
          ></v-text-field>
          <v-menu v-model="addDateMenu" :close-on-content-click="false" location="bottom start">
            <template #activator="{ props }">
              <v-text-field
                v-bind="props"
                :model-value="formatDate(addForm.date)"
                :label="$t('date')"
                density="compact"
                variant="outlined"
                rounded
                color="info"
                hide-details
                prepend-inner-icon="mdi-calendar"
                readonly
                class="mb-4 text-info"
              ></v-text-field>
            </template>
            <v-date-picker
              v-model="addDatePicker"
              color="primary"
              :max="today"
              @update:model-value="onAddDatePick"
            ></v-date-picker>
          </v-menu>
        </v-card-text>
        <v-card-actions class="px-5 pb-5">
          <v-spacer></v-spacer>
          <v-btn variant="text" rounded @click="addDialog = false">{{ $t('cancel') }}</v-btn>
          <v-btn color="info" variant="tonal" rounded :loading="addSaving"
            :disabled="!addForm.weight || addForm.weight <= 0 || !addForm.tracking"
            @click="submitAddTracking">
            {{ $t('add') }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- ═══════════ DELETE CONFIRM DIALOG ═══════════ -->
    <v-dialog v-model="deleteDialog" max-width="400">
      <v-card rounded="xl">
        <v-card-title class="text-error pt-5">
          <v-icon start color="error">mdi-alert-circle</v-icon>
          {{ $t('confirm_delete') }}
        </v-card-title>
        <v-card-text>
          {{ $t('delete_tracking_confirm') }}
          <div v-if="deleteTarget" class="mt-2">
            <v-chip v-for="(num, idx) in (deleteTarget.tracking_numbers || [])" :key="idx"
              size="small" color="primary" variant="outlined" class="me-1">
              {{ num }}
            </v-chip>
            <div class="text-caption mt-1">{{ fmt(deleteTarget.weight) }} KG</div>
          </div>
        </v-card-text>
        <v-card-actions class="px-5 pb-5">
          <v-spacer></v-spacer>
          <v-btn variant="text" @click="deleteDialog = false">{{ $t('cancel') }}</v-btn>
          <v-btn color="error" variant="flat" rounded="lg" :loading="deleting" @click="executeDelete">
            {{ $t('delete') }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- ═══════════ ADD ARTICLE DIALOG ═══════════ -->
    <v-dialog v-model="addArticleDialog" max-width="600" persistent>
      <v-card rounded="xl">
        <v-card-title class="d-flex align-center pt-5 px-5">
          <v-icon start color="success">mdi-plus-box</v-icon>
          {{ $t('add_article') }}
        </v-card-title>
        <v-card-subtitle class="px-5 pb-2">{{ $t('add_article_subtitle') }}</v-card-subtitle>
        <v-card-text class="px-5">
          <v-btn-toggle v-model="addArticleForm.type" mandatory color="primary" variant="outlined"
            rounded="lg" class="mb-4 w-100" divided density="comfortable">
            <v-btn value="FishType" class="flex-grow-1">
              <v-icon start size="18">mdi-fish</v-icon> {{ $t('fish') }}
            </v-btn>
            <v-btn value="GuanoType" class="flex-grow-1">
              <v-icon start size="18">mdi-leaf</v-icon> {{ $t('guano') }}
            </v-btn>
            <v-btn value="PackageType" class="flex-grow-1">
              <v-icon start size="18">mdi-package-variant</v-icon> {{ $t('package') }}
            </v-btn>
          </v-btn-toggle>

          <v-autocomplete
            v-model="addArticlePick"
            :items="availableArticles"
            item-value="id"
            :item-title="`name_${langCode}`"
            :label="$t('select_article')"
            density="comfortable"
            variant="outlined"
            rounded="lg"
            color="primary"
            hide-details
            :no-data-text="$t('no_articles_available')"
            @update:model-value="onArticlePicked"
          >
            <template #item="{ props, item }">
              <v-list-item v-bind="props">
                <template #prepend>
                  <v-avatar :color="typeColor(addArticleForm.type)" variant="tonal" size="32" rounded="lg">
                    <v-icon size="16">{{ typeIcon(addArticleForm.type) }}</v-icon>
                  </v-avatar>
                </template>
              </v-list-item>
            </template>
          </v-autocomplete>

          <div v-if="availableArticles.length === 0 && addArticleForm.type" class="text-center py-3">
            <v-icon color="grey" size="28">mdi-check-circle-outline</v-icon>
            <p class="text-caption text-medium-emphasis mt-1">{{ $t('all_articles_added') }}</p>
          </div>

          <!-- Selected articles cart -->
          <div v-if="selectedArticles.length > 0" class="mt-4">
            <div class="text-caption text-medium-emphasis mb-2">
              {{ $t('selected_articles') }} ({{ selectedArticles.length }})
            </div>
            <div class="d-flex flex-wrap ga-2">
              <v-chip v-for="(art, idx) in selectedArticles" :key="idx"
                :color="typeColor(art.type)" variant="tonal" closable
                @click:close="removeSelectedArticle(idx)">
                <v-icon start size="14">{{ typeIcon(art.type) }}</v-icon>
                {{ art.name }}
              </v-chip>
            </div>
          </div>
        </v-card-text>
        <v-card-actions class="px-5 pb-5">
          <v-spacer></v-spacer>
          <v-btn variant="text" @click="addArticleDialog = false">{{ $t('cancel') }}</v-btn>
          <v-btn color="success" variant="flat" rounded="lg" :loading="addArticleSaving"
            :disabled="selectedArticles.length === 0"
            @click="submitAddArticle">
            {{ $t('add') }} ({{ selectedArticles.length }})
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Snackbar -->
    <v-snackbar v-model="snackbar" :color="snackColor" rounded="lg" location="bottom right" :timeout="3000">
      {{ snackMessage }}
    </v-snackbar>
  </div>
</template>

<script>
import { usePackageType } from '@/stores/PackageType';
import { useFishType } from '@/stores/FishType';
import { useGuanoType } from '@/stores/GuanoType';
import { useMain } from '@/stores/Main';
import * as XLSX from 'xlsx';

export default {
  name: 'InventoryAdjustment',
  data() {
    return {
      // Warehouses
      warehouses: [],
      selectedWarehouse: null,
      // Articles
      articles: [],
      loadingArticles: false,
      articleSearch: '',
      expandedArticle: null,
      // Adjustments
      adjustments: {},
      adjustmentDate: new Date().toISOString().substring(0, 10),
      adjDateMenu: false,
      adjDatePicker: new Date(),
      reason: '',
      saving: false,
      // Add tracking
      addDialog: false,
      addSaving: false,
      addArticle: null,
      addForm: { tracking: '', weight: null, price: null, package_type_id: null, packages: 0, date: new Date().toISOString().substring(0, 10) },
      addDateMenu: false,
      addDatePicker: new Date(),
      // Delete
      deleteDialog: false,
      deleting: false,
      deleteTarget: null,
      // Add article
      addArticleDialog: false,
      addArticleSaving: false,
      addArticleForm: { type: 'FishType' },
      addArticlePick: null,
      selectedArticles: [], // [{type, id, name}]
      // Snackbar
      snackbar: false,
      snackMessage: '',
      snackColor: 'success',
    };
  },
  computed: {
    today() {
      return new Date();
    },
    packageTypes() {
      return usePackageType().getList;
    },
    fishTypes() {
      return useFishType().getList;
    },
    guanoTypes() {
      return useGuanoType().getList;
    },
    langCode() {
      return useMain().lang?.code || 'fr';
    },
    availableArticles() {
      const type = this.addArticleForm.type;
      let allArticles = [];
      if (type === 'FishType') allArticles = this.fishTypes;
      else if (type === 'GuanoType') allArticles = this.guanoTypes;
      else if (type === 'PackageType') allArticles = this.packageTypes;
      // Filter out articles already in this warehouse
      const existingIds = this.articles
        .filter(a => a.stockable_type === type)
        .map(a => a.article_id);
      // Also filter out articles already in the cart
      const cartIds = this.selectedArticles
        .filter(a => a.type === type)
        .map(a => a.id);
      return allArticles.filter(a => !existingIds.includes(a.id) && !cartIds.includes(a.id));
    },
    warehouseInfo() {
      return this.warehouses.find(w => w.id === this.selectedWarehouse);
    },
    filteredArticles() {
      if (!this.articleSearch) return this.articles;
      const q = this.articleSearch.toLowerCase();
      return this.articles.filter(a =>
        a.article_name.toLowerCase().includes(q) ||
        a.stockable_type.toLowerCase().includes(q) ||
        a.tracking_numbers.some(tn =>
          (tn.tracking_numbers || []).some(num => String(num).toLowerCase().includes(q))
        )
      );
    },
    changesCount() {
      let count = 0;
      for (const article of this.articles) {
        for (const tn of article.tracking_numbers) {
          const adj = this.adjustments[tn.id];
          if (!adj) continue;
          const newW = Number(adj.new_weight);
          if (isNaN(newW)) continue;
          if (round2(newW) !== round2(tn.weight)) {
            count++;
          }
        }
      }
      return count;
    },
    hasChanges() {
      return this.changesCount > 0;
    },
    totalTrackingNumbers() {
      return this.articles.reduce((sum, a) => sum + (a.tracking_numbers ? a.tracking_numbers.length : 0), 0);
    },
  },
  watch: {
    'addForm.weight'(val) {
      this.autoCalcPackages();
    },
    'addForm.package_type_id'() {
      this.autoCalcPackages();
    },
  },
  mounted() {
    this.fetchWarehouses();
  },
  methods: {
    fmt(num) {
      if (!num && num !== 0) return '0,00';
      return new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(num);
    },
    formatDate(d) {
      if (!d) return 'N/A';
      return new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' });
    },
    exportToExcel() {
      if (!this.articles.length) return;
      const whName = this.warehouseInfo?.name || 'warehouse';
      const headers = [
        this.$t('article'),
        this.$t('type'),
        this.$t('tracking_number'),
        this.$t('date_entered'),
        this.$t('current_stock') + ' (KG)',
        this.$t('price') + ' (MAD)',
      ];
      const rows = [];
      for (const article of this.articles) {
        for (const tn of article.tracking_numbers) {
          rows.push([
            article.article_name,
            article.stockable_type,
            (tn.tracking_numbers || []).join(', '),
            tn.date_in || '',
            tn.weight || 0,
            tn.price || 0,
          ]);
        }
      }
      const dataAoA = [headers, ...rows];
      const ws = XLSX.utils.aoa_to_sheet(dataAoA);
      // Set column widths
      ws['!cols'] = [
        { wch: 24 }, { wch: 14 }, { wch: 28 },
        { wch: 14 }, { wch: 16 }, { wch: 16 },
      ];
      const wb = XLSX.utils.book_new();
      XLSX.utils.book_append_sheet(wb, ws, whName);
      const filename = `inventory_${whName}_${new Date().toISOString().slice(0,10)}.xlsx`;
      XLSX.writeFile(wb, filename);
      this.snackMessage = this.$t('export_success') || 'Exported successfully';
      this.snackColor = 'success';
      this.snackbar = true;
    },
    typeColor(type) {
      if (type === 'FishType') return 'info';
      if (type === 'GuanoType') return 'brown';
      if (type === 'PackageType') return 'teal';
      return 'grey';
    },
    typeIcon(type) {
      if (type === 'FishType') return 'mdi-fish';
      if (type === 'GuanoType') return 'mdi-leaf';
      if (type === 'PackageType') return 'mdi-package-variant';
      return 'mdi-help-circle';
    },
    getDiff(tn) {
      const adj = this.adjustments[tn.id];
      if (!adj) return 0;
      return round2(adj.new_weight) - round2(tn.weight);
    },
    rowClass(tn) {
      const diff = this.getDiff(tn);
      if (diff > 0) return 'inv-row-increase';
      if (diff < 0) return 'inv-row-decrease';
      return '';
    },

    // ─── Data ───
    async fetchWarehouses() {
      try {
        const res = await axios.get('/api/warehouses');
        this.warehouses = res.data.warehouses;
        // Auto-select first warehouse
        if (this.warehouses.length > 0 && !this.selectedWarehouse) {
          this.selectedWarehouse = this.warehouses[0].id;
          this.onWarehouseChange(this.selectedWarehouse);
        }
      } catch (e) { console.error(e); }
    },
    async onWarehouseChange(id) {
      if (!id) {
        this.articles = [];
        this.adjustments = {};
        return;
      }
      this.loadingArticles = true;
      this.articles = [];
      this.adjustments = {};
      this.expandedArticle = null;
      try {
        const res = await axios.get(`/api/inventory/${id}/articles`);
        this.articles = res.data.articles;
        this.buildAdjustments();
      } catch (e) { console.error(e); }
      finally { this.loadingArticles = false; }
    },
    buildAdjustments() {
      const adj = {};
      for (const article of this.articles) {
        for (const tn of article.tracking_numbers) {
          adj[tn.id] = {
            new_weight: round2(tn.weight),
            new_price: null,
            new_packages: null,
          };
        }
      }
      this.adjustments = adj;
    },

    // ─── Submit Adjustments ───
    async submitAdjustments() {
      const changes = [];
      for (const article of this.articles) {
        for (const tn of article.tracking_numbers) {
          const adj = this.adjustments[tn.id];
          if (!adj) continue;
          const newW = Number(adj.new_weight);
          // Skip if not a valid number or no change
          if (isNaN(newW)) continue;
          if (round2(newW) !== round2(tn.weight)) {
            changes.push({
              tracking_number_id: tn.id,
              new_weight: round2(newW),
              new_price: adj.new_price ? Number(adj.new_price) : null,
              new_packages: adj.new_packages ? Number(adj.new_packages) : null,
            });
          }
        }
      }

      if (changes.length === 0) {
        this.showSnack(this.$t('no_changes'), 'warning');
        return;
      }

      this.saving = true;
      try {
        const res = await axios.post('/api/inventory/adjust', {
          warehouse_id: this.selectedWarehouse,
          date: this.adjustmentDate,
          reason: this.reason || null,
          adjustments: changes,
        });
        this.showSnack(this.$t('adjustment_saved') + ` (${res.data.changes} ${this.$t('changes')})`, 'success');
        this.reason = '';
        // Refresh
        await this.onWarehouseChange(this.selectedWarehouse);
        await this.fetchWarehouses();
      } catch (e) {
        const msg = e.response?.data?.message || 'Error';
        this.showSnack(msg, 'error');
      }
      finally { this.saving = false; }
    },

    // ─── Add Tracking ───
    openAddDialog(article) {
      this.addArticle = article;
      this.addForm = { tracking: '', weight: null, price: null, package_type_id: null, packages: 0, date: this.adjustmentDate };
      this.addDatePicker = new Date(this.adjustmentDate);
      this.addDialog = true;
    },
    autoCalcPackages() {
      if (this.addForm.package_type_id && this.addForm.weight) {
        const pkg = this.packageTypes.find(p => p.id === this.addForm.package_type_id);
        if (pkg && pkg.size > 0) {
          this.addForm.packages = +(this.addForm.weight / pkg.size).toFixed(1);
        }
      }
    },
    onAdjDatePick(val) {
      if (val) {
        const d = new Date(val);
        this.adjustmentDate = d.toISOString().substring(0, 10);
      }
      this.adjDateMenu = false;
    },
    onAddDatePick(val) {
      if (val) {
        const d = new Date(val);
        this.addForm.date = d.toISOString().substring(0, 10);
      }
      this.addDateMenu = false;
    },
    async submitAddTracking() {
      this.addSaving = true;
      try {
        const trackingArr = this.addForm.tracking.split(',').map(s => s.trim()).filter(Boolean);
        await axios.post('/api/inventory/add-tracking', {
          warehouse_id: this.selectedWarehouse,
          stockable_id: this.addArticle.stockable_id,
          tracking_numbers: trackingArr,
          weight: this.addForm.weight,
          price: this.addForm.price,
          package_type_id: this.addForm.package_type_id,
          packages: this.addForm.packages || 0,
          date: this.addForm.date,
        });
        this.showSnack(this.$t('tracking_added'), 'success');
        this.addDialog = false;
        await this.onWarehouseChange(this.selectedWarehouse);
        await this.fetchWarehouses();
      } catch (e) {
        const msg = e.response?.data?.message || 'Error';
        this.showSnack(msg, 'error');
      }
      finally { this.addSaving = false; }
    },

    // ─── Delete ───
    confirmDelete(tn) {
      this.deleteTarget = tn;
      this.deleteDialog = true;
    },
    async executeDelete() {
      this.deleting = true;
      try {
        await axios.delete(`/api/inventory/tracking/${this.deleteTarget.id}`);
        this.showSnack(this.$t('tracking_deleted'), 'success');
        this.deleteDialog = false;
        await this.onWarehouseChange(this.selectedWarehouse);
        await this.fetchWarehouses();
      } catch (e) {
        const msg = e.response?.data?.message || 'Error';
        this.showSnack(msg, 'error');
      }
      finally { this.deleting = false; }
    },

    showSnack(msg, color) {
      this.snackMessage = msg;
      this.snackColor = color;
      this.snackbar = true;
    },

    // ─── Add Article ───
    openAddArticleDialog() {
      this.addArticleForm = { type: 'FishType' };
      this.addArticlePick = null;
      this.selectedArticles = [];
      this.addArticleDialog = true;
    },
    onArticlePicked(id) {
      if (!id) return;
      const type = this.addArticleForm.type;
      const langKey = `name_${this.langCode}`;
      let allArticles = [];
      if (type === 'FishType') allArticles = this.fishTypes;
      else if (type === 'GuanoType') allArticles = this.guanoTypes;
      else if (type === 'PackageType') allArticles = this.packageTypes;
      const art = allArticles.find(a => a.id === id);
      if (art && !this.selectedArticles.some(s => s.type === type && s.id === id)) {
        this.selectedArticles.push({ type, id, name: art[langKey] || art.name_fr || art.name || `#${id}` });
      }
      this.$nextTick(() => { this.addArticlePick = null; });
    },
    removeSelectedArticle(idx) {
      this.selectedArticles.splice(idx, 1);
    },
    async submitAddArticle() {
      this.addArticleSaving = true;
      try {
        await axios.post('/api/inventory/add-article', {
          warehouse_id: this.selectedWarehouse,
          articles: this.selectedArticles.map(a => ({ stockable_type: a.type, stockable_id: a.id })),
        });
        this.showSnack(this.$t('article_added') + ` (${this.selectedArticles.length})`, 'success');
        this.addArticleDialog = false;
        await this.onWarehouseChange(this.selectedWarehouse);
        await this.fetchWarehouses();
      } catch (e) {
        const msg = e.response?.data?.message || 'Error';
        this.showSnack(msg, 'error');
      }
      finally { this.addArticleSaving = false; }
    },
  },
};

function round2(v) {
  return Math.round((Number(v) || 0) * 100) / 100;
}
</script>

<style scoped>
.inv-page {
  padding: 24px;
  max-width: 1400px;
  margin: 0 auto;
}

.inv-header {
  margin-bottom: 32px;
  position: relative;
}

.inv-header-glow {
  position: absolute;
  top: -30px;
  left: -20px;
  width: 200px;
  height: 200px;
  background: radial-gradient(circle, rgba(25,118,210,0.08) 0%, transparent 70%);
  border-radius: 50%;
  pointer-events: none;
}

.inv-icon-wrap {
  width: 48px;
  height: 48px;
  border-radius: 14px;
  background: linear-gradient(135deg, #1976d2, #42a5f5);
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4px 16px rgba(25,118,210,0.25);
}

.inv-title {
  font-size: 26px;
  font-weight: 800;
  margin-bottom: 2px;
  background: linear-gradient(135deg, #1976d2, #42a5f5);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.inv-subtitle {
  font-size: 14px;
  color: rgba(var(--v-theme-on-surface), 0.55);
  margin: 0;
}

.inv-card {
  background: rgba(var(--v-theme-surface), 0.85) !important;
  backdrop-filter: blur(16px);
  border: 1px solid rgba(var(--v-theme-on-surface), 0.06);
  border-radius: 20px !important;
  box-shadow: 0 4px 24px rgba(0,0,0,0.04);
  transition: box-shadow 0.3s ease, transform 0.2s ease;
}

.inv-card:hover {
  box-shadow: 0 8px 32px rgba(0,0,0,0.06);
}

.inv-wh-select {
  min-width: 280px;
  max-width: 400px;
}

.inv-article-search {
  max-width: 280px;
}

.inv-article-panel {
  border-bottom: 1px solid rgba(var(--v-theme-on-surface), 0.06);
  transition: background 0.2s ease;
}

.inv-article-panel:hover {
  background: rgba(25, 118, 210, 0.02);
}

.inv-tn-table {
  background: transparent !important;
}

.inv-tn-table th {
  font-weight: 700 !important;
  font-size: 11px !important;
  text-transform: uppercase;
  letter-spacing: 0.8px;
  opacity: 0.6;
  background: rgba(var(--v-theme-on-surface), 0.02) !important;
}

.inv-inline-input {
  max-width: 130px;
  display: inline-flex;
}

.inv-inline-input :deep(input) {
  text-align: right;
  font-weight: 600;
}

.inv-row-increase {
  background: rgba(46, 125, 50, 0.05);
  transition: background 0.2s ease;
}

.inv-row-increase:hover {
  background: rgba(46, 125, 50, 0.08);
}

.inv-row-decrease {
  background: rgba(211, 47, 47, 0.05);
  transition: background 0.2s ease;
}

.inv-row-decrease:hover {
  background: rgba(211, 47, 47, 0.08);
}

.inv-submit-card {
  border: 2px solid rgba(25, 118, 210, 0.15);
  background: linear-gradient(135deg, rgba(25,118,210,0.03), rgba(66,165,245,0.03)) !important;
}

.inv-date-field {
  max-width: 200px;
}

/* Responsive */
@media (max-width: 768px) {
  .inv-page { padding: 12px; }
  .inv-wh-select { min-width: 100%; }
  .inv-article-search { max-width: 100%; }
  .inv-date-field { max-width: 100%; }
}
</style>
