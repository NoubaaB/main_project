<template>
<v-card class="pm-card" elevation="0">
  <!-- Hero Header -->
  <div class="pm-hero"><div class="pm-hero-inner">
    <div class="d-flex align-center ga-4"><div class="pm-hero-icon"><v-icon size="28" color="white">mdi-factory</v-icon></div><div>
      <div class="pm-hero-title">{{ $t('production_management') }}</div>
      <div class="text-white text-caption" style="opacity:.7">{{ $t('production_management_desc') }}</div>
    </div></div>
  </div></div>
  <!-- Toolbar with SegmentedPillToggle (Activity Breakdown style) -->
  <div class="pm-toolbar-card mx-4 mt-3 mb-2 pa-2 rounded-xl border d-flex flex-wrap align-center justify-space-between ga-3">
    <div class="d-flex flex-wrap align-center ga-3">
      <SegmentedPillToggle
        v-model="activeTab"
        :items="[
          { value: 'categories', label: $t('production_categories'), icon: 'mdi-cog-outline' },
          { value: 'details', label: $t('detail_production_categories'), icon: 'mdi-format-list-checks' },
        ]"
        color="#00897B"
      />
    </div>
  </div>

  <!-- TAB: Production Categories -->
  <div v-show="activeTab === 'categories'" class="pa-0">
    <ProductionCategoryManagement :embedded="true" />
  </div>
  <!-- TAB: Detail Production Categories -->
  <div v-show="activeTab === 'details'" class="pa-0">
    <DetailProductionCategoryManagement :embedded="true" />
  </div>
</v-card>
</template>

<script>
import SegmentedPillToggle from '@/components/common/SegmentedPillToggle.vue';
import ProductionCategoryManagement from '@/components/parameters/ProductionCategoryManagement/index.vue';
import DetailProductionCategoryManagement from '@/components/parameters/DetailProductionCategoryManagement/index.vue';

export default {
  name: 'ProductionManagement',
  components: { SegmentedPillToggle, ProductionCategoryManagement, DetailProductionCategoryManagement },
  data() {
    return {
      activeTab: 'categories',
    };
  },
};
</script>

<style scoped>
.pm-card { border-radius: 20px !important; overflow: visible; position: relative; font-family: 'Inter', sans-serif; }
.pm-hero { background: linear-gradient(135deg, rgb(0, 105, 92) 0%, rgb(0, 137, 123) 40%, rgb(38, 166, 154) 100%); border-radius: inherit; border-bottom-left-radius: 0; border-bottom-right-radius: 0; height: 100px; }
.pm-hero-inner { position: relative; z-index: 1; padding: 22px 26px; color: #fff; }
.pm-hero-icon { width: 52px; height: 52px; border-radius: 14px; background: rgba(255,255,255,.18); backdrop-filter: blur(6px); display: flex; align-items: center; justify-content: center; border: 1px solid rgba(255,255,255,.15); }
.pm-hero-title { font-size: 19px; font-weight: 800; letter-spacing: .3px; }
.pm-toolbar-card { background: rgba(var(--v-theme-surface), 0.7); backdrop-filter: blur(8px); }
</style>
