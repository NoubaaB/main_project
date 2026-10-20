<template>
<v-card class="sm-card" elevation="0">
  <!-- Hero Header -->
  <div class="sm-hero"><div class="sm-hero-inner">
    <div class="d-flex align-center ga-4"><div class="sm-hero-icon"><v-icon size="28" color="white">mdi-package-variant-closed</v-icon></div><div>
      <div class="sm-hero-title">{{ $t('shipping_management') }}</div>
      <div class="text-white text-caption" style="opacity:.7">{{ $t('shipping_management_desc') }}</div>
    </div></div>
  </div></div>
  <!-- Toolbar with SegmentedPillToggle (Activity Breakdown style) -->
  <div class="sm-toolbar-card mx-4 mt-3 mb-2 pa-2 rounded-xl border d-flex flex-wrap align-center justify-space-between ga-3">
    <div class="d-flex flex-wrap align-center ga-3">
      <SegmentedPillToggle
        v-model="activeTab"
        :items="[
          { value: 'categories', label: $t('shipping_categories'), icon: 'mdi-tag-multiple-outline' },
          { value: 'details', label: $t('detail_shipping_categories'), icon: 'mdi-format-list-checks' },
        ]"
        color="#4643a0"
      />
    </div>
  </div>

  <!-- TAB: Shipping Categories -->
  <div v-show="activeTab === 'categories'" class="pa-0">
    <ShippingCategoryManagement :embedded="true" />
  </div>
  <!-- TAB: Detail Shipping Categories -->
  <div v-show="activeTab === 'details'" class="pa-0">
    <DetailShippingCategoryManagement :embedded="true" />
  </div>
</v-card>
</template>

<script>
import SegmentedPillToggle from '@/components/common/SegmentedPillToggle.vue';
import ShippingCategoryManagement from '@/components/parameters/ShippingCategoryManagement/index.vue';
import DetailShippingCategoryManagement from '@/components/parameters/DetailShippingCategoryManagement/index.vue';

export default {
  name: 'ShippingManagement',
  components: { SegmentedPillToggle, ShippingCategoryManagement, DetailShippingCategoryManagement },
  data() {
    return {
      activeTab: 'categories',
    };
  },
};
</script>

<style scoped>
.sm-card { border-radius: 20px !important; overflow: visible; position: relative; font-family: 'Inter', sans-serif; }
.sm-hero { background: linear-gradient(135deg, #372e7d 0%, #4643a0 40%, #6c66bb 100%); border-radius: inherit; border-bottom-left-radius: 0; border-bottom-right-radius: 0; height: 100px; }
.sm-hero-inner { position: relative; z-index: 1; padding: 22px 26px; color: #fff; }
.sm-hero-icon { width: 52px; height: 52px; border-radius: 14px; background: rgba(255,255,255,.18); backdrop-filter: blur(6px); display: flex; align-items: center; justify-content: center; border: 1px solid rgba(255,255,255,.15); }
.sm-hero-title { font-size: 19px; font-weight: 800; letter-spacing: .3px; }
.sm-toolbar-card { background: rgba(var(--v-theme-surface), 0.7); backdrop-filter: blur(8px); }
</style>
