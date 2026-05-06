<template>
<v-card class="sm-card" elevation="0">
  <!-- Hero Header -->
  <div class="sm-hero"><div class="sm-hero-inner">
    <div class="d-flex align-center ga-4"><div class="sm-hero-icon"><v-icon size="28" color="white">mdi-package-variant-closed</v-icon></div><div>
      <div class="sm-hero-title">{{ $t('shipping_management') }}</div>
      <div class="text-white text-caption" style="opacity:.7">{{ $t('shipping_management_desc') }}</div>
    </div></div>
  </div></div>
  <!-- Tab Slider -->
  <v-slide-group v-model="activeTab" mandatory show-arrows class="sm-tab-slider mx-4 mt-3">
    <v-slide-group-item v-for="tab in tabs" :key="tab.value" v-slot="{ isSelected, toggle }">
      <v-btn :color="isSelected ? '#4643a0' : undefined" :variant="isSelected ? 'flat' : 'text'" rounded class="mx-1" @click="toggle" :prepend-icon="tab.icon">{{ $t(tab.label) }}</v-btn>
    </v-slide-group-item>
  </v-slide-group>
  <v-divider class="mx-4 mt-2" color="#4643a0"></v-divider>
  <!-- TAB: Shipping Categories -->
  <div v-show="activeTab === 0" class="pa-0">
    <ShippingCategoryManagement :embedded="true" />
  </div>
  <!-- TAB: Detail Shipping Categories -->
  <div v-show="activeTab === 1" class="pa-0">
    <DetailShippingCategoryManagement :embedded="true" />
  </div>
</v-card>
</template>

<script>
import ShippingCategoryManagement from '@/components/parameters/ShippingCategoryManagement/index.vue';
import DetailShippingCategoryManagement from '@/components/parameters/DetailShippingCategoryManagement/index.vue';

export default {
  name: 'ShippingManagement',
  components: { ShippingCategoryManagement, DetailShippingCategoryManagement },
  data() {
    return {
      activeTab: 0,
      tabs: [
        { value: 0, label: 'shipping_categories', icon: 'mdi-tag-multiple-outline' },
        { value: 1, label: 'detail_shipping_categories', icon: 'mdi-format-list-checks' },
      ],
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
.sm-tab-slider { border-radius: 12px; }
</style>
