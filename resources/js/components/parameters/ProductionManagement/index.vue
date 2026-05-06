<template>
<v-card class="pm-card" elevation="0">
  <!-- Hero Header -->
  <div class="pm-hero"><div class="pm-hero-inner">
    <div class="d-flex align-center ga-4"><div class="pm-hero-icon"><v-icon size="28" color="white">mdi-factory</v-icon></div><div>
      <div class="pm-hero-title">{{ $t('production_management') }}</div>
      <div class="text-white text-caption" style="opacity:.7">{{ $t('production_management_desc') }}</div>
    </div></div>
  </div></div>
  <!-- Tab Slider -->
  <v-slide-group v-model="activeTab" mandatory show-arrows class="pm-tab-slider mx-4 mt-3">
    <v-slide-group-item v-for="tab in tabs" :key="tab.value" v-slot="{ isSelected, toggle }">
      <v-btn :color="isSelected ? '#00897B' : undefined" :variant="isSelected ? 'flat' : 'text'" rounded class="mx-1" @click="toggle" :prepend-icon="tab.icon">{{ $t(tab.label) }}</v-btn>
    </v-slide-group-item>
  </v-slide-group>
  <v-divider class="mx-4 mt-2" color="#00897B"></v-divider>
  <!-- TAB: Production Categories -->
  <div v-show="activeTab === 0" class="pa-0">
    <ProductionCategoryManagement :embedded="true" />
  </div>
  <!-- TAB: Detail Production Categories -->
  <div v-show="activeTab === 1" class="pa-0">
    <DetailProductionCategoryManagement :embedded="true" />
  </div>
</v-card>
</template>

<script>
import ProductionCategoryManagement from '@/components/parameters/ProductionCategoryManagement/index.vue';
import DetailProductionCategoryManagement from '@/components/parameters/DetailProductionCategoryManagement/index.vue';

export default {
  name: 'ProductionManagement',
  components: { ProductionCategoryManagement, DetailProductionCategoryManagement },
  data() {
    return {
      activeTab: 0,
      tabs: [
        { value: 0, label: 'production_categories', icon: 'mdi-cog-outline' },
        { value: 1, label: 'detail_production_categories', icon: 'mdi-format-list-checks' },
      ],
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
.pm-tab-slider { border-radius: 12px; }
</style>
