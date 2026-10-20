<template>
  <v-card class="ot-card" elevation="0">
    <!-- Hero Header -->
    <div class="ot-hero">
      <div class="ot-hero-inner">
        <div class="d-flex align-center ga-4">
          <div class="ot-hero-icon">
            <v-icon size="28" color="white">mdi-bank-transfer</v-icon>
          </div>
          <div>
            <div class="ot-hero-title">{{ $t('order_transfers') }}</div>
            <div class="text-white text-caption" style="opacity:.7">{{ $t('order_transfer_desc') }}</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Tab Slider (Matching Activity Breakdown Entity Toggle) -->
    <div class="d-flex align-center justify-space-between mx-4 mt-3">
      <SegmentedPillToggle
        v-model="activeTab"
        :items="orderTransferTabs"
        color="#00897B"
      />
    </div>

    <v-divider class="mx-4 mt-3" color="#00897B"></v-divider>

    <div class="pa-4">
      <ListOrderTransfer v-if="activeTab === 0" ref="listRef" @edit="onEdit" />
      <AddOrderTransfer v-if="activeTab === 1" :edit-data="editData" @saved="onSaved" @cancel-edit="editData = null" />
    </div>
  </v-card>
</template>

<script>
import ListOrderTransfer from './ListOrderTransfer.vue';
import AddOrderTransfer from './AddOrderTransfer.vue';
import SegmentedPillToggle from '@/components/common/SegmentedPillToggle.vue';
import { useAuth } from '@/stores/Auth';

export default {
  name: 'OrderTransferPage',
  components: { ListOrderTransfer, AddOrderTransfer, SegmentedPillToggle },
  data() {
    return {
      activeTab: 0,
      editData: null,
    };
  },
  computed: {
    auth() { return useAuth(); },
    orderTransferTabs() {
      return [
        { value: 0, label: this.$t('list_order_transfer'), icon: 'mdi-format-list-bulleted' },
        ...(this.auth.hasPermission('order_transfers.create')
          ? [{ value: 1, label: this.$t('add_order_transfer'), icon: 'mdi-plus-circle-outline' }]
          : []),
      ];
    },
  },
  methods: {
    onEdit(transfer) {
      this.editData = { ...transfer };
      this.activeTab = 1;
    },
    onSaved() {
      this.editData = null;
      this.activeTab = 0;
      this.$nextTick(() => {
        if (this.$refs.listRef) this.$refs.listRef.fetchTransfers();
      });
    },
  },
  watch: {
    activeTab(v) {
      if (v === 0) {
        this.editData = null;
        this.$nextTick(() => {
          if (this.$refs.listRef) this.$refs.listRef.fetchTransfers();
        });
      }
    },
  },
};
</script>

<style scoped>
.ot-card { border-radius: 20px !important; overflow: visible; font-family: 'Inter', sans-serif; }
.ot-hero { background: linear-gradient(135deg, #00695C 0%, #00897B 40%, #4DB6AC 100%); border-radius: inherit; border-bottom-left-radius: 0; border-bottom-right-radius: 0; height: 100px; }
.ot-hero-inner { position: relative; z-index: 1; padding: 22px 26px; color: #fff; }
.ot-hero-icon { width: 52px; height: 52px; border-radius: 14px; background: rgba(255,255,255,.18); backdrop-filter: blur(6px); display: flex; align-items: center; justify-content: center; border: 1px solid rgba(255,255,255,.15); }
.ot-hero-title { font-size: 19px; font-weight: 800; letter-spacing: .3px; }
.ot-tabs { background: rgba(0,137,123,.04); }
</style>
